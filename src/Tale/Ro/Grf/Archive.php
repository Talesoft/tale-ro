<?php

namespace Tale\Ro\Grf;

use Tale\Ro\Grf;
use Tale\Ro\Grf\File\ArchiveInfo;

//https://sourceforge.net/p/openkore/code/HEAD/tree/grftool/trunk/lib/grf.c#l192
//http://www.vbforums.com/showthread.php?584540-Reading-GRF-files
class Archive
{
    
    private $path;
    private $header;
    private $files;
    
    public function __construct($path)
    {

        $this->path = $path;
        $this->header = $this->readHeader();
        $this->files = $this->readFileTable();
    }

    /**
     * @return string
     */
    public function getPath()
    {

        return $this->path;
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
     * @return File[]
     */
    public function searchFiles($pattern)
    {

        $files = [];
        foreach ($this->files as $path => $info)
            if (preg_match($pattern, $path))
                $files[$path] = $info;

        return $files;
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
        
        if (!file_exists($this->path))
            return $this->createEmptyHeader();

        if (filesize($this->path) < Grf::HEADER_SIZE)
            throw new \RuntimeException(
                "Passed GRF file $this->path is not empty, but not a valid GRF file"
            );

        $fp = fopen($this->path, 'rb');

        //Read and check magic header
        $magicHeader = fread($fp, 15);
        if ($magicHeader !== Grf::MAGIC_HEADER)
            throw new \RuntimeException(
                "Passed GRF file $this->path is not a valid GRF file (Magic header mismatch)"
            );
        
        //Read encryption watermark
        $encryptionWatermark = array_map('ord', str_split(fread($fp, 15)));
        $fileTableOffset = $this->readUInt32($fp);
        $seed = $this->readUInt32($fp);
        $fileCount = $this->readUInt32($fp);
        $version = $this->readUInt32($fp);
        fclose($fp);

        return new Header($magicHeader, $encryptionWatermark, $fileTableOffset, $seed, $fileCount, $version);
    }

    private function readFileTable()
    {

        //No files, no need to parse any table
        if ($this->header->getRealFileCount() < 1)
            return [];

        //Calculate total offset of the file table
        $offset = $this->header->getFileTableOffset() + Grf::HEADER_SIZE;

        if (filesize($this->path) < $offset + 8 + 1)
            //Notice, after the offset (which is also the size until there) there should be two further ints (8 bytes)
            //for the size and compressed size. If thats not the case, kill.
            throw new \RuntimeException(
                "Passed GRF $this->path contains invalid file table offsets"
            );


        $fp = fopen($this->path, 'rb');

        //Jump to file table offset
        fseek($fp, $offset);

        //Get file table sizes
        $compressedSize = $this->readUInt32($fp);
        $size = $this->readUInt32($fp);

        //Read the compressed file table
        $compressedTableData = fread($fp, $compressedSize);

        fclose($fp);

        //Decompress table
        $tableData = zlib_decode($compressedTableData, $size);

        //Validate
        if (strlen($tableData) !== $size)
            throw new \RuntimeException(
                "Passed GRF $this->path contains a corrupt file table: File table size was ".strlen($tableData).", but $size was expected"
            );

        //Parse based on GRF version
        if ($this->header->getVersion() >= Grf::VERSION_200)
            return $this->parseFileTable200($tableData);

        return $this->parseFileTable10x($tableData);
    }

    private function parseFileTable200($tableData)
    {

        $files = [];
        for ($i = 0, $o = 0; $i < $this->header->getRealFileCount(); $i++) {

            //read filename until 0x00
            $path = '';
            $null = chr(0x00);
            while (($char = substr($tableData, $o, 1)) !== $null) {

                $path .= $char;
                $o++;
            }
            $o++;

            $data = unpack('V1compressedSize/V1alignedSize/V1size/C1flags/V1offset', substr($tableData, $o, 17));
            $o += 17;

            $archiveInfo = new ArchiveInfo(
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

    private function readBinaryValue($fp, $format, $readLength)
    {

        if (feof($fp))
            throw new \RuntimeException(
                "Failed to read $format from $this->path: No more bytes to read"
            );

        return unpack($format, fread($fp, $readLength))[1];
    }

    private function readUInt32($fp)
    {

        return $this->readBinaryValue($fp, 'V', 4);
    }
}