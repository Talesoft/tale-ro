<?php

namespace Tale\Ro\Act;

use Phim\Color;
use Phim\Color\Palette;
use Tale\Ro\Act;

class Motion
{

    private $range1;
    private $range2;
    private $clips;
    private $eventId;
    private $attachInfos;


    public function __construct(Rectangle $range1, Rectangle $range2, array $clips, $eventId, array $attachInfos)
    {

        $this->range1 = $range1;
        $this->range2 = $range2;
        $this->clips = $clips;
        $this->eventId = $eventId;
        $this->attachInfos = $attachInfos;
    }

    /**
     * @return Rectangle
     */
    public function getRange1()
    {

        return $this->range1;
    }

    /**
     * @return Rectangle
     */
    public function getRange2()
    {

        return $this->range2;
    }

    /**
     * @return mixed
     */
    public function getClips()
    {

        return $this->clips;
    }

    public function getClip($index)
    {

        return $this->clips[$index];
    }

    /**
     * @return mixed
     */
    public function getEventId()
    {

        return $this->eventId;
    }

    /**
     * @return mixed
     */
    public function getAttachInfos()
    {

        return $this->attachInfos;
    }

    public function __debugInfo()
    {

        return [
            'range1' => $this->range1,
            'range2' => $this->range2,
            'eventId' => $this->eventId,
            'attachInfos' => $this->attachInfos,
            'clips' => $this->clips
        ];
    }
}