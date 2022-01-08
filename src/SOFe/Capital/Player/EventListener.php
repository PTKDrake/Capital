<?php

declare(strict_types=1);

namespace SOFe\Capital\Player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use SOFe\Capital\Di\FromContext;
use SOFe\Capital\Di\SingletonArgs;

final class EventListener implements Listener, FromContext {
    use SingletonArgs;

    public function __construct(
        private SessionManager $sessionManager,
    ) {}

    public function onPlayerLogin(PlayerLoginEvent $event) : void {
        $player = $event->getPlayer();
        $this->sessionManager->createSession($player);
    }

    public function onPlayerQuit(PlayerQuitEvent $event) : void {
        $player = $event->getPlayer();
        $this->sessionManager->removeSession($player);
    }
}
