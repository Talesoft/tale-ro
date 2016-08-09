<?php

namespace Tale\Ro\Grf;

use Tale\Ro\Grf;
use Tale\Ro\Grf\File\Info;

class File
{

    private $archive;
    private $path;
    private $info;
    private $flags;
    private $content;

    /**
     * File constructor.
     * @param Archive $archive
     * @param $path
     * @param null $content
     * @param $flags
     * @param Info $info
     */
    public function __construct(
        Archive $archive,
        $path,
        Info $info = null,
        $flags = null,
        $content = null
    )
    {

        $this->archive = $archive;
        $this->path = $path;
        $this->info = $info;
        $this->flags = $flags ?: 0;
        $this->content = $content;
    }

    /**
     * @return Archive
     */
    public function getArchive()
    {

        return $this->archive;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {

        return $this->path;
    }

    /**
     * @return Info
     */
    public function getInfo()
    {

        return $this->info;
    }

    /**
     * @return mixed
     */
    public function getFlags()
    {

        return $this->flags;
    }

    public function isDirectory()
    {

        return !($this->flags & Grf::FILE_TYPE_FILE);
    }

    public function invalidate()
    {

        $this->info = null;

        return $this;
    }

    public function isValid()
    {

        return $this->info !== null;
    }

    /**
     * @param resource $filePointer
     * @return string
     */
    public function getContent($filePointer = null)
    {

        //If custom content is set, we return that one
        if ($this->content !== null)
            return $this->content;

        if ($filePointer && !is_resource($filePointer))
            throw new \InvalidArgumentException(
                "The passed argument to File->getContent needs to be valid file pointer"
            );

        
        //Return a \n separated directory listing if is directory
        if ($this->isDirectory())
            return $this->getDirectoryListing();

        //It's a new file, but no content was given. Give it back as simply empty content.
        if (!$this->isValid())
            return '';

        //No data, no need to parse anything
        if ($this->info->getSize() < 1)
            return '';

        if (($this->flags & Grf::FILE_TYPE_ENCRYPTED_DES) || ($this->flags & Grf::FILE_TYPE_ENCRYPTED_MIXED))
            throw new \RuntimeException(
                "Failed to read GRF file [$this->path]: Encrypted files are not supported right now"
            );

        //Calculate total offset of the file content
        $offset = $this->info->getOffset() + Grf::HEADER_SIZE;

        $archivePath = $this->archive->getPath();

        //Make sure this is actually coming from the GRF this file belongs to
        if ($filePointer) {

            //Make sure the files match
            $archivePath = realpath($archivePath);
            $path = realpath(stream_get_meta_data($filePointer)['uri']);

            if ($archivePath !== $path)
                throw new \RuntimeException(
                    "The file pointer you passed is not the same file as the source GRF file (got $path, expected $archivePath)"
                );
        }

        //Check if our offset is in the range of our file size
        if (filesize($archivePath) < $offset + 1)
            throw new \RuntimeException(
                "Passed GRF $archivePath's file [$this->path] contains invalid offsets"
            );


        $fp = $filePointer ?: fopen($archivePath, 'rb');

        //Jump to file data offset
        fseek($fp, $offset);

        //FOR DEBUGGING PURPOSES
        //echo "F: {$this->path}, ZS: {$this->archiveInfo->getCompressedSize()}, AS: {$this->archiveInfo->getAlignedSize()}, S: {$this->archiveInfo->getSize()}, O: {$offset}\n";

        //Read the whole file content chunk
        $compressedContent = fread($fp, $this->info->getAlignedSize());

        //FOR DEBUGGING PURPOSES
        //echo "\n\n".implode(' ', array_map(function($c) {

        //    return str_pad(dechex(ord($c)), 2, '0', STR_PAD_LEFT);
        //}, str_split($compressedContent)))."\n\n";

        //Decompress (Not using the second parameter, it leads to "insufficient memory" errors :()
        $content = @zlib_decode($compressedContent);

        if ($content === false)
            throw new \RuntimeException(
                "Failed to decompress $archivePath's file [$this->path]"
            );

        if (strlen($content) !== $this->info->getSize())
            throw new \RuntimeException(
                "Passed GRF $archivePath's file [$this->path] seems to be corrupted. Maybe the GRF is encrypted in ".
                "some mysterious way."
            );

        if (!$filePointer)
            fclose($fp);

        if ($this->archive->getHeader()->getVersion() >= Grf::VERSION_200)
            $this->content = $content;
        else
            $this->content = $this->parseFileContent10x($compressedContent);

        return $this->content;
    }

    public function getTextContent($filePointer = null)
    {

        return mb_convert_encoding($this->getContent($filePointer), 'UTF-8', 'EUC-KR');
    }

    public function setContent($content)
    {

        $this->content = $content;
        $this->invalidate();

        return $this;
    }

    public function setTextContent($content)
    {

        $this->setContent(mb_convert_encoding($content, 'EUC-KR', 'UTF-8'));

        return $this;
    }

    private function getDirectoryListing()
    {

        return implode("\n", array_keys($this->archive->getFilesIn($this->path)));
    }

    private function parseFileContent10x($compressedContent)
    {

        throw new \RuntimeException(
            "version 10x not supported right now"
        );
    }

    public function __toString()
    {

        return $this->path;
    }

    public function __debugInfo()
    {

        return [
            'path' => $this->path,
            'valid' => $this->isValid() ? 'Yes' : 'No',
            'directory' => $this->isDirectory() ? 'Yes' : 'No',
            'info' => $this->info
        ];
    }
}