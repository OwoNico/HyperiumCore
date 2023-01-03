<?php

declare(strict_types=1);


namespace hyperiummc\core\session;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;

class Session
{

    private Player $player;

    private int $current_ping;


    public function __construct(Player $player)
    {
        $this->player = $player;
        $this->current_ping = $this->getPing();
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    private function clearInventory(): void
    {
        $this->player->getInventory()->clearAll();
        $this->player->getArmorInventory()->clearAll();
    }

    public function setImmobile(bool $immobile = true): void
    {
        $this->player->setImmobile($immobile);
    }

    public function sendOrbSound(): void
    {
        $position = $this->player->getPosition();
        $packet = new PlaySoundPacket();
        $packet->soundName = "random.orb";
        $packet->x = $position->getX();
        $packet->y = $position->getY();
        $packet->z = $position->getZ();
        $packet->volume = 1;
        $packet->pitch = 1;
        $this->sendDataPacket($packet);
    }

    public function sendDataPacket(ClientboundPacket $packet): void
    {
        $this->player->getNetworkSession()->sendDataPacket($packet);
    }

    public function getPing(): int
    {
        if (!$this->player->isOnline()) {
            return 0;
        }
        return $this->player->getNetworkSession()->getPing() ?? 0;
    }

    public function checkPing(): bool
    {
        $ping = $this->getPing();
        if ($this->current_ping !== $ping) {
            $this->current_ping = $ping;
            return true;
        }
        return false;
    }

    public function getUsername(): string
    {
        return $this->player->getName();
    }

    public function popup(string $popup): void
    {
        if ($this->player->isConnected()) $this->player->sendPopup($popup);
    }

    public function title(string $title, string $subtitle = ""): void
    {
        if ($this->player->isConnected()) $this->player->sendTitle($title, $subtitle);
    }

    public function message(string $message): void
    {
        if ($this->player->isConnected()) $this->player->sendMessage($message);
    }
}
