<?php

namespace hyperiummc\core\commands;

use hyperiummc\core\HyperiumCore;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class BWCommand extends Command{

    public function __construct(HyperiumCore $plugin)
    {
        parent::__construct("bedwars", "Bedwars Command", null, ["bw"]);

        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) return;

        $this->plugin->getFormManager()->bedwarsForm($sender);
    }
}
