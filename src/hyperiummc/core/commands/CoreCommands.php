<?php

namespace hyperiummc\core\commands;

use hyperiummc\core\entity\BedwarsNPCEntity;
use hyperiummc\core\entity\StatsEntity;
use hyperiummc\core\HyperiumCore;
use hyperiummc\core\utils\Converter;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Skin;
use pocketmine\player\Player;
use pocketmine\entity\Location;

class CoreCommands extends Command {

    public function __construct(HyperiumCore $plugin)
    {
        parent::__construct("core", "HyperiumCore Commands", "/core <option> <on|off>", [""]);
        $this->setPermission("hyperiummc.staff");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $commandLabel, array $args)
    {
        if ($sender instanceof Player){

            if (!isset($args[0])) return;
            switch ($args[0]){
                case "stats":
                    $entity = new StatsEntity($sender->getLocation()->asLocation(), $sender->getSkin());
                    $entity->setSkin($sender->getSkin());
                    $entity->teleport($sender->getLocation()->asVector3());
                    $entity->spawnToAll();
                    break;
                case "clearstats":
                    foreach ($sender->getWorld()->getEntities() as $entity){
                        if ($entity instanceof StatsEntity){
                            $entity->flagForDespawn();
                        }
                    }
                    break;
                case "npc":
                    if (!isset($args[1])) return;
                    if (!isset($args[2])) return;
                    switch ($args[1]){
                        case "bedwars":
                            if ($args[2] == "spawn"){
                                $bedwars = new BedwarsNPCEntity($sender->getLocation()->asLocation(), $sender->getSkin());
                                //$bedwars->teleport(new Location($sender->getLocation()->getX(), $sender->getLocation()->getY() + $sender->getEyeHeight(), $sender->getLocation()->getZ(), $sender->getWorld(), 360, 0));
                                //$bedwars->teleport($sender->getLocation()->asVector3()->add(0.5, 0, 0.5));
                                $bedwars->setSkin(new Skin($bedwars->getSkin()->getSkinId(), Converter::getPngSkin($this->plugin->getDataFolder() . "/npc/" . "bedwars.png"), $bedwars->getSkin()->getCapeData(), "geometry.unknown", file_get_contents($this->plugin->getDataFolder() . "/npc/" . "bedwars.json")));
                                $bedwars->sendSkin();
                                $bedwars->setNameTag("§l§6BedWars\n§r§bClick to play");
                                $bedwars->setNameTagAlwaysVisible(true);
                                $bedwars->spawnToAll();
                            }
                            if ($args[2] == "kill"){
                                foreach($sender->getWorld()->getEntities() as $entity){
                                    if ($entity instanceof BedwarsNPCEntity){
                                        $entity->close();
                                    }
                                }
                            }

                            if ($args[2] == "edit"){
                                if (HyperiumCore::$bwnpcedit){
                                    HyperiumCore::$bwnpcedit = false;
                                } else{
                                    HyperiumCore::$bwnpcedit = true;
                                }
                            }

                            if ($args[2] == "left"){
                                if (HyperiumCore::$bwnpcleft){
                                    HyperiumCore::$bwnpcleft = false;
                                } else{
                                    HyperiumCore::$bwnpcleft = true;
                                }
                            }
                            break;
                    }
                    break;
            }
        } else {
            $sender->sendMessage("Run this in game!");
        }
    }
}
