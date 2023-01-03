<?php

namespace hyperiummc\core\task;

use hyperiummc\core\HyperiumCore;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class CosmeticsTask extends Task{

    public function __construct(HyperiumCore $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onRun(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
            if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
                $fly = new Config($this->plugin->getDataFolder() . "cosmetics/Fly.yml", Config::YAML);

                if ($fly->exists($player->getXUID())){
                    $player->setAllowFlight(true);
                }
            }
        }
    }
}
