<?php

declare(strict_types=1);

namespace SOFe\Capital\Database;

use SOFe\AwaitGenerator\Await;
use SOFe\Capital\ModInterface;

final class Mod implements ModInterface {
    public static function init() : void {
        Await::g2c(Database::getInstance()->init());
    }

    public static function shutdown() : void {
        Database::getInstance()->shutdown();
    }
}
