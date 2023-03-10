<?php

declare(strict_types=1);

namespace hyperiummc\core\skin;

use hyperiummc\core\HyperiumCore;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\LegacySkinAdapter;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;

class PersonaSkinAdapter extends LegacySkinAdapter
{
    public function fromSkinData(SkinData $data): Skin
    {
        if ($data->isPersona()) {
            return HyperiumCore::getRandomSkin();
        }
        return parent::fromSkinData($data); // TODO: Change the autogenerated stub
    }

}