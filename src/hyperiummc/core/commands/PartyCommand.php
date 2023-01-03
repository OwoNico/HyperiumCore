<?php

namespace hyperiummc\core\commands;

use hyperiummc\core\party\form\PartyForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;

class PartyCommand extends Command{

    public function __construct()
    {
        parent::__construct("party", "Party Command", "", ["p"]);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) return;

        $sender->sendForm(new PartyForm($sender));
    }
}
