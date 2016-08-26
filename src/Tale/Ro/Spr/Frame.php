<?php

namespace Tale\Ro\Spr;

use Phim\Color;
use Phim\Color\Palette;
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

        if ($this->type !== Spr::FRAME_TYPE_INDEXED_RLE)
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

    public function getGdImage($withAlpha = true, $backgroundColor = null)
    {

        if ($this->type === Spr::FRAME_TYPE_RGBA)
            return $this->getGdRgbaImage($withAlpha, $backgroundColor);

        $im = imagecreate($this->width, $this->height);
        $data = $this->readFrameData();
        $data = array_values(unpack('C'.strlen($data), $data));

        if ($withAlpha) {

            imagealphablending($im, false);
            imagesavealpha($im, true);
        }

        $palette = $this->spr->getPalette();

        $pal = [];
        foreach ($palette as $color) {

            //Create alpha-supporting image using the first index of the palette as the alpha color
            if ($withAlpha && empty($pal)) {

                $pal[] = imagecolorallocatealpha($im, $color->getRed(), $color->getGreen(), $color->getBlue(), 127);
                continue;
            } else if ($backgroundColor && empty($pal)) {

                $bgColor = Color::get($backgroundColor)->getRgb();
                $pal[] = imagecolorallocate($im, $bgColor->getRed(), $bgColor->getGreen(), $bgColor->getBlue());
                continue;
            }

            $pal[] = imagecolorallocate($im, $color->getRed(), $color->getGreen(), $color->getBlue());
        }

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {

                imagesetpixel($im, $x, $y, $pal[$data[$y * $this->width + $x]]);
            }
        }

        return $im;
    }

    private function getGdRgbaImage($withAlpha = null, $backgroundColor = null)
    {

        $im = imagecreatetruecolor($this->width, $this->height);
        $data = $this->readFrameData();
        $data = array_values(unpack('C'.strlen($data), $data));

        if ($withAlpha) {

            imagealphablending($im, false);
            imagesavealpha($im, true);
        } else {

            $backgroundColor = $backgroundColor ?: 'white';

            imagealphablending($im, true);
            imagesavealpha($im, false);

            $bgColor = Color::get($backgroundColor)->getRgba();
            $bgIndex = imagecolorallocatealpha($im, $bgColor->getRed(), $bgColor->getGreen(), $bgColor->getBlue(), 127 - ($bgColor->getAlpha() * 127));
            imagefill($im, 0, 0, $bgIndex);
        }

        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {

                $i = ($y * $this->width + $x) * 4;
                imagesetpixel($im, $x, $y, $this->getGdColor(
                    $im,
                    $data[$i + 3],
                    $data[$i + 2],
                    $data[$i + 1],
                    127 - ($data[$i] / 2)
                ));
            }
        }

        return $im;
    }

    public function getDataUri()
    {

        ob_start();
        imagepng($this->getGdImage());
        return 'data://image/png;base64,'.base64_encode(ob_get_clean());
    }
}