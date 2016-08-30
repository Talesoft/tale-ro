<?php

namespace Tale\Ro;

//https://github.com/vthibault/ROChargenPHP/blob/master/loaders/class.Sprite.php
use Phim\Color\Palette\SimplePalette;
use Phim\Color\PaletteInterface;
use Phim\Color\RgbaColor;
use Phim\ColorInterface;
use Tale\Ro\Spr\Frame;

class Spr extends AbstractFormat
{

    const MAGIC_HEADER = 'SP';
    const MAGIC_HEADER_SIZE = 2;

    const FRAME_TYPE_INDEXED = 0;
    const FRAME_TYPE_INDEXED_RLE = 1;
    const FRAME_TYPE_RGBA = 2;

    const FLIP_HORIZONTAL = \IMG_FLIP_HORIZONTAL;
    const FLIP_VERTICAL = \IMG_FLIP_VERTICAL;
    const FLIP_BOTH = \IMG_FLIP_BOTH;
    
    private $magicHeader;
    private $version;
    private $indexedFrameCount;
    private $rgbaFrameCount;

    /**
     * @var Frame[]
     */
    private $frames;
    private $palette;
    
    public function __construct($handle = null)
    {
        parent::__construct($handle);

        $this->magicHeader = null;
        $this->version = null;
        $this->indexedFrameCount = 0;
        $this->rgbaFrameCount = 0;
        $this->frames = [];
        $this->palette = [];

        $this->read();
    }

    /**
     * @return null
     */
    public function getMagicHeader()
    {

        return $this->magicHeader;
    }

    /**
     * @return null
     */
    public function getVersion()
    {

        return $this->version;
    }

    /**
     * @return int
     */
    public function getIndexedFrameCount()
    {

        return $this->indexedFrameCount;
    }

    /**
     * @return int
     */
    public function getRgbaFrameCount()
    {

        return $this->rgbaFrameCount;
    }

    /**
     * @return Frame[]
     */
    public function getFrames()
    {

        return $this->frames;
    }

    public function hasFrame($index)
    {

        return isset($this->frames[$index]);
    }

    public function getFrame($index)
    {

        return $this->frames[$index];
    }

    /**
     * @return PaletteInterface|RgbaColor[]
     */
    public function getPalette()
    {

        return $this->palette;
    }

    private function read()
    {

        if ($this->getSize() === 0) {

            //Fill with default data and return. Empty sprite.
            $this->magicHeader = self::MAGIC_HEADER;
            $this->version = 2.1;
            return;
        }

        if ($this->getSize() < 6)
            throw new \RuntimeException(
                "Failed to read sprite: Header needs to have at least 6 bytes"
            );

        fseek($this->getHandle(), 0);

        $header = unpack('A2magicHeader/CminorVersion/CmajorVersion/vindexedFrameCount', fread($this->getHandle(), 6));

        if ($header['magicHeader'] !== self::MAGIC_HEADER)
            throw new \RuntimeException(
                "Failed to read sprite: Magic header mismatch"
            );

        $this->magicHeader = $header['magicHeader'];
        $this->version = $header['minorVersion'] / 10 + $header['majorVersion'];
        $this->indexedFrameCount = $header['indexedFrameCount'];

        if ($this->version > 1.1)
            $this->rgbaFrameCount = unpack('v', fread($this->getHandle(), 2))[1];

        if ($this->version < 2.1)
            $this->readIndexedFrames();
        else
            $this->readIndexedFramesRle();

        $this->readRgbaFrames();

        if ($this->version > 1.0) {

            $palSize = 1024;
            fseek($this->getHandle(), -$palSize, SEEK_END);
            $data = array_values(unpack('C1024', fread($this->getHandle(), $palSize)));

            $pal = [];
            for ($i = 0; $i < $palSize; $i += 4) {

                $pal[] = new RgbaColor($data[$i], $data[$i + 1], $data[$i + 2], (255 - $data[$i + 3]) / 255);
            }

            $this->palette = new SimplePalette($pal, $palSize);
        }
    }

    private function readIndexedFrames()
    {

        for ($i = 0; $i < $this->indexedFrameCount; $i++) {

            $info = unpack('vwidth/vheight', fread($this->getHandle(), 4));

            $this->frames[] = new Frame(
                $this,
                $info['width'],
                $info['height'],
                self::FRAME_TYPE_INDEXED,
                ftell($this->getHandle()),
                $info['width'] * $info['height']
            );

            fseek($this->getHandle(), $info['width'] * $info['height'], SEEK_CUR);
        }
    }

    private function readIndexedFramesRle()
    {

        for ($i = 0; $i < $this->indexedFrameCount; $i++) {

            $info = unpack('vwidth/vheight/vsize', fread($this->getHandle(), 6));

            $this->frames[] = new Frame(
                $this,
                $info['width'],
                $info['height'],
                self::FRAME_TYPE_INDEXED_RLE,
                ftell($this->getHandle()),
                $info['size']
            );

            fseek($this->getHandle(), $info['size'], SEEK_CUR);
        }
    }

    private function readRgbaFrames()
    {

        for ($i = 0; $i < $this->rgbaFrameCount; $i++) {

            $info = unpack('vwidth/vheight', fread($this->getHandle(), 4));

            $this->frames[] = new Frame(
                $this,
                $info['width'],
                $info['height'],
                self::FRAME_TYPE_RGBA,
                ftell($this->getHandle()),
                $info['width'] * $info['height'] * 4
            );

            fseek($this->getHandle(), $info['width'] * $info['height'] * 4, SEEK_CUR);
        }
    }

    public function __debugInfo()
    {

        return [
            'magicHeader' => $this->magicHeader,
            'version' => $this->version,
            'frames' => $this->frames,
            'palette' => $this->palette
        ];
    }
}