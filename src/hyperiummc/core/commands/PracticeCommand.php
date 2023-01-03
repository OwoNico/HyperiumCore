<?php

namespace hyperiummc\core\commands;

use hyperiummc\core\HyperiumCore;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class PracticeCommand extends Command{

    public function __construct(HyperiumCore $plugin)
    {
        parent::__construct("practice", "Transfer to practice server");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player){
            $sender->transfer("play.hyperiummc.ml", 19190);
        }
    }
}
