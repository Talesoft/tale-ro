<?php

namespace Tale\Ro\Grf;

use Tale\Ro\Act;
use Tale\Ro\Act\Action;
use Tale\Ro\Grf;
use Tale\Ro\Grf\File\Info;
use Tale\Ro\Spr;

class File
{

    private $grf;
    private $path;
    private $info;
    private $flags;
    private $content;

    /**
     * File constructor.
     * @param Grf $grf
     * @param $path
     * @param Info $info
     * @param $flags
     * @param null $content
     */
    public function __construct(
        Grf $grf,
        $path,
        Info $info = null,
        $flags = null,
        $content = null
    )
    {

        $this->grf = $grf;
        $this->path = $path;
        $this->info = $info;
        $this->flags = $flags ?: 0;
        $this->content = null;

        if ($content) {

            if (!is_resource($content))
                throw new \InvalidArgumentException(
                    "Passed content needs to be valid resource"
                );

            $this->content = $content;
        }
    }

    public function __destruct()
    {

        if (is_resource($this->content))
            fclose($this->content);
    }

    /**
     * @return Grf
     */
    public function getGrf()
    {

        return $this->grf;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {

        return $this->path;
    }

    public function getExtension()
    {

        $pos = mb_strrpos($this->path, '.');

        if ($pos === false)
            return '';

        return mb_substr($this->path, $pos + 1);
    }

    public function isExtension($ext)
    {

        return strtolower($ext) === strtolower($this->getExtension());
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
     * @return resource
     */
    public function getContentHandle()
    {

        //If custom content is set, we return that one
        if ($this->content !== null)
            return $this->content;

        //Return a \n separated directory listing if it's a directory
        if ($this->isDirectory()) {

            $this->content = fopen('data://text/plain;base64,'.base64_encode($this->getDirectoryListing()), 'r');
            return $this->content;
        }

        //It's a new file, but no content was given. Give it back as simply empty content.
        if (!$this->isValid())
            return '';

        //No data, no need to parse anything
        if ($this->info->getSize() < 1)
            return '';

        if (($this->flags & Grf::FILE_TYPE_ENCRYPTED_DES) || ($this->flags & Grf::FILE_TYPE_ENCRYPTED_MIXED))
            throw new \RuntimeException(
                "Failed to read GRF file [$this->path]: Encrypted files are not supported right now. Catch this and skip the file."
            );

        //Calculate total offset of the file content
        $offset = $this->info->getOffset() + Grf::HEADER_SIZE;

        //Check if our offset is in the range of our file size
        if ($this->grf->getSize() < $offset + 1)
            throw new \RuntimeException(
                "Passed GRF file [$this->path] contains invalid offsets"
            );


        $fp = $this->grf->getHandle();

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
            throw new \RuntimeException("Failed to decompress file [$this->path]");

        if (strlen($content) !== $this->info->getSize())
            throw new \RuntimeException(
                "Passed GRF file [$this->path] seems to be corrupted. Maybe the GRF is encrypted in ".
                "some mysterious way."
            );

        if ($this->grf->getHeader()->getVersion() < Grf::VERSION_200)
            $content = $this->parseFileContent10x($content);

        $this->content = fopen('data://text/plain;base64,'.base64_encode($content), 'rb');

        return $this->content;
    }

    public function setContentHandle($content)
    {

        if (!is_resource($content))
            throw new \InvalidArgumentException(
                "Passed argument to setContent needs to be valid resource"
            );

        $this->content = $content;
        $this->invalidate();

        return $this;
    }
    
    public function getTextContent($encoding = false)
    {

        $encoding = $encoding ?: Grf::ENCODING_EUC_KR;

        $content = stream_get_contents($this->getContentHandle());

        if ($encoding !== Grf::ENCODING_UTF8)
            $content = mb_convert_encoding($content, Grf::ENCODING_UTF8, $encoding);

        return $content;
    }
    
    public function setTextContent($content)
    {
        
        $content = mb_convert_encoding($content, mb_detect_encoding($content,
            implode(',', [Grf::ENCODING_EUC_KR, Grf::ENCODING_ASCII, Grf::ENCODING_ASCII])
        ), 'UTF-8');

        $this->setContentHandle(fopen('data://text/plain;base64,'.base64_encode($content), 'rb'));

        return $this;
    }

    public function isSpr()
    {

        return $this->isExtension('spr');
    }

    /**
     * @return Spr
     */
    public function getAsSpr()
    {

        return new Spr($this->getContentHandle());
    }

    /**
     * @return Act
     */
    public function getAsAct()
    {

        return new Act($this->getContentHandle());
    }

    private function getDirectoryListing()
    {

        return implode("\n", array_keys($this->grf->getFilesIn($this->path)));
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