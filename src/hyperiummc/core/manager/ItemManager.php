<?php

namespace hyperiummc\core\manager;

use hyperiummc\core\HyperiumCore;
use hyperiummc\core\item\HyperiumMCItemIds;
use hyperiummc\zenapi\lang\LangManager;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class ItemManager{

    public function __construct(HyperiumCore $plugin){
        $this->plugin = $plugin;
    }

    public function giveItem(Player $player){
        $player->getInventory()->clearAll();

        $item = ItemFactory::getInstance();

        $player->getInventory()->setItem(0, $item->get(ItemIds::MAGMA_CREAM)->setCustomName(TextFormat::AQUA . LangManager::getTranslatedMessage("travelcompass.name", $player)));
        if ($player->hasPermission("hyperiummc.staff")) {
            $player->getInventory()->setItem(6, $item->get(ItemIds::EMERALD)->setCustomName(TextFormat::AQUA . LangManager::getTranslatedMessage("stafftool.name", $player)));
        }

        $player->getInventory()->setItem(2, VanillaBlocks::BARRIER()->asItem()->setCustomName("§r "));

        $player->getInventory()->setItem(1, $item->get(ItemIds::PAPER)->setCustomName(TextFormat::AQUA . LangManager::getTranslatedMessage("socialitem.name", $player)));
        $player->getInventory()->setItem(4, $item->get(ItemIds::SLIMEBALL)->setCustomName(TextFormat::AQUA . LangManager::getTranslatedMessage("reportitem.name", $player)));
        $player->getInventory()->setItem(7, $item->get(ItemIds::DIAMOND)->setCustomName(TextFormat::AQUA . LangManager::getTranslatedMessage("cosmeticsitem.name", $player)));
        $player->getInventory()->setItem(5, $item->get(ItemIds::ENCHANTED_BOOK)->setCustomName(TextFormat::AQUA . LangManager::getTranslatedMessage("profileitem.name", $player)));
        $player->getInventory()->setItem(8, $item->get(ItemIds::COMPARATOR)->setCustomName(TextFormat::AQUA . LangManager::getTranslatedMessage("settingitem.name", $player)));
    }
}
