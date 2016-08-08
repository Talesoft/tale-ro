<?php

namespace Tale\Ro\Grf;

class FileInfo
{

    private $compressedSize;
    private $alignedSize;
    private $size;
    private $flags;
    private $position;

    /**
     * File constructor.
     * @param $compressedSize
     * @param $alignedSize
     * @param $size
     * @param $flags
     * @param $position
     */
    public function __construct($compressedSize, $alignedSize, $size, $flags, $position)
    {

        $this->compressedSize = $compressedSize;
        $this->alignedSize = $alignedSize;
        $this->size = $size;
        $this->flags = $flags;
        $this->position = $position;
    }

    /**
     * @return mixed
     */
    public function getCompressedSize()
    {

        return $this->compressedSize;
    }

    /**
     * @param mixed $compressedSize
     * @return File
     */
    public function setCompressedSize($compressedSize)
    {

        $this->compressedSize = $compressedSize;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAlignedSize()
    {

        return $this->alignedSize;
    }

    /**
     * @param mixed $alignedSize
     * @return File
     */
    public function setAlignedSize($alignedSize)
    {

        $this->alignedSize = $alignedSize;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {

        return $this->size;
    }

    /**
     * @param mixed $size
     * @return File
     */
    public function setSize($size)
    {

        $this->size = $size;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFlags()
    {

        return $this->flags;
    }

    /**
     * @param mixed $flags
     * @return File
     */
    public function setFlags($flags)
    {

        $this->flags = $flags;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {

        return $this->position;
    }

    /**
     * @param mixed $position
     * @return File
     */
    public function setPosition($position)
    {

        $this->position = $position;

        return $this;
    }
}