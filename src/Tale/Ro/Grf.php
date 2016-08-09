<?php

namespace Tale\Ro;

class Grf
{

    const MAGIC_HEADER = 'Master of Magic';

    const MAGIC_HEADER_SIZE = 15; //Length of MAGIC_HEADER
    const HEADER_MID_SIZE = self::MAGIC_HEADER_SIZE + 15; //Length of MAGIC_HEADER + ENCRYPTION_WATERMARK
    const HEADER_SIZE = self::HEADER_MID_SIZE + 16; //HEADER_MID_SIZE + 4 ints (table offset, seed, fcount, version)

    //Important for GRF files with encryption
    const ENCRYPTED_ENCRYPTION_WATERMARK = [
        0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0A, 0x0B, 0x0C, 0x0D, 0x0E
    ];

    //Can be used on 0x200 unencrypted GRF files
    const UNENCRYPTED_ENCRYPTION_WATERMARK = [
        0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00
    ];

    const FILE_TYPE_FILE = 0x01;
    const FILE_TYPE_ENCRYPTED_MIXED = 0x02;
    const FILE_TYPE_ENCRYPTED_DES = 0x04;

    const VERSION_200 = 0x200;
    const VERSION_103 = 0x103;
    const VERSION_102 = 0x102;
}