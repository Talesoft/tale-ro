<?php

namespace Tale\Ro\Grf;

use Tale\Ro\Grf;

//https://sourceforge.net/p/openkore/code/HEAD/tree/grftool/trunk/lib/grf.c#l192
//http://www.vbforums.com/showthread.php?584540-Reading-GRF-files
class Archive
{
    
    private $path;
    private $handle;
    private $header;
    private $files;
    
    public function __construct($path)
    {

        $this->path = $path;
        $this->handle = fopen($path, 'rb');
        $this->header = $this->readHeader();
        $this->files = $this->readFileTable();
    }

    public function __destruct()
    {

        if (is_resource($this->handle))
            fclose($this->handle);
    }

    private function readHeader()
    {

        //Read and check magic header
        $magicHeader = fread($this->handle, 15);
        if ($magicHeader !== Grf::MAGIC_HEADER)
            throw new \RuntimeException(
                "Passed GRF file $this->path is not a valid GRF file (Magic header mismatch)"
            );
        
        //Read encryption watermark
        $encryptionWatermark = array_map('ord', str_split(fread($this->handle, 15)));
        $fileTableOffset = $this->readUInt32();
        $seed = $this->readUInt32();
        $fileCount = $this->readUInt32();
        $version = $this->readUInt32();

        return new Header($magicHeader, $encryptionWatermark, $fileTableOffset, $seed, $fileCount, $version);
    }

    private function readFileTable()
    {

        //Jump to file table offset
        fseek($this->handle, $this->header->getFileTableOffset() + 46);

        if ($this->header->getVersion() >= Grf::VERSION_200)
            return $this->read200FileTable();

        return $this->read10xFileTable();
    }

    private function read200FileTable()
    {

        //Get file table sizes
        $compressedSize = $this->readUInt32();
        $size = $this->readUInt32();

        //Read the compressed file table
        $compressedTable = fread($this->handle, $compressedSize);

        $table = zlib_decode($compressedTable, $size);

        if (strlen($table) !== $size)
            throw new \RuntimeException(
                "File table size was ".strlen($table).", but $size was expected"
            );

        $files = [];
        for ($i = 0, $o = 0; $i < $this->header->getRealFileCount(); $i++) {

            //There's the file size at the first 4 bytes
            $len = unpack('V', substr($table, $o, 4))[1];
            $o += 4;

            //read filename until 0x00
            $fileName = '';
            while (($char = substr($table, $o, 1)) !== chr(0x00)) {

                $fileName .= $char;
                $o++;
            }
            $o++;

            $compressedSize = unpack('V', substr($table, $o, 4))[1];
            $o += 4;
            $alignedSize = unpack('V', substr($table, $o, 4))[1];
            $o += 4;
            $size = unpack('V', substr($table, $o, 4))[1];

            $flags = unpack('C', substr($table, $o, 1))[1];
            $o += 1;
            $position = unpack('V', substr($table, $o, 4));
            $o += 4;

            $files[$fileName] = new FileInfo($compressedSize, $alignedSize, $size, $flags, $position);

            echo "$fileName (".strlen($fileName).":$len)\n";
        }

        return $files;
    }

    private function read10xFileTable()
    {

        return [];
    }

    private function readBinaryValue($format, $readLength)
    {

        if (feof($this->handle))
            throw new \RuntimeException(
                "Failed to read $format from $this->path: No more bytes to read"
            );

        return unpack($format, fread($this->handle, $readLength))[1];
    }

    private function readUInt8()
    {

        return $this->readBinaryValue('C', 1);
    }

    private function readUInt16()
    {

        return $this->readBinaryValue('S', 2);
    }

    private function readUInt32()
    {

        return $this->readBinaryValue('V', 4);
    }
}