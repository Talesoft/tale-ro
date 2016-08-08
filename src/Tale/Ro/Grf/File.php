<?php

namespace Tale\Ro\Grf;

use Tale\Ro\Grf;
use Tale\Ro\Grf\File\ArchiveInfo;

class File
{

    private $archive;
    private $path;
    private $archiveInfo;
    private $flags;
    private $content;

    /**
     * File constructor.
     * @param Archive $archive
     * @param $path
     * @param null $content
     * @param $flags
     * @param ArchiveInfo $archiveInfo
     */
    public function __construct(
        Archive $archive,
        $path,
        ArchiveInfo $archiveInfo = null,
        $flags = null,
        $content = null
    )
    {

        $this->archive = $archive;
        $this->path = $path;
        $this->archiveInfo = $archiveInfo;
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
     * @return ArchiveInfo
     */
    public function getArchiveInfo()
    {

        return $this->archiveInfo;
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

        $this->archiveInfo = null;

        return $this;
    }

    public function isValid()
    {

        return $this->archiveInfo !== null;
    }

    /**
     * @return string
     */
    public function getContent()
    {

        if ($this->content !== null)
            return $this->content;

        //It's a new file, but no content was given. Give it back as simply empty content.
        if (!$this->isValid())
            return '';

        echo "DIR: ".$this->isDirectory()."\n";
        //Return a \n separated directory listing if is directory
        if ($this->isDirectory())
            return $this->getDirectoryListing();

        //No data, no need to parse anything
        if ($this->archiveInfo->getSize() < 1)
            return '';

        //Calculate total offset of the file content
        $offset = $this->archiveInfo->getOffset() + Grf::HEADER_SIZE;

        $archivePath = $this->archive->getPath();
        if (filesize($archivePath) < $offset + 1)
            throw new \RuntimeException(
                "Passed GRF $archivePath's file [$this->path] contains invalid offsets"
            );

        $fp = fopen($archivePath, 'rb');

        //Jump to file data offset
        fseek($fp, $offset);

        var_dump($this->archiveInfo, $offset);
        //Read the whole file content chunk
        $compressedContent = fread($fp, $this->archiveInfo->getAlignedSize());
        $content = zlib_decode($compressedContent, $this->archiveInfo->getCompressedSize());
        var_dump($compressedContent);

        var_dump(strlen($content).' <> '.$this->archiveInfo->getSize());
        if (strlen($content) !== $this->archiveInfo->getSize())
            throw new \RuntimeException(
                "Passed GRF $archivePath's file [$this->path] seems to be corrupted"
            );

        fclose($fp);

        if ($this->archive->getHeader()->getVersion() >= Grf::VERSION_200)
            $this->content = $content;
        else
            $this->content = $this->parseFileContent10x($compressedContent);

        return $this->content;
    }

    public function setContent($content)
    {

        $this->content = $content;
        $this->invalidate();

        return $this;
    }

    private function getDirectoryListing()
    {

        $len = strlen($this->path);

        $listing = [];
        foreach ($this->archive->getFiles() as $path => $info) {

            if (strncmp($path, $this->path, $len) === 0)
                $listing[] = $path;
        }

        return implode("\n", $listing);
    }

    private function parseFileContent10x($compressedContent)
    {

        throw new \RuntimeException(
            "version 10x not supported right now"
        );
    }

    function __toString()
    {

        return $this->path;
    }
}