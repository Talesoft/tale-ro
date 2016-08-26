<?php

namespace Tale\Ro;

use Tale\Ro\Grf\File;
use Tale\Ro\Grf\File\Info;
use Tale\Ro\Grf\Header;

class Grf extends AbstractFormat
{

    const MAGIC_HEADER = 'Master of Magic';

    const MAGIC_HEADER_SIZE = 15; //Length of MAGIC_HEADER
    const HEADER_MID_SIZE = self::MAGIC_HEADER_SIZE + 15; //Length of MAGIC_HEADER + ENCRYPTION_WATERMARK
    const HEADER_SIZE = self::HEADER_MID_SIZE + 16; //HEADER_MID_SIZE + 4 ints (table offset, seed, fcount, version)

    //Important for GRF files with encryption
    const ENCRYPTED_ENCRYPTION_WATERMARK = [
        0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0A, 0x0B, 0x0C, 0x0D, 0x0E
    ];

    //Can be used on 0x200 unencrypted GRF files
    const UNENCRYPTED_ENCRYPTION_WATERMARK = [
        0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00
    ];

    const FILE_TYPE_FILE = 0x01;
    const FILE_TYPE_ENCRYPTED_MIXED = 0x02;
    const FILE_TYPE_ENCRYPTED_DES = 0x04;

    const VERSION_200 = 0x200;
    const VERSION_103 = 0x103;
    const VERSION_102 = 0x102;

    const ENCODING_UTF8 = 'UTF-8';
    const ENCODING_ASCII = 'ASCII';
    const ENCODING_EUC_KR = 'EUC-KR';

    private $pathEncoding;
    private $header;
    private $files;

    public function __construct($handle, $pathEncoding = null)
    {
        parent::__construct($handle);
        
        $this->pathEncoding = $pathEncoding ?: self::ENCODING_EUC_KR;
        $this->header = $this->readHeader();
        $this->files = $this->readFileTable();
    }

    public function isUsingUtf8Paths()
    {

        return $this->pathEncoding;
    }

    /**
     * @return Header
     */
    public function getHeader()
    {

        return $this->header;
    }

    /**
     * @return File[]
     */
    public function getFiles()
    {

        return $this->files;
    }

    /**
     * @param $path
     * @return bool
     */
    public function hasFile($path)
    {

        return isset($this->files[$path]);
    }

    /**
     * @param $path
     * @return File
     */
    public function getFile($path)
    {

        return $this->files[$path];
    }

    /**
     * @param $pattern
     * @param null $resultCount
     * @return File[]
     */
    public function searchFiles($pattern, $resultCount = null)
    {

        $files = [];
        $i = 0;
        foreach ($this->files as $path => $info) {
            if (preg_match($pattern, $path)) {

                $files[$path] = $info;

                if ($resultCount !== null && ++$i >= $resultCount)
                    break;
            }
        }

        return $files;
    }

    /**
     * @param $path
     * @param bool $recursive
     * @param bool $sort
     * @return File[]
     */
    public function getFilesIn($path, $recursive = false, $sort = true)
    {

        $path = trim($path, '\\').'\\';
        $len = mb_strlen($path);

        $dirs = [];
        $files = [];
        foreach ($this->files as $filePath => $info) {

            if ($path !== '\\' && strncmp($path, $filePath, $len) !== 0)
                continue;

            if (!$recursive && ($pos = mb_strpos(mb_substr($filePath, $len), '\\')) !== false) {

                //This could still be an interesting path, for a directory name!
                //Notice that usually directories don't exist in the file table, we check it prior to that
                //(if there is a directory with that name, it will land in here automatically through this loop)

                $dirPath = mb_substr($filePath, 0, $pos + $len);

                if ($dirPath !== $path && !isset($this->files[$dirPath]) && !isset($dirs[$dirPath]))
                    $dirs[$dirPath] = new File($this, $dirPath);

                continue;
            }

            $files[$filePath] = $info;
        }

        if ($sort) {

            $caseInsensitiveSorter = function($a, $b) {

                return strcmp(strtolower($a), strtolower($b));
            };

            uksort($dirs, $caseInsensitiveSorter);
            uksort($files, $caseInsensitiveSorter);
        }

        return array_replace($dirs, $files);
    }

    private function createEmptyHeader()
    {

        return new Header(
            Grf::MAGIC_HEADER,
            Grf::ENCRYPTED_ENCRYPTION_WATERMARK,
            0,
            0,
            0,
            Grf::VERSION_200
        );
    }

