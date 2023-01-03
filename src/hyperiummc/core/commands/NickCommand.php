<?php

namespace hyperiummc\core\commands;

#using pocketmine.command.Command

use hyperiummc\core\HyperiumCore;
use hyperiummc\zenapi\lang\LangManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class NickCommand extends Command{

    public function __construct(HyperiumCore $plugin)
    {
        parent::__construct("nick", "Change your nickname(Prime+)", "", [""]);

        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player){
            if ($sender->hasPermission("hyperiummc.primeplus")) {
                if (isset($args[0])) {
                    if (!in_array($args[0], ["test", "help", "idk", "empty", "android", "windows", "hyperiummc", "fuck", "sex", "suck"]) || strtolower($args[0]) !== strtolower($sender->getName())) {
                        $sender->setDisplayName($args[0]);

                        $this->plugin->nickedPlayer[$sender->getName()] = $sender;
                        $this->plugin->nickedName[$sender->getName()] = $args[0];

                        $sender->sendMessage("§aNicked your name to " . $args[0]);

                        $sender->sendMessage("§6Use /unnick to reset your nick");
                    } else{
                        $sender->sendMessage("§cInvalid name! Please write another name!");
                    }
                } else{
                    $this->plugin->getFormManager()->nickForm($sender);
                }
            } else{
                $sender->sendMessage("§c" . LangManager::getTranslatedMessage("noperms.highrank.text", $sender));
            }
        } else{
            $sender->sendMessage("Run this in game!");
        }
    }
}