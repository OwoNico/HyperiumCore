<?php

namespace hyperiummc\core\task;

use hyperiummc\core\HyperiumCore;
use pocketmine\scheduler\Task;

class AutoBroadcastTask extends Task{

    public function __construct(HyperiumCore $plugin){
        $this->plugin = $plugin;
    }

    public function onRun(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
            if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
                switch (rand(1, 4)){
                    case 1:
                        $player->sendMessage("§l §6Have fun in HyperiumMC Network!");
                        break;
                    case 2:
                        $player->sendMessage("§l §cCheating is not allowed! Use /report to report the cheaters!");
                        break;
                    case 3:
                        $player->sendMessage("§l §eBuy higher rank from our official discord to access more features!");
                        break;
                    case 4:
                        $player->sendMessage("§l §eJoin our official discord (https://discord.io/hyperiummc) to get latest information about our network!");
                        break;
                }
            }
        }
    }
}
