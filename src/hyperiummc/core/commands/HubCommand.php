<?php

namespace hyperiummc\core\commands;

use hyperiummc\core\HyperiumCore;
use hyperiummc\zenapi\ZenAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class HubCommand extends Command {

    public function __construct(HyperiumCore $plugin)
    {
        parent::__construct("hub", "Back to lobby", "", ["lobby"]);
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $commandLabel, array $args)
    {
        if ($sender instanceof Player){
            $zenapi = ZenAPI::getInstance();
            $scoreboard = $zenapi->getScoreboardAPI();

            $sender->getHungerManager()->setFood(20);
            $sender->setGamemode(GameMode::ADVENTURE());
            $sender->setHealth(20);
            $sender->setMaxHealth(20);
            $sender->getEffects()->clear();

            $sender->getInventory()->setHeldItemIndex(0);

            $sender->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());

            $scoreboard->remove($sender);

            $sender->setNameTag("§7[§b{$this->plugin->getPlayerRank($sender)}§7] §r{$sender->getName()}");

            $this->plugin->getItemManager()->giveItem($sender);
           // $sender->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn()->add(0.5, 1.5, 0.5));
        } else {
            $sender->sendMessage("Run this in game!");
        }
    }
}
