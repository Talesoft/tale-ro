<?php

namespace Tale\Ro;


use Tale\Ro\Act\Action;
use Tale\Ro\Act\AttachInfo;
use Tale\Ro\Act\Clip;
use Tale\Ro\Act\Motion;
use Tale\Ro\Act\Rectangle;

class Act extends AbstractFormat
{

    const MAGIC_HEADER = 'AC';
    const MAGIC_HEADER_SIZE = 2;

    const HEADER_SIZE = self::MAGIC_HEADER_SIZE + 14;

    private $magicHeader;
    private $version;
    private $actions;
    private $sounds;


    public function __construct($handle = null)
    {
        parent::__construct($handle);

        $this->magicHeader = null;
        $this->version = null;
        $this->actions = [];
        $this->sounds = [];

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

    private function read()
    {

        if ($this->getSize() === 0) {

            //Fill with default data and return. Empty action file.
            $this->magicHeader = self::MAGIC_HEADER;
            $this->version = 2.1;
            return;
        }

        if ($this->getSize() < self::HEADER_SIZE)
            throw new \RuntimeException(
                "Failed to read act file: Header needs to have at least ".self::HEADER_SIZE." bytes"
            );

        fseek($this->getHandle(), 0, SEEK_SET);

        $header = unpack('A2magicHeader/CminorVersion/CmajorVersion/vactionCount/C10reserved', fread($this->getHandle(), self::HEADER_SIZE));

        if ($header['magicHeader'] !== self::MAGIC_HEADER)
            throw new \RuntimeException(
                "Failed to read sprite: Magic header mismatch"
            );

        $this->magicHeader = $header['magicHeader'];
        $this->version = $header['minorVersion'] / 10 + $header['majorVersion'];

        $this->readActions($header['actionCount']);
    }

    private function readActions($actionCount)
    {

        //Skip to end of header
        fseek($this->getHandle(), self::HEADER_SIZE);

        var_dump("Action Count: $actionCount");

		for ($i = 0; $i < $actionCount; $i++) {

            $motionCount = unpack('V', fread($this->getHandle(), 4))[1];

            $motions = [];
            for ($j = 0; $j < $motionCount; $j++) {

                //TODO: Fix this. These are two rectangles, but I don't know the exact struct format. This seems to be wrong.
                /*$data = unpack('Vleft1/Vtop1/Vright1/Vbottom1/Vleft2/Vtop2/Vright2/Vbottom2/VclipCount', fread($this->getHandle(), 36));

                $range1 = new Rectangle($data['left1'], $data['top1'], $data['right1'], $data['bottom1']);
                $range2 = new Rectangle($data['left2'], $data['top2'], $data['right2'], $data['bottom2']);*/


                fseek($this->getHandle(), 32, SEEK_CUR);
                $clipCount = unpack('V', fread($this->getHandle(), 4))[1];

                var_dump("CC $clipCount");

                $clips = [];
                for ($k = 0; $k < $clipCount; $k++) {
                    
                    $clip = null;
                    if ($this->version < 2.0) {

                        $data = unpack('Vx/Vy/VspriteIndex/Vflags', fread($this->getHandle(), 16));
                        $clip = new Clip($data['x'], $data['y'], $data['spriteIndex'], $data['flags'], 255, 255, 255, 255, 1.0, 1.0, 0, 0);
                    } else if ($this->version < 2.4) {

                        trigger_error('Reading with <2.4', E_USER_NOTICE);

                        $data = unpack('Vx/Vy/VspriteIndex/Vflags/Cr/Cg/Cb/Ca/fzoom/Vangle/Vtype', fread($this->getHandle(), 32));
                        $clip = new Clip(
                            $data['x'], $data['y'], $data['spriteIndex'], $data['flags'],
                            $data['r'], $data['g'], $data['b'], $data['a'],
                            $data['zoom'], $data['zoom'], $data['angle'], $data['type']
                        );
                    } else if ($this->version === 2.4) {


                        $data = unpack('Vx/Vy/VspriteIndex/Vflags/Cr/Cg/Cb/Ca/fzoomX/fzoomY/Vangle/Vtype', fread($this->getHandle(), 36));
                        $clip = new Clip(
                            $data['x'], $data['y'], $data['spriteIndex'], $data['flags'],
                            $data['r'], $data['g'], $data['b'], $data['a'],
                            $data['zoomX'], $data['zoomY'], $data['angle'], $data['type']
                        );
                    } else {

                        trigger_error('Reading with >2.5', E_USER_NOTICE);
                        $data = unpack('Vx/Vy/VspriteIndex/Vflags/Cr/Cg/Cb/Ca/fzoomX/fzoomY/Vangle/Vtype/Vwidth/Vheight', fread($this->getHandle(), 44));
                        $clip = new Clip(
                            $data['x'], $data['y'], $data['spriteIndex'], $data['flags'],
                            $data['r'], $data['g'], $data['b'], $data['a'],
                            $data['zoomX'], $data['zoomY'], $data['angle'], $data['type'],
                            $data['width'], $data['height']
                        );
                    }
                    
                    $clips[] = $clip;
                }


                $eventId = -1;
                if ($this->version >= 2.0)
                    $eventId = unpack('V', fread($this->getHandle(), 4))[1];

                if ($this->version === 2.0)
                    $eventId = -1;

                $attachInfos = [];
                if ($this->version >= 2.3) {

                    $attachInfoCount = unpack('V', fread($this->getHandle(), 4))[1];

                    for ($k = 0; $k < $attachInfoCount; $k++) {

                        $data = unpack('Vunknown/Vx/Vy/Vattribute', fread($this->getHandle(), 16));
                        $attachInfos[] = new AttachInfo($data['unknown'], $data['x'], $data['y'], $data['attribute']);
                    }
                }

                $motions[] = $motion = new Motion(new Rectangle(0, 0, 0, 0), new Rectangle(0, 0, 0, 0), $clips, $eventId, $attachInfos);

                var_dump("Motion $j (".ftell($this->getHandle()).")", $motion);

                if ($j > 1) exit;
            }

            $this->actions[] = new Action($this, $motions);
        }
	}


    public function __debugInfo()
    {

        return [
            'magicHeader' => $this->magicHeader,
            'version' => $this->version,
            'actions' => $this->actions
        ];
    }
}