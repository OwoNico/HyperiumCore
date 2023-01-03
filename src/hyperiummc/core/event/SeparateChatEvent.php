<?php

namespace hyperiummc\core\event;

use hyperiummc\core\HyperiumCore;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\Player;

class SeparateChatEvent implements Listener{

    public function onChat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        $recipients = $event->getRecipients();

        foreach ($recipients as $recipient => $key){
            if ($recipient instanceof Player){
                if ($recipient->getWorld() !== $player->getWorld()){
                    unset($recipients[$key]);
                }
            }
        }
        $event->setRecipients($recipients);
    }
}
