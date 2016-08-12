<?php

namespace Tale\Ro\Spr;

use Tale\Ro\Spr;

class Frame
{
    
    private $spr;
    private $width;
    private $height;
    private $type;
    private $offset;
    private $size;

    /**
     * Frame constructor.
     * @param Spr $spr
     * @param $width
     * @param $height
     * @param $type
     * @param $offset
     */
    public function __construct(Spr $spr, $width, $height, $type, $offset, $size)
    {

        $this->width = $width;
        $this->height = $height;
        $this->spr = $spr;
        $this->type = $type;
        $this->offset = $offset;
        $this->size = $size;
    }
    
    /**
     * @return Spr
     */
    public function getSpr()
    {

        return $this->spr;
    }

    /**
     * @return int
     */
    public function getWidth()
    {

        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {

        return $this->height;
    }

    /**
     * @return int
     */
    public function getType()
    {

        return $this->type;
    }

    /**
     * @return int
     */
    public function getOffset()
    {

        return $this->offset;
    }

    /**
     * @return int
     */
    public function getSize()
    {

        return $this->size;
    }

    public function readFrameData()
    {

        fseek($this->spr->getHandle(), $this->offset);
        $data = fread($this->spr->getHandle(), $this->size);

        if ($this->type === Spr::FRAME_TYPE_RGBA)
            return $data;

        //Decode RLE
        $decodedData = '';
        for ($i = 0; $i < $this->size; $i++) {

            $c = $data[$i];
            $decodedData .= $c;

            if (ord($c) === 0x00) {

                $count = $data[++$i];

                if (ord($count) === 0x00)
                    $decodedData .= $count;
                else
                    $decodedData .= str_repeat($c, ord($count) - 1);

            }
        }

        return $decodedData;
    }

    private function getGdColor($im, $r, $g, $b, $a)
    {

        $color = imagecolorexactalpha($im, $r, $g, $b, $a);

        if ($color !== -1)
            return $color;

        return imagecolorallocatealpha($im, $r, $g, $b, $a);
    }

    public function getGdImage()
    {

        if ($this->type === Spr::FRAME_TYPE_RGBA)
            return $this->getGdRgbaImage();

        $im = imagecreate($this->width, $this->height);
        $data = $this->readFrameData();
        $data = array_values(unpack('C'.strlen($data), $data));

        //Build palette cleanly
        $pal = [];
        $palette = $this->spr->getPalette();
        for ($i = 0; $i < 256; $i += 4) {

            $pal[] = imagecolorallocate(
                $im,
                $palette[$i],
                $palette[$i + 1],
                $palette[$i + 2]
            );
        }


        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {

                imagesetpixel($im, $x, $y, $palette[
                    ord(
                        $data[$y * $this->width + $x]
                    )
                ]);
            }
        }

        return $im;
    }

    private function getGdRgbaImage()
    {

        $im = imagecreatetruecolor($this->width, $this->height);
        $data = $this->readFrameData();
        $data = array_values(unpack('C'.strlen($data), $data));

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {

                $i = $y * $this->width + $x;
                imagesetpixel($im, $x, $y, $this->getGdColor(
                    $im,
                    $data[$i],
                    $data[$i + 1],
                    $data[$i + 2],
                    127 - $data[$i + 3]
                ));
            }
        }

        return $im;
    }
}