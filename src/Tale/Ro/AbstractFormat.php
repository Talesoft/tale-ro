<?php

namespace Tale\Ro;

abstract class AbstractFormat
{

    private $handle;

    public function __construct($handle)
    {

        if (!is_resource($handle))
            throw new \InvalidArgumentException(
                "Handle passed to format needs to be a valid resource, ".gettype($handle)." ($handle) given"
            );

        $this->handle = $handle;
    }

    public function __destruct()
    {

        //Dont close??
        /*
        if (is_resource($this->handle))
            fclose($this->handle);
        */
    }

    /**
     * @return resource
     */
    public function getHandle()
    {

        return $this->handle;
    }

    /**
     * @return int
     */
    public function getSize()
    {

        return fstat($this->handle)['size'];
    }
}