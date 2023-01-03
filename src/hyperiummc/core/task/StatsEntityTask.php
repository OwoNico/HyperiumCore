<?php

namespace hyperiummc\core\task;

use hyperiummc\core\entity\StatsEntity;
use hyperiummc\core\HyperiumCore;
use hyperiummc\zenapi\lang\LangManager;
use hyperiummc\zenapi\ZenAPI;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\scheduler\Task;

class StatsEntityTask extends Task{

    public function __construct(HyperiumCore $plugin){
        $this->plugin = $plugin;
    }

    public function onRun(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
                foreach ($this->plugin->getServer()->getWorldManager()->getWorldByName("Lobby")->getEntities() as $entity){
                    if ($entity instanceof StatsEntity){
                        $this->plugin->getServer()->broadcastPackets([$player], [SetActorDataPacket::create($entity->getId(), [EntityMetadataProperties::NAMETAG => new StringMetadataProperty(
                            "§6Stats\n§e" . LangManager::getTranslatedMessage("playername.text", $player) . ": §b{$player->getName()}\n§e" . LangManager::getTranslatedMessage("rank.text", $player) . ": §b{$this->plugin->getPlayerRank($player)}\n§e" . LangManager::getTranslatedMessage("level.text", $player) . ": §b" . ZenAPI::getInstance()->getLevelManager()->getPlayerLevel($player) . "\n§e" . LangManager::getTranslatedMessage("exp.text", $player) . ": §c" . ZenAPI::getInstance()->getLevelManager()->getPlayerEXP($player) . "§e/§b" . ZenAPI::getInstance()->getLevelManager()->getExpNeed($player) . "\n§e" . LangManager::getTranslatedMessage("coins.text", $player) . ": §b" . ZenAPI::getInstance()->getCoinManager()->getPlayerCoin($player) . "\n\n\n")], 20)]);

                        //$entity->setNameTag("§6Stats\n§e" . LangManager::getTranslatedMessage("playername.text", $player) . ": §b{$player->getName()}\n§e" . LangManager::getTranslatedMessage("rank.text", $player) . ": §b{$this->plugin->getPlayerRank($player)}\n§e" . LangManager::getTranslatedMessage("level.text", $player) . ": §b" . ZenAPI::getInstance()->getLevelManager()->getPlayerLevel($player) . "\n§e" . LangManager::getTranslatedMessage("exp.text", $player) . ": §c" . ZenAPI::getInstance()->getLevelManager()->getPlayerEXP($player) . "§e/§b" . ZenAPI::getInstance()->getLevelManager()->getExpNeed($player) . "\n§e" . LangManager::getTranslatedMessage("coins.text", $player) . ": §b" . ZenAPI::getInstance()->getCoinManager()->getPlayerCoin($player) . "\n\n\n");

                        $entity->setNameTagAlwaysVisible();
                    }
                }
        }
    }
}
