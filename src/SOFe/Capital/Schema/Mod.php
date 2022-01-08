<?php

declare(strict_types=1);

namespace SOFe\Capital\Schema;

use Generator;
use SOFe\Capital\TypeMap\ModInterface;
use SOFe\Capital\TypeMap\TypeMap;

final class Mod implements ModInterface {
    public const API_VERSION = "0.1.0";

    /**
     * @return VoidPromise
     */
    public static function init(TypeMap $typeMap) : Generator {
        false && yield;


    }

    public static function shutdown(TypeMap $typeMap) : void {
    }
}
