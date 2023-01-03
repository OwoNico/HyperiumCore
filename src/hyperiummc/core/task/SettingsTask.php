<?php

namespace hyperiummc\core\task;

use hyperiummc\core\event\EventsListener;
use hyperiummc\core\HyperiumCore;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class SettingsTask extends Task{

    public function __construct(HyperiumCore $plugin){
        $this->plugin = $plugin;
    }

    public function onRun(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
            if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
                $hidePlayers = new Config($this->plugin->getDataFolder() . "settings/HidePlayers.yml", Config::YAML);

                if ($hidePlayers->exists($player->getXUID())){
                    foreach ($this->plugin->getServer()->getOnlinePlayers() as $players){
                        $player->hidePlayer($players);
                    }
                } else{
                    foreach ($this->plugin->getServer()->getOnlinePlayers() as $players){
                        $player->showPlayer($players);
                    }
                }
            }
        }
    }
}
