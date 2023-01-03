<?php

declare(strict_types=1);

namespace hyperiummc\core\session;

use pocketmine\player\Player;

class SessionManager {

    /** @var Session[] */
    static private array $sessions = [];

    /**
     * @return Session[]
     */
    static public function getSessions(): array {
        return self::$sessions;
    }

    static public function getSession(Player $player): ?Session {
        return self::$sessions[$player->getName()] ?? null;
    }

    static public function createSession(Player $player): void {
        $session = new Session($player);
        self::$sessions[$player->getName()] = $session;
    }

    static public function removeSession(Player $player): void {
        $session = self::$sessions[$player->getName()];
        unset($session);
    }

}
