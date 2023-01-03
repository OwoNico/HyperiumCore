<?php

namespace hyperiummc\core\commands;

use hyperiummc\core\HyperiumCore;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class StaffchatCommand extends Command{

    public function __construct(HyperiumCore $plugin)
    {
        parent::__construct("staffchat", "Staffchat commmand");

        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player){
            if (!isset($args)){
                $sender->sendMessage("§cPls provide a message");
            }
            if ($sender->hasPermission("hyperiummc.staff")){
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $staffs){
                    $message = implode(" ", $args);
                    if ($staffs->hasPermission("hyperiummc.staff")) {
                        $staffs->sendMessage("§l§bStaffchat §r§7({$sender->getName()}) §l§6» §r{$message}");
                    }
                }
            } else{
                $sender->sendMessage("§cYou dont have permission to use this");
            }
        } else{
            $sender->sendMessage("Run this in game");
        }
    }
}
