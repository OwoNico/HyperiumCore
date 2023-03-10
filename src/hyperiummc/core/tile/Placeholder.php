<?php

declare(strict_types = 1);

namespace hyperiummc\core\tile;

use pocketmine\block\tile\Tile;
use pocketmine\block\{Block, BlockFactory, VanillaBlocks};
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class Placeholder extends Tile{

    protected Block $block;

    public function __construct(World $world, Vector3 $pos){
        parent::__construct($world, $pos);
        $this->block = VanillaBlocks::INFO_UPDATE();
    }

    public function readSaveData(CompoundTag $nbt): void{
        $blockTag = $nbt->getCompoundTag('Block');
        if($blockTag !== null){
            $this->block = BlockFactory::getInstance()->get($blockTag->getShort('id'), $blockTag->getByte('meta'));
        }
    }

    protected function writeSaveData(CompoundTag $nbt): void{
        // NOOP
    }

    public function getExtendedBlock(): Block{
        return clone $this->block;
    }
}