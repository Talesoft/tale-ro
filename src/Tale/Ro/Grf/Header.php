<?php

namespace Tale\Ro\Grf;


class Header
{

    private $magicHeader;
    private $encryptionWatermark;
    private $fileTableOffset;
    private $seed;
    private $fileCount;
    private $version;
    
    public function __construct(
        $magicHeader,
        array $encryptionWatermark,
        $fileTableOffset,
        $seed,
        $fileCount,
        $version
    )
    {

        $this->magicHeader = $magicHeader;
        $this->encryptionWatermark = $encryptionWatermark;
        $this->fileTableOffset = $fileTableOffset;
        $this->seed = $seed;
        $this->fileCount = $fileCount;
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getMagicHeader()
    {

        return $this->magicHeader;
    }

    /**
     * @param string $magicHeader
     * @return $this
     */
    public function setMagicHeader($magicHeader)
    {

        $this->magicHeader = $magicHeader;

        return $this;
    }

    /**
     * @return array
     */
    public function getEncryptionWatermark()
    {

        return $this->encryptionWatermark;
    }

    /**
     * @param array $encryptionWatermark
     * @return $this
     */
    public function setEncryptionWatermark($encryptionWatermark)
    {

        $this->encryptionWatermark = $encryptionWatermark;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFileTableOffset()
    {

        return $this->fileTableOffset;
    }

    /**
     * @param mixed $fileTableOffset
     * @return $this
     */
    public function setFileTableOffset($fileTableOffset)
    {

        $this->fileTableOffset = $fileTableOffset;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSeed()
    {

        return $this->seed;
    }

    /**
     * @param mixed $seed
     * @return $this
     */
    public function setSeed($seed)
    {

        $this->seed = $seed;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFileCount()
    {

        return $this->fileCount;
    }

    /**
     * @param mixed $fileCount
     * @return $this
     */
    public function setFileCount($fileCount)
    {

        $this->fileCount = $fileCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {

        return $this->version;
    }

    /**
     * @param mixed $version
     * @return $this
     */
    public function setVersion($version)
    {

        $this->version = $version;

        return $this;
    }
}