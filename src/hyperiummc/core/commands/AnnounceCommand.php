<?php

namespace hyperiummc\core\commands;

use hyperiummc\core\HyperiumCore;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class AnnounceCommand extends Command{

    public function __construct(HyperiumCore $plugin)
    {
        parent::__construct("announce", "Announce message to all players");

        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player){
            if (!isset($args)){
                $sender->sendMessage("§cPls provide a message");
            }
            if ($sender->hasPermission("hyperiummc.staff")){
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
                    $message = implode(" ", $args);
                    $player->sendMessage("§l§6Announcement §r§7({$sender->getName()}) §l§c» §r§b{$message}");
                }
            } else{
                $sender->sendMessage("§cYou dont have permissions to use this");
            }
        } else{
            $sender->sendMessage("Run this in game!");
        }
    }
}
