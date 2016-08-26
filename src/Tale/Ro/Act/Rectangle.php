<?php

namespace Tale\Ro\Act;

class Rectangle
{

    private $left;
    private $top;
    private $right;
    private $bottom;

    /**
     * Rectangle constructor.
     * @param $left
     * @param $top
     * @param $right
     * @param $bottom
     */
    public function __construct($left, $top, $right, $bottom)
    {

        $this->left = $left;
        $this->top = $top;
        $this->right = $right;
        $this->bottom = $bottom;
    }

    /**
     * @return int
     */
    public function getLeft()
    {

        return $this->left;
    }

    /**
     * @return int
     */
    public function getTop()
    {

        return $this->top;
    }

    /**
     * @return int
     */
    public function getRight()
    {

        return $this->right;
    }

    /**
     * @return int
     */
    public function getBottom()
    {

        return $this->bottom;
    }
}