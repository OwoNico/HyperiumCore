<?php

namespace hyperiummc\core\entity;

use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\nbt\tag\CompoundTag;
use hyperiummc\core\HyperiumCore;

class BedwarsNPCEntity extends Human{

    protected function initEntity(CompoundTag $nbt) : void{
	parent::initEntity($nbt);
        $this->setImmobile(true);
        //$this->location->yaw = 0; //220
        //$this->location->headyaw = 220.0;
    }

    
    public function onUpdate(int $currentTick): bool
    {
        if($this->isClosed()){
	    return false;
        }


        if(HyperiumCore::$bwnpcedit){

            $this->location->yaw += 1.2; //5.5
            $this->updateMovement();

            //TODO: Add bouncing
            $this->move($this->motion->x, $this->motion->y, $this->motion->z);

        }

        if(HyperiumCore::$bwnpcleft){
            $this->location->yaw -= 1.2;
            $this->updateMovement();

            //TODO: Add bouncing
            $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        }

        $this->scheduleUpdate();

        parent::onUpdate($currentTick);
        return true;
    }
}
