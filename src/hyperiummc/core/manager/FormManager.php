<?php

namespace hyperiummc\core\manager;

use EasyUI\element\Button;
use EasyUI\element\Dropdown;
use EasyUI\element\Option;
use EasyUI\utils\FormResponse;
use EasyUI\variant\CustomForm;
use EasyUI\variant\ModalForm;
use EasyUI\variant\SimpleForm;
use hyperiummc\core\HyperiumCore;
use hyperiummc\core\session\SessionManager;
use hyperiummc\zenapi\form\FormUI;
use hyperiummc\zenapi\lang\LangManager;
use hyperiummc\zenapi\ZenAPI;
use JaxkDev\DiscordBot\Models\Messages\Embed\Embed;
use JaxkDev\DiscordBot\Models\Messages\Embed\Footer;
use JaxkDev\DiscordBot\Models\Messages\Message;
use pocketmine\entity\Skin;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;

class FormManager {

    use FormUI;

    public function __construct(HyperiumCore $plugin){
        $this->plugin = $plugin;
    }

    public function travelForm($player){
        $form = $this->createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null){
                return false;
            }

            switch ($result){
                case 0:
                    $player->chat("/sw random"); //party system issue
                    //$player->getServer()->dispatchCommand($player, "sw random");
                    break;
                case 1:
                    $player->chat("/mb random"); //party system issue
                    //$player->getServer()->dispatchCommand($player, "mb random");
                    break;
                case 2:
                    $this->bedwarsForm($player);
                    break;
                case 3:
                    $player->chat("/tb random");
                    break;
                case 4:
                    $player->chat("/practice");
                    break;
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("travelform.name", $player));
        $form->addButton(LangManager::getTranslatedMessage("skywars.name", $player), 0, "textures/other/skywars");
        $form->addButton(LangManager::getTranslatedMessage("mlgblock.name", $player), 0, "textures/other/mlgblock");
        $form->addButton(LangManager::getTranslatedMessage("bedwars.text", $player), 0, "textures/other/bedwars");
        $form->addButton(LangManager::getTranslatedMessage("thebridge.text", $player), 0, "textures/other/thebridge");
        $form->addButton(LangManager::getTranslatedMessage("practice.text", $player), 0, "textures/other/practice");