    private function readHeader()
    {

        if ($this->getSize() === 0)
            return $this->createEmptyHeader();

        if ($this->getSize() < Grf::HEADER_SIZE) {
            throw new \RuntimeException(
                "Passed GRF file is not empty, but not a valid GRF file"
            );
        }

        //Make sure we're at the start of the GRF
        fseek($this->getHandle(), 0);

        $header = unpack(
            'A15magicHeader/A15encryptionWatermark/VfileTableOffset/Vseed/VfileCount/Vversion',
            fread($this->getHandle(), Grf::HEADER_SIZE)
        );

        //Read and check magic header
        if ($header['magicHeader'] !== Grf::MAGIC_HEADER)
            throw new \RuntimeException(
                "Passed GRF file is not a valid GRF file (Magic header mismatch)"
            );

        return new Header(
            $header['magicHeader'],
            array_map('ord', str_split($header['encryptionWatermark'])),
            $header['fileTableOffset'],
            $header['seed'],
            $header['fileCount'] - $header['seed'] - 7, //Notice this. It's important. Failed obfuscation attempt of gravity.
            $header['version']
        );
    }

    private function readFileTable()
    {

        //No files, no need to parse any table
        if ($this->header->getFileCount() < 1)
            return [];

        //Calculate total offset of the file table
        $offset = $this->header->getFileTableOffset() + Grf::HEADER_SIZE;

        if ($this->getSize() < $offset + 8 + 1)
            //Notice, after the offset (which is also the size until there) there should be two further ints (8 bytes)
            //for the size and compressed size. If thats not the case, kill.
            throw new \RuntimeException(
                "Passed GRF contains invalid file table offsets"
            );

        //Jump to file table offset
        fseek($this->getHandle(), $offset);

        //Extract compressed size and size
        $data = unpack('VcompressedSize/Vsize', fread($this->getHandle(), 8));

        //Read the compressed file table
        $compressedTableData = fread($this->getHandle(), $data['compressedSize']);

        //Decompress table (Not using the second parameter, it leads to insufficient memory errors :()
        $tableData = @zlib_decode($compressedTableData);

        if ($tableData === false)
            throw new \RuntimeException(
                "Failed to decompress the file table"
            );

        //Validate
        if (strlen($tableData) !== $data['size'])
            throw new \RuntimeException(
                "Passed GRF contains a corrupt file table: File table size was ".strlen($tableData).", but {$data['size']} was expected"
            );

        //Parse based on GRF version
        if ($this->header->getVersion() >= Grf::VERSION_200)
            return $this->parseFileTable200($tableData);

        return $this->parseFileTable10x($tableData);
    }

    private function parseFileTable200($tableData)
    {

        $files = [];
        for ($i = 0, $o = 0; $i < $this->header->getFileCount(); $i++) {

            //read filename until 0x00
            $path = '';
            $null = chr(0x00);
            while (($char = substr($tableData, $o, 1)) !== $null) {

                $path .= $char;
                $o++;
            }
            $o++;

            //Convert the path to unicode (Warning: This will require mb_* pendants at points working with the path)
            if ($this->pathEncoding !== self::ENCODING_UTF8)
                $path = mb_convert_encoding($path, self::ENCODING_UTF8, $this->pathEncoding);

            $data = unpack('VcompressedSize/ValignedSize/Vsize/Cflags/Voffset', substr($tableData, $o, 17));
            $o += 17;

            //The meta info is separated from the file, since it doesn't exist on added or modified files
            $archiveInfo = new Info(
                $data['compressedSize'],
                $data['alignedSize'],
                $data['size'],
                $data['offset']
            );

            $files[$path] = new File(
                $this,
                $path,
                $archiveInfo,
                $data['flags'],
                null
            );
        }

        return $files;
    }

    private function parseFileTable10x($tableData)
    {

        throw new \RuntimeException(
            "The 0x10x versions are not supported right now"
        );
    }

    private function registerFileInFileTree(array &$tree, File $file)
    {

        if ($file->isDirectory())
            return;

        $parts = explode('\\', trim($file->getPath(), '\\'));
        $last = count($parts) - 1;

        $baseName = $parts[$last];
        unset($parts[$last]);

        $current = &$tree;
        foreach ($parts as $part) {

            if (!isset($current[$part]))
                $current[$part] = [];

            $current = &$current[$part];
        }

        $current[$baseName] = $file;
    }

    public function buildFileTree()
    {

        $tree = [];
        foreach ($this->files as $file)
            $this->registerFileInFileTree($tree, $file);

        return $tree;
    }

    public function __debugInfo()
    {

        return [
            'header' => $this->header,
            'files' => $this->files
        ];
    }
}