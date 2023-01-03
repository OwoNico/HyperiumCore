<?php

namespace hyperiummc\core\commands;

use hyperiummc\core\HyperiumCore;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class UnnickCommand extends Command{

    public function __construct(HyperiumCore $plugin)
    {
        parent::__construct("unnick", "Unnick Command");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player){
            if (!isset($this->plugin->nickedPlayer[$sender->getName()])){
                $sender->sendMessage("§cYou are not nicked!");
                return;
            }
            $sender->setDisplayName($sender->getNetworkSession()->getDisplayName());
            $sender->sendMessage("§aYour nickname were changed to default name");

            unset($this->plugin->nickedPlayer[$sender->getName()]);
            $this->plugin->nickedName[$sender->getName()] = $sender->getNetworkSession()->getDisplayName();
        }
    }
}