        $form->sendToPlayer($player);
        return $form;
    }

    public function settingForm(Player $player){
        $form = $this->createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null){
                return false;
            }

            switch ($result){
                case 0:
                    $hidePlayers = new Config($this->plugin->getDataFolder() . "settings/HidePlayers.yml", Config::YAML);
                    if ($hidePlayers->exists($player->getXUID())){
                        $hidePlayers->remove($player->getXUID());
                        $hidePlayers->save();
                        $player->sendMessage("§a" . LangManager::getTranslatedMessage("hideplayers.disabled", $player));
                    } else {
                        $hidePlayers->set($player->getXUID(), true);
                        $hidePlayers->save();
                        $player->sendMessage("§a" . LangManager::getTranslatedMessage("hideplayers.enabled", $player));
                    }
                    break;
                case 1:
                    $fly = new Config($this->plugin->getDataFolder() . "cosmetics/Fly.yml", Config::YAML);

                    if ($player->hasPermission("hyperiummc.vip")){
                        if ($fly->exists($player->getXUID())){
                            $fly->remove($player->getXUID());
                            $fly->save();
                            $player->setAllowFlight(false);
                            $player->sendMessage("§a" . LangManager::getTranslatedMessage("fly.disabled", $player));
                        } else {
                            $fly->set($player->getXUID(), true);
                            $fly->save();
                            $player->sendMessage("§a" . LangManager::getTranslatedMessage("fly.enabled", $player));
                        }
                    } else {
                        $player->sendMessage(TextFormat::AQUA . LangManager::getTranslatedMessage("noperms.highrank.text", $player));
                    }
                    break;
                case 2:
                    $player->getServer()->dispatchCommand($player, "lang");
                    break;
                case 3:
                    $autoSprint = new Config($this->plugin->getDataFolder() . "settings/AutoSprint.yml", Config::YAML);
                    if ($autoSprint->exists($player->getXUID())){
                        $autoSprint->remove($player->getXUID());
                        $autoSprint->save();
                        $player->sendMessage("§a" . LangManager::getTranslatedMessage("autosprint.disabled", $player));
                    } else {
                        $autoSprint->set($player->getXUID(), true);
                        $autoSprint->save();
                        $player->sendMessage("§a" . LangManager::getTranslatedMessage("autosprint.enabled", $player));
                    }
                    break;
                case 4:
                    $cpsPopup = new Config($this->plugin->getDataFolder() . "settings/CPSPopup.yml", Config::YAML);
                    if ($cpsPopup->exists($player->getXUID())){
                        $cpsPopup->remove($player->getXUID());
                        $cpsPopup->save();
                        $player->sendMessage("§a" . LangManager::getTranslatedMessage("cpspopup.disabled", $player));
                    } else {
                        $cpsPopup->set($player->getXUID(), true);
                        $cpsPopup->save();
                        $player->sendMessage("§a" . LangManager::getTranslatedMessage("cpspopup.enabled", $player));
                    }
                    break;
                case 5:
                    $hitParticles = new Config($this->plugin->getDataFolder() . "settings/HitParticles.yml", Config::YAML);
                    if ($hitParticles->exists($player->getXUID())){
                        $hitParticles->remove($player->getXUID());
                        $hitParticles->save();
                        $player->sendMessage("§a" . LangManager::getTranslatedMessage("hitparticle.disabled", $player));
                    } else {
                        $hitParticles->set($player->getXUID(), true);
                        $hitParticles->save();
                        $player->sendMessage("§a" . LangManager::getTranslatedMessage("hitparticle.enabled", $player));
                    }
                    break;
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("settingsform.name", $player));

        $hidePlayers = new Config($this->plugin->getDataFolder() . "settings/HidePlayers.yml", Config::YAML);
        $fly = new Config($this->plugin->getDataFolder() . "cosmetics/Fly.yml", Config::YAML);
        $autoSprint = new Config($this->plugin->getDataFolder() . "settings/AutoSprint.yml", Config::YAML);
        $cpsPopup = new Config($this->plugin->getDataFolder() . "settings/CPSPopup.yml", Config::YAML);
        $hitParticles = new Config($this->plugin->getDataFolder() . "settings/HitParticles.yml", Config::YAML);

        if ($hidePlayers->exists($player->getXUID())){
            $form->addButton(LangManager::getTranslatedMessage("hideplayer.text", $player) . "\n" . TextFormat::GREEN . LangManager::getTranslatedMessage("enabled.text", $player));
        } else {
            $form->addButton(LangManager::getTranslatedMessage("hideplayer.text", $player) . "\n" . TextFormat::RED . LangManager::getTranslatedMessage("disabled.text", $player));
        }
        if ($fly->exists($player->getXuid())){
            $form->addButton(LangManager::getTranslatedMessage("fly.text", $player) . "\n" . TextFormat::GREEN . LangManager::getTranslatedMessage("enabled.text", $player));
        } else {
            $form->addButton(LangManager::getTranslatedMessage("fly.text", $player) . "\n" . TextFormat::RED . LangManager::getTranslatedMessage("disabled.text", $player));
        }

        $form->addButton(LangManager::getTranslatedMessage("changelang.text", $player) . "\n" . TextFormat::AQUA . LangManager::getTranslatedMessage("currentlang.text", $player) . ": " . TextFormat::GREEN . LangManager::getTranslatedMessage("lang.name", $player));

        if ($autoSprint->exists($player->getXuid())){
            $form->addButton(LangManager::getTranslatedMessage("autosprint.text", $player) . "\n" . TextFormat::GREEN . LangManager::getTranslatedMessage("enabled.text", $player));
        } else {
            $form->addButton(LangManager::getTranslatedMessage("autosprint.text", $player) . "\n" . TextFormat::RED . LangManager::getTranslatedMessage("disabled.text", $player));
        }

        if ($cpsPopup->exists($player->getXuid())){
            $form->addButton(LangManager::getTranslatedMessage("cpspopup.text", $player) . "\n" . TextFormat::GREEN . LangManager::getTranslatedMessage("enabled.text", $player));
        } else {
            $form->addButton(LangManager::getTranslatedMessage("cpspopup.text", $player) . "\n" . TextFormat::RED . LangManager::getTranslatedMessage("disabled.text", $player));
        }

        if ($hitParticles->exists($player->getXuid())){
            $form->addButton(LangManager::getTranslatedMessage("hitparticle.text", $player) . "\n" . TextFormat::GREEN . LangManager::getTranslatedMessage("enabled.text", $player));
        } else {
            $form->addButton(LangManager::getTranslatedMessage("hitparticle.text", $player) . "\n" . TextFormat::RED . LangManager::getTranslatedMessage("disabled.text", $player));
        }

        $form->sendToPlayer($player);
        return $form;
    }

    public function socialForm($player){
        $form = $this->createSimpleForm(function (Player $player, int $data = null){

            $result = $data;
            if ($result === null){
                return false;
            }

            switch ($result){
                case 0:
                    $player->sendMessage(TextFormat::AQUA . LangManager::getTranslatedMessage("comingsoon.text", $player));
                    break;
                case 1:
                    $player->getServer()->dispatchCommand($player, "party");
                    break;
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("socialform.name", $player));
        $form->addButton(LangManager::getTranslatedMessage("socialform.friend", $player));
        $form->addButton(LangManager::getTranslatedMessage("socialform.party", $player));

        $form->sendToPlayer($player);
        return $form;
    }

    public function cosmeticForm(Player $player){
        $form = $this->createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null){
                return false;
            }

            switch ($result){
                case 0:
                    $this->capeForm($player);
                    break;
                case 1:
                    $pform = new SimpleForm("Particles");
                    $pform->addButton(new Button("AngryVillager", null, function (Player $player){
                        $angryvillagerParticles = new Config($this->plugin->getDataFolder() . "particles/AngryVillager.yml", Config::YAML);
                        if ($angryvillagerParticles->exists($player->getXuid())){
                            $this->plugin->lobbyParticle[$player->getName()] = "AngryVillager";
                            $player->sendMessage("§aChanged your particle to §6AngryVillager");
                        } else{
                            $pnavForm = new ModalForm("Particle - AngryVillager ($1300)", "§cYou didn't own this particle\n\n§bDo you want to buy it now?");

                            $pnavForm->setAcceptText("Yes");
                            $pnavForm->setDenyText("No");

                            $pnavForm->setAcceptListener(function (Player $player){
                                $price = 1300;
                                $angryvillagerParticles = new Config($this->plugin->getDataFolder() . "particles/AngryVillager.yml", Config::YAML);
                                if (ZenAPI::getInstance()->getCoinManager()->getPlayerCoin($player) >= $price){
                                    ZenAPI::getInstance()->getCoinManager()->reducePlayerCoin($player, $price);
                                    $player->sendMessage("§aSuccessfully bought AngryVillager particle!");
                                    $angryvillagerParticles->set($player->getXuid(), true);
                                    $angryvillagerParticles->save();
                                } else{
                                    $player->sendMessage("§cYou dont have enough money!");
                                }
                            });

                            $pnavForm->setDenyListener(function (Player $player){

                            });

                            $player->sendForm($pnavForm);
                        }
                    }));

                    $pform->addButton(new Button("Water", null, function (Player $player){
                        $waterParticles = new Config($this->plugin->getDataFolder() . "particles/Water.yml", Config::YAML);
                        if ($waterParticles->exists($player->getXuid())){
                            $this->plugin->lobbyParticle[$player->getName()] = "Water";
                            $player->sendMessage("§aChanged your particle to §6Water");
                        } else{
                            $pnwForm = new ModalForm("Particle - Water ($1500)", "§cYou didn't own this particle\n\n§bDo you want to buy it now?");

                            $pnwForm->setAcceptText("Yes");
                            $pnwForm->setDenyText("No");

                            $pnwForm->setAcceptListener(function (Player $player){
                                $price = 1500;
                                $waterParticles = new Config($this->plugin->getDataFolder() . "particles/Water.yml", Config::YAML);
                                if (ZenAPI::getInstance()->getCoinManager()->getPlayerCoin($player) >= $price){
                                    ZenAPI::getInstance()->getCoinManager()->reducePlayerCoin($player, $price);
                                    $player->sendMessage("§aSuccessfully bought water particle!");
                                    $waterParticles->set($player->getXuid(), true);
                                    $waterParticles->save();
                                } else{
                                    $player->sendMessage("§cYou dont have enough money!");
                                }
                            });

                            $pnwForm->setDenyListener(function (Player $player){

                            });

                            $player->sendForm($pnwForm);
                        }
                    }));

                    $pform->addButton(new Button("Fire", null, function (Player $player){
                        $fireParticles = new Config($this->plugin->getDataFolder() . "particles/Fire.yml", Config::YAML);
                        if ($fireParticles->exists($player->getXuid())){
                            $this->plugin->lobbyParticle[$player->getName()] = "Fire";
                            $player->sendMessage("§aChanged your particle to §6Fire");
                        } else{
                            $pnfForm = new ModalForm("Particle - Fire ($2000)", "§cYou didn't own this particle\n\n§bDo you want to buy it now?");

                            $pnfForm->setAcceptText("Yes");
                            $pnfForm->setDenyText("No");

                            $pnfForm->setAcceptListener(function (Player $player){
                                $price = 2000;
                                $fireParticles = new Config($this->plugin->getDataFolder() . "particles/Fire.yml", Config::YAML);
                                if (ZenAPI::getInstance()->getCoinManager()->getPlayerCoin($player) >= $price){
                                    ZenAPI::getInstance()->getCoinManager()->reducePlayerCoin($player, $price);
                                    $player->sendMessage("§aSuccessfully bought fire particle!");
                                    $fireParticles->set($player->getXuid(), true);
                                    $fireParticles->save();
                                } else{
                                    $player->sendMessage("§cYou dont have enough money!");
                                }
                            });

                            $pnfForm->setDenyListener(function (Player $player){

                            });
                            $player->sendForm($pnfForm);
                        }
                    }));

                    $pform->addButton(new Button("Heart", null, function (Player $player){
                        $heartParticles = new Config($this->plugin->getDataFolder() . "particles/Heart.yml", Config::YAML);
                        if ($heartParticles->exists($player->getXuid())){
                            $this->plugin->lobbyParticle[$player->getName()] = "Heart";
                            $player->sendMessage("§aChanged your particle to §6Heart");
                        } else{
                            $pnhForm = new ModalForm("Particle - Heart ($2300)", "§cYou didn't own this particle\n\n§bDo you want to buy it now?");

                            $pnhForm->setAcceptText("Yes");
                            $pnhForm->setDenyText("No");

                            $pnhForm->setAcceptListener(function (Player $player){
                                $price = 2300;
                                $heartParticles = new Config($this->plugin->getDataFolder() . "particles/Heart.yml", Config::YAML);
                                if (ZenAPI::getInstance()->getCoinManager()->getPlayerCoin($player) >= $price){
                                    ZenAPI::getInstance()->getCoinManager()->reducePlayerCoin($player, $price);
                                    $player->sendMessage("§aSuccessfully bought heart particle!");
                                    $heartParticles->set($player->getXuid(), true);
                                    $heartParticles->save();
                                } else{
                                    $player->sendMessage("§cYou dont have enough money!");
                                }
                            });

                            $pnhForm->setDenyListener(function (Player $player){

                            });
                            $player->sendForm($pnhForm);
                        }
                    }));

                    $pform->addButton(new Button("Smoke", null, function (Player $player){
                        $smokeParticles = new Config($this->plugin->getDataFolder() . "particles/Smoke.yml", Config::YAML);
                        if ($smokeParticles->exists($player->getXuid())){
                            $this->plugin->lobbyParticle[$player->getName()] = "Smoke";
                            $player->sendMessage("§aChanged your particle to §6Smoke");
                        } else{
                            $pnsForm = new ModalForm("Particle - Smoke ($2500)", "§cYou didn't own this particle\n\n§bDo you want to buy it now?");

                            $pnsForm->setAcceptText("Yes");
                            $pnsForm->setDenyText("No");

                            $pnsForm->setAcceptListener(function (Player $player){
                                $price = 2500;
                                $smokeParticles = new Config($this->plugin->getDataFolder() . "particles/Smoke.yml", Config::YAML);
                                if (ZenAPI::getInstance()->getCoinManager()->getPlayerCoin($player) >= $price){
                                    ZenAPI::getInstance()->getCoinManager()->reducePlayerCoin($player, $price);
                                    $player->sendMessage("§aSuccessfully bought smoke particle!");
                                    $smokeParticles->set($player->getXuid(), true);
                                    $smokeParticles->save();
                                } else{
                                    $player->sendMessage("§cYou dont have enough money!");
                                }
                            });

                            $pnsForm->setDenyListener(function (Player $player){

                            });
                            $player->sendForm($pnsForm);
                        }
                    }));

                    $pform->addButton(new Button("Remove all particles", null, function (Player $player){
                        if (isset($this->plugin->lobbyParticle[$player->getName()])){
                            unset($this->plugin->lobbyParticle[$player->getName()]);
                            $player->sendMessage("§aRemoved all the particles");
                        } else{
                            $player->sendMessage("§cYou dont have any particles enabled");
                        }
                    }));

                    $player->sendForm($pform);
                    break;
                case 2:
                    $gform = new SimpleForm("Gadget");
                    $gform->addButton(new Button("TNT Launcher §7(§bPrime§6+§7)", null, function (Player $player){
                        if ($player->hasPermission("hyperiummc.primeplus")){
                            $player->getInventory()->setItem(2, ItemFactory::getInstance()->get(ItemIds::BLAZE_ROD)->setCustomName("§bTNT Launcher"));
                        } else{
                            $player->sendMessage("§c" . LangManager::getTranslatedMessage("noperms.highrank.text", $player));
                        }
                    }));
                    $gform->addButton(new Button("Leaper §7(§bPrime§7)", null, function (Player $player){
                        if ($player->hasPermission("hyperiummc.prime")){
                            $player->getInventory()->setItem(2, ItemFactory::getInstance()->get(ItemIds::FEATHER)->setCustomName("§bLeaper"));
                        } else{
                            $player->sendMessage("§c" . LangManager::getTranslatedMessage("noperms.highrank.text", $player));
                        }
                    }));
                    $gform->addButton(new Button("Remove all gadgets", null, function (Player $player){
                        $this->plugin->getItemManager()->giveItem($player);
                    }));

                    $player->sendForm($gform);
                    break;
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("cosmeticform.name", $player));

        $form->addButton(LangManager::getTranslatedMessage("cosmeticform.cape", $player));

        $form->addButton(LangManager::getTranslatedMessage("cosmeticform.particles", $player));
        $form->addButton(LangManager::getTranslatedMessage("cosmeticform.gadgets", $player));

        $form->sendToPlayer($player);
        return $form;
    }

    public function profileForm($player){
        $form = $this->createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null){
                return false;
            }

            switch ($result){
                case 0:
                    $this->usrProfileForm($player);
                    break;
                case 1:
                    $this->gameProfileForm($player);
                    break;
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("profileform.name", $player));
        $form->addButton(LangManager::getTranslatedMessage("profileform.user", $player));
        $form->addButton(LangManager::getTranslatedMessage("profileform.game", $player));

        $form->sendToPlayer($player);
        return $form;
    }

    public function usrProfileForm(Player $player){
        $form = $this->createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null){
                return false;
            }

            switch ($result){
                case 0:
                    break;
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("userprofile.name", $player));
        $zenapi = ZenAPI::getInstance();
        $form->setContent("§r" . LangManager::getTranslatedMessage("playername.text", $player) . ": §b{$player->getName()}\n\n§r" . LangManager::getTranslatedMessage("level.text", $player) . ": §b{$zenapi->getLevelManager()->getPlayerLevel($player)}\n\n§r" . LangManager::getTranslatedMessage("exp.text", $player) . ": §c{$zenapi->getLevelManager()->getPlayerEXP($player)}§e/§b{$zenapi->getLevelManager()->getExpNeed($player)}\n\n§r" . LangManager::getTranslatedMessage("rank.text", $player) . ": §b{$this->plugin->getPlayerRank($player)}\n\n§r" . LangManager::getTranslatedMessage("coins.text", $player) . ": §b{$zenapi->getCoinManager()->getPlayerCoin($player)}\n\n");
        $form->addButton(LangManager::getTranslatedMessage("close.text", $player));

        $form->sendToPlayer($player);
        return $form;
    }

    public function gameProfileForm(Player $player){
        $form = $this->createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null){
                return false;
            }

            switch ($result){
                case 0:
                    $player->getServer()->dispatchCommand($player, "sw stats");
                    break;
                case 1:
                    $player->getServer()->dispatchCommand($player, "mb stats");
                    break;
                case 2:
                    $player->getServer()->dispatchCommand($player, "hbws stats");
                    break;
                case 3:
                    $player->getServer()->dispatchCommand($player, "tb stats");
                    break;
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("gameprofile.name", $player));
        $form->addButton(LangManager::getTranslatedMessage("skywars.name", $player));
        $form->addButton(LangManager::getTranslatedMessage("mlgblock.name", $player));
        $form->addButton(LangManager::getTranslatedMessage("bedwars.text", $player) . "(" . LangManager::getTranslatedMessage("bedwars.solo.text", $player) . ")");
        $form->addButton(LangManager::getTranslatedMessage("thebridge.text", $player));

        $form->sendToPlayer($player);
        return $form;
    }

    public function staffForm($player){
        $form = $this->createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null){
                return false;
            }

            switch ($result){
                case 0:
                    $this->gamemodeForm($player);
                    break;
                case 1:
                    if (isset($this->plugin->staffvanish[$player->getName()])){
                        unset($this->plugin->staffvanish[$player->getName()]);
                        $player->setInvisible(false);
                    } else{
                        $this->plugin->staffvanish[$player->getName()] = $player;
                    }
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("stafftool.name", $player));
        $form->addButton(LangManager::getTranslatedMessage("gamemode.text", $player));

        if (isset($this->plugin->staffvanish[$player->getName()])){
            $form->addButton(LangManager::getTranslatedMessage("vanish.text", $player) . " \n §a" . LangManager::getTranslatedMessage("enabled.text", $player));
        } else{
            $form->addButton(LangManager::getTranslatedMessage("vanish.text", $player) . " \n §c" . LangManager::getTranslatedMessage("disabled.text", $player));
        }

        $form->sendToPlayer($player);
        return $form;
    }

    public function gamemodeForm($player){
        $form = $this->createSimpleForm(function (Player $player, int $data = null){
            $result = $data;
            if ($result === null){
                return false;
            }

            switch ($result){
                case 0:
                    $player->setGamemode(GameMode::SURVIVAL());
                    $player->sendMessage("§a" . LangManager::getTranslatedMessage("changedgamemodeto.text", $player) . LangManager::getTranslatedMessage("gamemodesurvival.text", $player));
                    break;
                case 1:
                    $player->setGamemode(GameMode::ADVENTURE());
                    $player->sendMessage("§a" . LangManager::getTranslatedMessage("changedgamemodeto.text", $player) . LangManager::getTranslatedMessage("gamemodeadventure.text", $player));
                    break;
                case 2:
                    $player->setGamemode(GameMode::CREATIVE());
                    $player->sendMessage("§a" . LangManager::getTranslatedMessage("changedgamemodeto.text", $player) . LangManager::getTranslatedMessage("gamemodecreative.text", $player));
                    break;
                case 3:
                    $player->setGamemode(GameMode::SPECTATOR());
                    $player->sendMessage("§a" . LangManager::getTranslatedMessage("changedgamemodeto.text", $player) . LangManager::getTranslatedMessage("gamemodespectator.text", $player));
                    $player->sendMessage("§6Use /gamemode to change your gamemode");
                    break;
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("gamemode.text", $player));
        $form->addButton(LangManager::getTranslatedMessage("gamemodesurvival.text", $player));
        $form->addButton(LangManager::getTranslatedMessage("gamemodeadventure.text", $player));
        $form->addButton(LangManager::getTranslatedMessage("gamemodecreative.text", $player));
        $form->addButton(LangManager::getTranslatedMessage("gamemodespectator.text", $player));

        $form->sendToPlayer($player);
        return $form;
    }

    public function nickForm(Player $player){
        $form = $this->createCustomForm(function (Player $player, $data){
            if ($data === null) return;

            if ($data[0] === null){
                $player->sendMessage("§cProvide a name!");
                return;
            }
            if (!in_array($data[0], ["test", "help", "idk", "empty", "android", "windows", "hyperiummc", "fuck", "sex", "suck"]) || strtolower($data[0]) !== strtolower($player->getName())) {
                $player->setDisplayName($data[0]);

                $this->plugin->nickedPlayer[$player->getName()] = $player;
                $this->plugin->nickedName[$player->getName()] = $data[0];

                $player->sendMessage("§aNicked your name to " . $data[0]);

                $player->sendMessage("§6Use /unnick to reset your nick");
            } else{
                $player->sendMessage("§cInvalid name! Please write another name!");
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("nicksystem.text", $player));

        $form->addInput("Name: ", $player->getName());

        $player->sendForm($form);
    }

    public function capeForm(Player $player) {
        $form = $this->createSimpleForm(function(Player $player, $data = null) {
            $result = $data;

            if(is_null($result)) {
                return true;
            }

            switch($result) {
                case 0:
                    $pdata = new Config($this->plugin->getDataFolder() . "capes/data.yml", Config::YAML);
                    $oldSkin = $player->getSkin();
                    $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), "", $oldSkin->getGeometryName(), $oldSkin->getGeometryData());

                    $player->setSkin($setCape);
                    $player->sendSkin();

                    if($pdata->get($player->getXuid()) !== null){
                        $pdata->remove($player->getXuid());
                        $pdata->save();
                    }

                    $player->sendMessage("§aRemoved your cape!");
                    break;
                case 1:
                    $this->capeListForm($player);
                    break;
            }
        });

        $form->setTitle(LangManager::getTranslatedMessage("cape.text", $player));
        $form->addButton(LangManager::getTranslatedMessage("removecape.text", $player));
        $form->addButton(LangManager::getTranslatedMessage("capeslist.text", $player));
        $player->sendForm($form);
    }

    public function capeListForm(Player $player) {
        $form = $this->createSimpleForm(function(Player $player, $data = null) {
            $result = $data;

            if(is_null($result)) {
                return true;
            }

            $cape = $data;
            $pdata = new Config($this->plugin->getDataFolder() . "capes/data.yml", Config::YAML);

            if(!file_exists($this->plugin->getDataFolder() . "capes/" . $data . ".png")) {
                $player->sendMessage("The chosen skin is not available!");
            } else {
                $oldSkin = $player->getSkin();
                $capeData = $this->plugin->createCape($cape);
                $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());

                $player->setSkin($setCape);
                $player->sendSkin();

                $player->sendMessage("§aChanged your cape to " . $cape);

                $pdata->set($player->getXuid(), $cape);
                $pdata->save();
            }
    });

        $form->setTitle("Capes List");

        foreach($this->plugin->getAllCapes() as $capes) {
            $form->addButton("$capes", -1, "", $capes);
        }

        $player->sendForm($form);
    }

    public function bedwarsForm(Player $player){
        $form = $this->createSimpleForm(function (Player $player, int$data =null){
            if (is_null($data))return;

            switch ($data){
                case 0:
                    $player->chat("/hbws random");
                    //$player->getServer()->dispatchCommand($player, "hbws random"); party issue
                    break;
            }
        });
        $form->setTitle(LangManager::getTranslatedMessage("bedwars.text", $player));
        $form->addButton(LangManager::getTranslatedMessage("bedwars.solo.text", $player));

        $player->sendForm($form);
    }

    public function reportForm(Player $player){
        $form = new CustomForm("Report");
        $p = new Dropdown("Select a player: ");
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $onlinePlayer){
            $p->addOption(new Option($onlinePlayer->getName(), $onlinePlayer->getName()));
        }
        $r = new Dropdown("Select a reason: ");
        foreach (["Cheating", "Cross Teaming", "Toxic", "Spam", "Abusing"] as $reason){
            $r->addOption(new Option($reason, $reason));
        }
        $form->addElement("players", $p);
        $form->addElement("reasons", $r);

        $form->setSubmitListener(function (Player $player, FormResponse $response){
            $username = $response->getDropdownSubmittedOptionId("players");
            $reason = $response->getDropdownSubmittedOptionId("reasons");

            if ($username === null) return;
            if ($reason === null) return;

            $target = $this->plugin->getServer()->getPlayerExact($username);
            if ($target === null){
                $player->sendMessage("§cThis player is not online!");
                return;
            }

            if ($target->getName() === $player->getName()){
                $player->sendMessage("§cYou cannot report yourself!");
                return;
            }

            $embed = new Embed("New Report", Embed::TYPE_RICH, "Player: {$target->getName()}\nReported By: {$player->getName()}\nReason: {$reason}", "", time(), 0xFFA500, new Footer("HyperiumMC Network"));
            $msg = new Message("935136906453319680", null, "", $embed);
            HyperiumCore::getDiscordAPI()->getApi()->sendMessage($msg);

            foreach ($this->plugin->getServer()->getOnlinePlayers() as $staff){
                if ($staff->hasPermission("hyperiummc.staff")){
                    $staff->sendMessage("§6§lNew Report\n§r§bPlayer: §a{$target->getName()} | §bReported By: §a{$player->getName()} | §bReason: §a{$reason}");
                }
            }

            $player->sendMessage("§aYour report has been sent to all the online staff");
            $player->getWorld()->addSound($player->getLocation()->asVector3(), new NoteSound(NoteInstrument::PIANO(), 2));
        });

        $player->sendForm($form);
    }
}
