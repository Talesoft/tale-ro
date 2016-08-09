<?php

namespace Tale\Ro\Grf\File;

class Info
{

    private $compressedSize;
    private $alignedSize;
    private $size;
    private $offset;

    /**
     * ArchiveMeta constructor.
     * @param $compressedSize
     * @param $alignedSize
     * @param $size
     * @param $offset
     */
    public function __construct($compressedSize, $alignedSize, $size, $offset)
    {

        $this->compressedSize = $compressedSize;
        $this->alignedSize = $alignedSize;
        $this->size = $size;
        $this->offset = $offset;
    }

    /**
     * @return mixed
     */
    public function getCompressedSize()
    {

        return $this->compressedSize;
    }

    /**
     * @return mixed
     */
    public function getAlignedSize()
    {

        return $this->alignedSize;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {

        return $this->size;
    }

    /**
     * @return mixed
     */
    public function getOffset()
    {

        return $this->offset;
    }
}