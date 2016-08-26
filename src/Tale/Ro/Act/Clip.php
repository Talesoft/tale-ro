<?php

namespace Tale\Ro\Act;

class Clip
{

    private $x;
    private $y;
    private $spriteIndex;
    private $flags;
    private $r;
    private $g;
    private $b;
    private $a;
    private $zoomX;
    private $zoomY;
    private $angle;
    private $type;
    private $width;
    private $height;

    /**
     * Clip constructor.
     *
     * @param $x
     * @param $y
     * @param $spriteIndex
     * @param $flags
     * @param $r
     * @param $g
     * @param $b
     * @param $a
     * @param $zoomX
     * @param $zoomY
     * @param $angle
     * @param $type
     */
    public function __construct($x, $y, $spriteIndex, $flags, $r, $g, $b, $a, $zoomX, $zoomY, $angle, $type, $width = null, $height = null)
    {

        $this->x = $x;
        $this->y = $y;
        $this->spriteIndex = $spriteIndex;
        $this->flags = $flags;
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
        $this->a = $a;
        $this->zoomX = $zoomX;
        $this->zoomY = $zoomY;
        $this->angle = $angle;
        $this->type = $type;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * @return mixed
     */
    public function getX()
    {

        return $this->x;
    }

    /**
     * @return mixed
     */
    public function getY()
    {

        return $this->y;
    }

    /**
     * @return mixed
     */
    public function getSpriteIndex()
    {

        return $this->spriteIndex;
    }

    /**
     * @return mixed
     */
    public function getFlags()
    {

        return $this->flags;
    }

    /**
     * @return mixed
     */
    public function getR()
    {

        return $this->r;
    }

    /**
     * @return mixed
     */
    public function getG()
    {

        return $this->g;
    }

    /**
     * @return mixed
     */
    public function getB()
    {

        return $this->b;
    }

    /**
     * @return mixed
     */
    public function getA()
    {

        return $this->a;
    }

    /**
     * @return mixed
     */
    public function getZoomX()
    {

        return $this->zoomX;
    }

    /**
     * @return mixed
     */
    public function getZoomY()
    {

        return $this->zoomY;
    }

    /**
     * @return mixed
     */
    public function getAngle()
    {

        return $this->angle;
    }

    /**
     * @return mixed
     */
    public function getType()
    {

        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {

        return $this->width;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {

        return $this->height;
    }


    public function __debugInfo()
    {

        return [
            'x' => $this->x,
            'y' => $this->y,
            'spriteIndex' => $this->spriteIndex,
            'flags' => $this->flags,
            'rgba' => [$this->r, $this->g, $this->b, $this->a],
            'zoom' => [$this->zoomX, $this->zoomY],
            'angle' => $this->angle,
            'type' => $this->type,
            'size' => [$this->width, $this->height]
        ];
    }
}