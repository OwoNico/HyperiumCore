<?php

namespace hyperiummc\core\task;

use hyperiummc\core\HyperiumCore;
use hyperiummc\zenapi\lang\LangManager;
use hyperiummc\zenapi\ZenAPI;
use pocketmine\scheduler\Task;

class ScoreboardTask extends Task{

   public function __construct(HyperiumCore $plugin){
       $this->plugin = $plugin;
   }

    public function onRun(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
            if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
                $zenapi = ZenAPI::getInstance();
                $scoreboard = $zenapi->getScoreboardAPI();
                $ping = $player->getNetworkSession()->getPing() / 2 !== -1 ? round($player->getNetworkSession()->getPing() / 2) : "20";

                $lines = [
                    1 => " §e" . LangManager::getTranslatedMessage("rank.text", $player) . ": §b{$this->plugin->getPlayerRank($player)}",
                    2 => " §e" . LangManager::getTranslatedMessage("level.text", $player) . ": §b{$zenapi->getLevelManager()->getPlayerLevel($player)}",
                    3 => " §e" . LangManager::getTranslatedMessage("coins.text", $player) . ": §b{$zenapi->getCoinManager()->getPlayerCoin($player)}",
                    4 => "§l⨖ §r§e" . LangManager::getTranslatedMessage("ping.text", $player) . ": §b" . $ping . "ms",
                    5 => " §b{$this->plugin->getTimeFormat($player)}"
                ];

                $scoreboard->new($player, 'lobby', "score");

                foreach ($lines as $line => $content){
                    $scoreboard->setLine($player, $line, $content);
                }
            }
        }
    }
}
