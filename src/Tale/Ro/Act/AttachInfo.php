<?php

namespace Tale\Ro\Act;

use Phim\Color;
use Phim\Color\Palette;
use Tale\Ro\Act;
use Tale\Ro\Spr;

class AttachInfo
{

    private $unknown;
    private $x;
    private $y;
    private $attribute;


    public function __construct($unknown, $x, $y, $attribute)
    {

        $this->unknown = $unknown;
        $this->x = $x;
        $this->y = $y;
        $this->attribute = $attribute;
    }

    /**
     * @return mixed
     */
    public function getUnknown()
    {

        return $this->unknown;
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
    public function getAttribute()
    {

        return $this->attribute;
    }

    public function __debugInfo()
    {

        return [
            'unknown' => $this->unknown,
            'x' => $this->x,
            'y' => $this->y,
            'attribute' => $this->attribute
        ];
    }

}