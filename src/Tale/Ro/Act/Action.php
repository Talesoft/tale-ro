<?php

namespace Tale\Ro\Act;

use Phim\Color;
use Phim\Color\Palette;
use Tale\Ro\Act;
use Tale\Ro\Spr;

class Action
{

    private $act;
    private $motions;


    public function __construct(Act $act, array $motions)
    {

        $this->act = $act;
        $this->motions = $motions;
    }

    /**
     * @return Motion[]
     */
    public function getMotions()
    {

        return $this->motions;
    }

    public function __debugInfo()
    {

        return [
            'motions' => $this->motions
        ];
    }

}