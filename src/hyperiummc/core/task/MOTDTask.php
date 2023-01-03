<?php

namespace hyperiummc\core\task;

use hyperiummc\core\HyperiumCore;
use pocketmine\scheduler\Task;

class MOTDTask extends Task{

    public function __construct(HyperiumCore $plugin){
        $this->plugin = $plugin;
    }

    public function onRun(): void
    {
        $motd = array("§cTheBridge", "§6HyperiumMC Network", "§eBedWars");
        $arraymotd = $motd[array_rand($motd)];

        $this->plugin->getServer()->getNetwork()->setName($arraymotd);
    }
}
