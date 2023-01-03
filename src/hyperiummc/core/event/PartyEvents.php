<?php

namespace hyperiummc\core\event;

use hyperiummc\core\session\SessionManager;
use hyperiummc\zenapi\ZenAPI;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;

class PartyEvents implements Listener{

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();

        if(ZenAPI::getInstance()->getPartyManager()->isPlayerInParty($player)) {

            $party = ZenAPI::getInstance()->getPartyManager()->getPartyOfPlayer(SessionManager::getSession($player));
            if(is_null($party)) return;

            if($party->isOwner($player))
                ZenAPI::getInstance()->getPartyManager()->delete($party->getName());
            else $party->remove(SessionManager::getSession($player));
        }
    }

    public function onCommandProcess(PlayerCommandPreprocessEvent $event){
        $player = $event->getPlayer();
        $command = str_replace("/", "", $event->getMessage());

        if(in_array($command, ["hbws random", "sw random", "skywars random", "mlgblock random", "mb random", "hub", "thebridge random", "tb random", "practice"])) {
            if (ZenAPI::getInstance()->getPartyManager()->isPlayerInParty($player)){
                $party = ZenAPI::getInstance()->getPartyManager()->getPartyOfPlayer(SessionManager::getSession($player));
                if(is_null($party)) return;

                if ($party->isOwner($player)){
                    foreach ($party->getPlayers() as $members){
                        if (!$party->isOwner($members->getPlayer())){
                            Server::getInstance()->dispatchCommand($members->getPlayer(), $command);
                        }
                    }
                }
            }
        }
    }
}
