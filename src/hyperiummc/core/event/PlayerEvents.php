<?php

namespace hyperiummc\core\event;

use hyperiummc\core\entity\BedwarsNPCEntity;
use hyperiummc\core\entity\StatsEntity;
use hyperiummc\core\HyperiumCore;
use pocketmine\entity\Living;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;

class PlayerEvents implements Listener{

    public function __construct(HyperiumCore $plugin){
        $this->plugin = $plugin;
    }

    public function onDamage(EntityDamageEvent $event){
        $entity = $event->getEntity();
        if ($entity instanceof Player){
            if ($entity->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
                $event->cancel();
            }
        }

        if ($event instanceof EntityDamageByEntityEvent){
            $entity = $event->getEntity();
            $damager = $event->getDamager();

            if(!$damager instanceof Player) return;

            if ($entity instanceof StatsEntity){
                $event->cancel();
                $this->plugin->getFormManager()->profileForm($damager);
            }

            if ($entity instanceof BedwarsNPCEntity){
                $event->cancel();
                $damager->getServer()->dispatchCommand($damager, "bedwars");
            }
        }
    }

    /**
     * @param EntityDamageByEntityEvent $event
     * @return void
     * @priority LOWEST
     */
    public function knockbackEvent(EntityDamageByEntityEvent $event){
        $knockback = [0.40, 0.42, 0.44, 0.46, 0.48, 0.50, 0.55, 0.60];
        $event->setKnockBack($knockback[array_rand($knockback)]);

        $damager = $event->getDamager();
        if(!$event instanceof EntityDamageByChildEntityEvent and $damager instanceof Living and $damager->isSprinting()){
            $event->setKnockback(1.5*$event->getKnockback()); //According to singleplayer tests
        }
    }

    /**
     * @priority MONITOR
     * If the event didn't get cancelled then we can safely reset the entity's sprint
     */
    public function onPostEntityDamageEventByEntity(EntityDamageByEntityEvent $event): void{
        $damager = $event->getDamager();
        if(!$event instanceof EntityDamageByChildEntityEvent and $damager instanceof Living){
            $damager->setSprinting(false);
        }
    }

    public function onMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
        if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
            if ($player->getLocation()->getY() < -1){
                $player->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());

                $this->plugin->getItemManager()->giveItem($player);
            }
        }
    }

    public function onBreak(BlockBreakEvent $event){
        $player = $event->getPlayer();

        if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()) {
            if (!$player->hasPermission("hyperiummc.staff")){
                $event->cancel();
            }
        }
    }

    public function onBuild(BlockPlaceEvent $event){
        $player = $event->getPlayer();

        if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()) {
            if (!$player->hasPermission("hyperiummc.staff")){
                $event->cancel();
            }

            if ($event->getItem()->hasCustomName()){
                $event->cancel();
            }
        }
    }
}
