<?php

namespace hyperiummc\core\event;

use JaxkDev\DiscordBot\Models\Activity;
use JaxkDev\DiscordBot\Models\Member;
use JaxkDev\DiscordBot\Plugin\Events\DiscordReady;
use JaxkDev\DiscordBot\Models\Messages\Webhook;
use JaxkDev\DiscordBot\Plugin\Events\MessageSent;
use JaxkDev\DiscordBot\Plugin\Storage;
use JaxkDev\DiscordBot\Models\Messages\Embed\Embed;
use JaxkDev\DiscordBot\Models\Messages\Embed\Footer;
use JaxkDev\DiscordBot\Models\Messages\Message;
use hyperiummc\core\entity\BedwarsNPCEntity;
use hyperiummc\core\entity\StatsEntity;
use hyperiummc\core\HyperiumCore;
use hyperiummc\core\item\HyperiumMCItemIds;
use hyperiummc\core\session\SessionManager;
use hyperiummc\core\task\FetchPlayerTimeZoneTask;
use hyperiummc\core\task\JoinTitleTask;
use hyperiummc\core\task\ScoreboardTask;
use hyperiummc\core\tile\Placeholder;
use hyperiummc\zenapi\ZenAPI;
use pocketmine\block\Anvil;
use pocketmine\block\Beacon;
use pocketmine\block\Chest;
use pocketmine\block\CraftingTable;
use pocketmine\block\Door;
use pocketmine\block\EnchantingTable;
use pocketmine\block\EnderChest;
use pocketmine\block\Trapdoor;
use pocketmine\block\WoodenDoor;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\mcpe\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\mcpe\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\mcpe\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\ToastRequestPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\player\Player;
use pocketmine\block\Furnace;
use pocketmine\player\GameMode;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\scheduler\ClosureTask;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\sound\BlazeShootSound;
use pocketmine\world\sound\EndermanTeleportSound;
use pocketmine\world\sound\FizzSound;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\sound\NoteSound;
use pocketmine\world\sound\XpLevelUpSound;

class EventsListener implements Listener{

    public static $autoSprint = [];
    public static $packSendQueue = [];


    public function __construct(HyperiumCore $plugin){
        $this->plugin = $plugin;
    }

    public function onDiscordReady(DiscordReady $event): void{
        //Update presence.
        $activity = new Activity(strval("play.hyperiummc.ml"), Activity::TYPE_PLAYING);
        HyperiumCore::getDiscordAPI()->getApi()->updateBotPresence($activity, Member::STATUS_IDLE);
    }

    public function onLogin(PlayerLoginEvent $event){
        $player = $event->getPlayer();
        SessionManager::createSession($player);

        $player->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn()->add(0.5, 1.5, 0.5));
        $this->plugin->getScheduler()->scheduleRepeatingTask(new ScoreboardTask($this->plugin), 80);

        //if($this->plugin->checkSkin($player)){
         //   $player->kick("§cInvalid skin!\n§bIf you think it is a bug, you can open a ticket in our official discord");
        //}
    }

    /**
     * @throws \JsonException
     */
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $event->setJoinMessage('');

        $zenapi = ZenAPI::getInstance();

        $player->getHungerManager()->setFood(20);
        $player->setGamemode(GameMode::ADVENTURE());
        $player->setHealth(20);
        $player->setMaxHealth(20);
        $player->getEffects()->clear();
        $player->setDisplayName("§r{$player->getNetworkSession()->getDisplayName()}");
        $player->getInventory()->setHeldItemIndex(0);

        //if ($this->plugin->isJohnnyWai($player)){
        //    $player->sendMessage("§6[HyperiumCore] §a已检测是johnnywai");
        //    $player->setDisplayName("§bI'm Dumb");
        //}

        $player->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn()->add(0.5, 1.5, 0.5));

        if (isset($this->plugin->nickedPlayer[$player->getName()])){
            $player->setNameTag("§7[§b{$this->plugin->getPlayerRank($player)}§7] §r{$this->plugin->nickedName[$player->getName()]}");
        } else {
            $player->setNameTag("§7[§b{$this->plugin->getPlayerRank($player)}§7] §r{$player->getNetworkSession()->getDisplayName()}");
        }

        $this->plugin->getItemManager()->giveItem($player);

        $this->plugin->shouldSendAnimatedTitle[$player->getName()] = $player;

        $player->getWorld()->addSound($player->getLocation()->asVector3(), new NoteSound(NoteInstrument::PIANO(), 18));

        $this->plugin->handleStatsEntity($player);

        $this->plugin->playerSkin[$player->getName()] = $player->getSkin();
        $playercape = new Config($this->plugin->getDataFolder() . "capes/data.yml", Config::YAML);

        if(file_exists($this->plugin->getDataFolder() . "capes/" . $playercape->get($player->getXuid()) . ".png")) {
            $oldSkin = $player->getSkin();
            $capeData = $this->plugin->createCape($playercape->get($player->getXuid()));
            $setCape = new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());

            $player->setSkin($setCape);
            $player->sendSkin();
        } else {
            $playercape->remove($player->getXuid());
            $playercape->save();
        }

        $pk = ToastRequestPacket::create("§6HyperiumMC", "§bWelcome To §6HyperiumMC Network!");
        $player->getNetworkSession()->sendDataPacket($pk);

        $embed = new Embed("", Embed::TYPE_RICH, $player->getName() . " joined the server", null, time(), 0x51FF00, new Footer("HyperiumMC Network"), null, null, null, null, []);

        $message = new Message("1005759118688141393", null, "", $embed);
        HyperiumCore::getDiscordAPI()->getApi()->sendMessage($message);

        //$levelevent = LevelEventPacket::create(LevelEvent::START_RAIN, mt_rand(90000, 110000), null);
        //$player->getNetworkSession()->sendDataPacket($levelevent);

    }

    /**
     * @throws \Exception
     */
    public function onInteract(PlayerItemUseEvent $event){
        $player = $event->getPlayer();
        $item = $event->getItem();

            if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()) {
                if ($item->getId() === ItemIds::MAGMA_CREAM && $item->hasCustomName()){
                    $this->plugin->getFormManager()->travelForm($player);
                } elseif ($item->getId() === ItemIds::EMERALD && $item->hasCustomName() && $player->hasPermission("hyperiummc.staff")){
                    $this->plugin->getFormManager()->staffForm($player);
                } elseif ($item->getId() === ItemIds::PAPER && $item->hasCustomName()){
                    $this->plugin->getFormManager()->socialForm($player);
                } elseif ($item->getId() === ItemIds::SLIMEBALL && $item->hasCustomName()){
                    $this->plugin->getFormManager()->reportForm($player);
                } elseif ($item->getId() === ItemIds::DIAMOND && $item->hasCustomName()){
                    $this->plugin->getFormManager()->cosmeticForm($player);
                } elseif ($item->getId() === ItemIds::ENCHANTED_BOOK && $item->hasCustomName()){
                    $this->plugin->getFormManager()->profileForm($player);
                } elseif ($item->getId() === ItemIds::COMPARATOR && $item->hasCustomName()){
                    $this->plugin->getFormManager()->settingForm($player);
                } elseif ($item->getId() === ItemIds::BLAZE_ROD && $item->hasCustomName()){
                    if (isset($this->plugin->gadgetcooldown[$player->getName()])){
                        $player->sendMessage("§cYou must wait {$this->plugin->gadgetcooldown[$player->getName()]}s to use this again");
                        $event->cancel();
                    }

                    if (!isset($this->plugin->gadgetcooldown[$player->getName()]) || $this->plugin->gadgetcooldown[$player->getName()] == 0){
                        $tnt = new PrimedTNT(new Location($player->getLocation()->getX(), $player->getLocation()->getY() + $player->getEyeHeight(), $player->getLocation()->getZ(), $player->getWorld(), 0, 0));
                        $tnt->setMotion(new Vector3(-sin($player->getLocation()->yaw / 180 * M_PI) * cos($player->getLocation()->pitch / 180 * M_PI), -sin($player->getLocation()->pitch / 180 * M_PI), cos($player->getLocation()->yaw / 180 * M_PI) * cos($player->getLocation()->pitch / 180 * M_PI)));
                        //$tnt->setRotation($player->getLocation()->yaw, $player->getLocation()->pitch);

                        $tnt->setMotion($tnt->getMotion() != 0 ? $tnt->getMotion()->multiply(2) : 4);
                        $tnt->spawnToAll();
                        $player->getWorld()->addSound($player->getLocation()->asVector3(), new FizzSound(0));
                        $this->plugin->gadgetcooldown[$player->getName()] = 3;
                    }
                } elseif ($item->getId() === ItemIds::FEATHER && $item->hasCustomName()){
                    if (isset($this->plugin->gadgetcooldown[$player->getName()])){
                        $player->sendMessage("§cYou must wait {$this->plugin->gadgetcooldown[$player->getName()]}s to use this again");
                        $event->cancel();
                    }

                    if (!isset($this->plugin->gadgetcooldown[$player->getName()]) || $this->plugin->gadgetcooldown[$player->getName()] == 0){
                        $distance = 5;

                        $motFlat = $player->getDirectionPlane()->normalize()->multiply($distance * 3.75 / 20);

                        $mot = new Vector3($motFlat->x, 1.5, $motFlat->y);

                        $player->setMotion($mot);

                        $player->getWorld()->addSound($player->getLocation()->asVector3(), new BlazeShootSound());

                        $player->sendPopup("§aLeap!");
                        $this->plugin->gadgetcooldown[$player->getName()] = 3;
                    }
                }
            }
    }

    public function onLobbyExplode(ExplosionPrimeEvent $event){
        if ($event->getEntity()->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
            $event->setBlockBreaking(false);
        }
    }

    public function onBlockOpen(PlayerInteractEvent $event){
        $block = $event->getBlock();
        $player = $event->getPlayer();

        if ($player->getWorld() == $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
            if ($event->getAction() == $event::RIGHT_CLICK_BLOCK){
                if ($block instanceof Chest || $block instanceof EnchantingTable || $block instanceof CraftingTable || $block instanceof Anvil || $block instanceof Beacon || $block instanceof Door || $block instanceof Trapdoor || $block instanceof EnderChest || $block instanceof Furnace){
                    $event->cancel();
                }
            }
        }
    }

    public function onLevelChange(EntityTeleportEvent $event){
        $entity = $event->getEntity();
        if ($entity instanceof Player){
            if ($entity->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
                if ($event->getTo() !== $entity->getWorld()) {
                    $scoreboard = ZenAPI::getInstance()->getScoreboardAPI();
                    $scoreboard->remove($entity);
                    $entity->setMaxHealth(20);
                    $entity->setHealth(20);
                    $entity->setAllowFlight(false);
                    $entity->setFlying(false);

                    if ($entity->isInvisible()) {
                        $entity->setInvisible(false);
                    }

                    if ($entity->getGamemode() === GameMode::CREATIVE()) {
                        $entity->setAllowFlight(true);
                        $entity->setFlying(true);
                    }
                }
            }

            $this->plugin->handleStatsEntity($entity);

            if ($event->getFrom()->getWorld() !== $event->getTo()->getWorld()) {
                $entity->getCraftingGrid()->clearAll();

                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    $entity->showPlayer($player);
                }

                $this->plugin->prepareChunk($event->getTo()->getWorld(), $event->getTo());
                
                if (!$event->getTo()->getWorld()->isLoaded()){
                    $this->plugin->getServer()->getWorldManager()->loadWorld($event->getTo()->getWorld()->getFolderName());
                }
            }
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        $event->setQuitMessage('');
        SessionManager::removeSession($event->getPlayer());

        $embed = new Embed("", Embed::TYPE_RICH, $event->getPlayer()->getName() . " left the server", null, time(), 0xFF0000, new Footer("HyperiumMC Network"), null, null, null, null, []);

        $message = new Message("1005759118688141393", null, "", $embed);

        HyperiumCore::getDiscordAPI()->getApi()->sendMessage($message);
    }

    public function onDrop(PlayerDropItemEvent $event){
        $player = $event->getPlayer();
        $item = $event->getItem();

        if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld() && !$player->hasPermission("hyperiummc.staff")){
            $event->cancel();
        }
    }

    public function onPickup(EntityItemPickupEvent $event){
        $entity = $event->getEntity();
        $inventory = $event->getInventory();

        if ($inventory instanceof PlayerInventory){
            if ($entity->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld() && !$entity->hasPermission("hyperiummc.staff")){
                $event->cancel();
            }
        }
    }

    public function onChat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        $msg = $event->getMessage();

        //if ($this->plugin->isJohnnyWai($player)){
        //    $event->setMessage("§7Blocked a message from a spammer");
        //    $player->sendMessage("§e你说你有说话的资格吗?笑死");

        //    return;
        //}

        if (isset($this->plugin->chatcooldown[$player->getName()])){
            $event->cancel();
            $player->sendMessage("§cYou must wait {$this->plugin->chatcooldown[$player->getName()]}s to send message again!");
        }

        if (!isset($this->plugin->chatcooldown[$player->getName()]) || $this->plugin->chatcooldown[$player->getName()] == 0){
            $this->plugin->chatcooldown[$player->getName()] = 5;
            $embed = new Embed($player->getName(), Embed::TYPE_RICH, $event->getMessage(), null, time(), 0xFFC500, new Footer("HyperiumMC Network"), null, null, null, null, []);
            $message = new Message("1005759118688141393", null, "", $embed);
            HyperiumCore::getDiscordAPI()->getApi()->sendMessage($message);
        }

        if($player->getWorld() == $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
            $event->setFormat("§7[§b{$this->plugin->getPlayerRank($player)}§7] §r{$player->getDisplayName()} ⨞§r {$msg}");
        }

        if (str_contains($event->getMessage(), "@")) {
            foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                $pos = $player->getPosition();
                if (str_contains($event->getMessage(), "@{$player->getDisplayName()}")) {
                    $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
                        "random.orb",
                        $pos->x, $pos->y, $pos->z,
                        1.0, 1.0
                    ));
                }
                elseif (str_contains($event->getMessage(), "@here")) {
                    $player->getNetworkSession()->sendDataPacket(PlaySoundPacket::create(
                        "random.orb",
                        $pos->x, $pos->y, $pos->z,
                        1.0, 1.0
                    ));
                }
            }
        }
    }

    public function onMove(PlayerMoveEvent $event){
        $player = $event->getPlayer();
      
        $this->plugin->shouldSendTitleMore[$player->getName()] = $player;

        if (isset($this->plugin->staffvanish[$player->getName()])){
            if (!$player->isInvisible()){
                $player->setInvisible(true);
            }
        }

        if (isset($this->plugin->shouldSendAnimatedTitle[$player->getName()]) && isset($this->plugin->shouldSendTitleMore[$player->getName()])){
            unset($this->plugin->shouldSendTitleMore[$player->getName()]);
            //$player->sendTitle("§6§1", "", 3, 12, 2);
            $this->plugin->getScheduler()->scheduleRepeatingTask(new JoinTitleTask($player, $this->plugin), 5);
        }

        if ($event->getFrom()->distance($event->getTo()) < 0.1) {
            return;
        }

        foreach ($player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy(8, 8, 8), $player) as $entity) {
            if ($entity instanceof StatsEntity) {
                $angle = atan2($player->getLocation()->z - $entity->getLocation()->z, $player->getLocation()->x - $entity->getLocation()->x);
                $yaw = (($angle * 180) / M_PI) - 90;
                $angle = atan2((new Vector2($entity->getLocation()->x, $entity->getLocation()->z))->distance(new Vector2($player->getLocation()->x, $player->getLocation()->z)), $player->getLocation()->y - $entity->getLocation()->y);
                $pitch = (($angle * 180) / M_PI) - 90;

                if ($entity instanceof StatsEntity) {
                    $pk = new MovePlayerPacket();
                    $pk->actorRuntimeId = $entity->getId();
                    $pk->position = $entity->getLocation()->add(0, $entity->getEyeHeight(), 0);
                    $pk->yaw = $yaw;
                    $pk->pitch = $pitch;
                    $pk->headYaw = $yaw;
                    $pk->onGround = $entity->onGround;
                    $player->getNetworkSession()->sendDataPacket($pk);
                }
            }
        }
    }

    public function onExhaust(PlayerExhaustEvent $event){
        $player = $event->getPlayer();

        if ($player->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()){
            $event->cancel();
        }
    }

    public function onTransaction(InventoryTransactionEvent $event){
            $transaction = $event->getTransaction();
            foreach ($transaction->getActions() as $action){
                $sources = $transaction->getSource();

                if ($sources instanceof Player) {
                    if ($sources->getWorld() === $this->plugin->getServer()->getWorldManager()->getDefaultWorld()) {
                        if ($action instanceof SlotChangeAction) {
                            if ($action->getInventory() instanceof PlayerInventory) {
                                if (!$sources->hasPermission("hyperiummc.staff")) {
                                    $event->cancel();
                                }
                            }
                        }
                    }
                }
            }
    }

    public function onSkinChange(PlayerChangeSkinEvent $event){
        $player = $event->getPlayer();

        $this->plugin->handleStatsEntity($player);

        $this->plugin->playerSkin[$player->getName()] = $player->getSkin();

        //if ($this->plugin->checkSkin($player, $event->getNewSkin()->getSkinData())){
        //    $player->kick("§cInvalid skin!\n§bIf you think it is a bug, you can open a ticket in our official discord");
        //}
    }

    public function onQueryServer(QueryRegenerateEvent $event){
        $event->getQueryInfo()->setServerName("§6HyperiumMC");
        $event->getQueryInfo()->setMaxPlayerCount(count($this->plugin->getServer()->getOnlinePlayers()) + 1);
        $event->getQueryInfo()->setPlugins([$this->plugin->getServer()->getPluginManager()->getPlugin("HyperiumCore")]);
    }

    public function onDataPacketSend(DataPacketSendEvent $event){
        $pks = $event->getPackets();
        $targets = $event->getTargets();

        foreach ($pks as $pk){
            if ($pk instanceof AvailableCommandsPacket){
                foreach ($targets as $target){
                    if ($target->getPlayer() !== null){
                        if (!$target->getPlayer()->hasPermission("hyperiummc.staff")){
                            $pk->commandData = array_intersect_key($pk->commandData, ["hub", "lobby", "party", "friend", "report", "lang", "nick", "bedwars", "bw", "vote"]);
                        }
                    }
                }
            } elseif ($pk->pid() == LevelSoundEventPacket::NETWORK_ID) {
                /** @var LevelSoundEventPacket $pk */
                if($pk->sound === LevelSoundEvent::ATTACK) $event->cancel();
                elseif($pk->sound === LevelSoundEvent::ATTACK_NODAMAGE) $event->cancel();
                elseif($pk->sound === LevelSoundEvent::ATTACK_STRONG) $event->cancel();
            }
        }
    }

    public function onCreation(PlayerCreationEvent $event){
        $namedtag = $this->plugin->getServer()->getOfflinePlayerData($event->getNetworkSession()->getPlayerInfo()->getUsername());
        $worldManager = $this->plugin->getServer()->getWorldManager();

        if($namedtag !== null && ($world = $worldManager->getWorldByName($namedtag->getString("Level", ""))) !== null){
            $vec = EntityDataHelper::parseVec3($namedtag, "Pos", false);
        }else{
            $world = $worldManager->getDefaultWorld();

            //Prevents an exception thrown when a get safe spawn an ungenerated world
            $this->plugin->prepareChunk($world, $world->getSpawnLocation());
            $vec = $world->getSafeSpawn();
        }
        $this->plugin->prepareChunk($world, $vec);
    }

    public function onCommandExecute(PlayerCommandPreprocessEvent $event) {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $msg = explode(' ', trim($message));
        $m = substr("$message", 0, 1);
        $whitespace_check = substr($message, 1, 1);
        $slash_check = substr($msg[0], -1, 1);
        $quote_mark_check = substr($message, 1, 1) . substr($message, -1, 1);

        if ($m == '/') {
            if ($whitespace_check === ' ' or $whitespace_check === '\\' or $slash_check === '\\' or $quote_mark_check === '""') {
                $event->cancel();
            }
        }
    }

    public function onCommandProcess(PlayerCommandPreprocessEvent $event){
        $player = $event->getPlayer();
        $command = str_replace("/", "", $event->getMessage());

        if(in_array($command, ["report", "reports"])){
            if ($player->getName() == "johnnywai666"){
                $event->cancel();
                $player->sendMessage("你说你配吗?");
            }
        }
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event){
        $pk = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();
        $origin = $event->getOrigin();
        if($pk instanceof InventoryTransactionPacket){
            if($pk->trData instanceof UseItemOnEntityTransactionData){
                if($pk->trData->getActionType() == UseItemOnEntityTransactionData::ACTION_ATTACK){
                    if($player->isSpectator() || $player->getGamemode()->equals(GameMode::SPECTATOR())){
                        $event->cancel();
                    }
                }
            }
        }
            if(!$player instanceof Player) return;

		if ($pk instanceof ResourcePackClientResponsePacket) {
			if ($pk->status === ResourcePackClientResponsePacket::STATUS_SEND_PACKS) {
				$event->cancel();

				$manager = $this->getServer()->getResourcePackManager();

				$playerName = $player?->getName() ?? "Null";
				self::$packSendQueue[$playerName] = $entry = new PackSendEntry($player);
				$entry->setSendInterval(30);

				foreach ($pk->packIds as $uuid) {
					//dirty hack for mojang's dirty hack for versions
					$splitPos = strpos($uuid, "_");
					if ($splitPos !== false) {
						$uuid = substr($uuid, 0, $splitPos);
					}

					$pack = $manager->getPackById($uuid);
					if (!($pack instanceof ResourcePack)) {
						//Client requested a resource pack but we don't have it available on the server
						$player->kick("", "disconnectionScreen.resourcePack", true);
						$this->plugin->getServer()->getLogger()->debug("Got a resource pack request for unknown pack with UUID " . $uuid . ", available packs: " . implode(", ", $manager->getPackIdList()));

						return false;
					}

					$pk1 = new ResourcePackDataInfoPacket();
					$pk1->packId = $pack->getPackId();
					$pk1->maxChunkSize = 524288;
					$pk1->chunkCount = (int) ceil($pack->getPackSize() / $pk1->maxChunkSize);
					$pk1->compressedPackSize = $pack->getPackSize();
					$pk1->sha256 = $pack->getSha256();
					$player->getNetworkSession()->sendDataPacket($pk1);

					for ($i = 0; $i < $pk->chunkCount; $i++) {
						$pk2 = new ResourcePackChunkDataPacket();
						$pk2->packId = $pack->getPackId();
						$pk2->chunkIndex = $i;
						$pk2->data = $pack->getPackChunk($pk1->maxChunkSize * $i, $pk1->maxChunkSize);
						$pk2->progress = ($pk1->maxChunkSize * $i);

						$entry->addPacket($pk2);
					}
				}
			}
		} elseif ($pk instanceof ResourcePackChunkRequestPacket) {
			$event->cancel(); // dont rely on client
		}
    }

    public function onDataPacketReceive2(DataPacketReceiveEvent $event){
        $player = $event->getOrigin()->getPlayer();
        $packet = $event->getPacket();

        switch ($packet->pid()){
            case PlayerAuthInputPacket::NETWORK_ID:
                $autoSprint = new Config($this->plugin->getDataFolder() . "settings/AutoSprint.yml", Config::YAML);

                if ($autoSprint->exists($player->getXuid())){
                    if($player->isSprinting() && $packet->hasFlag(PlayerAuthInputFlags::DOWN)){
                        $player->setSprinting(false);
                    }elseif(!$player->isSprinting() && $packet->hasFlag(PlayerAuthInputFlags::UP)){
                        $player->setSprinting();
                    }
                }
                break;
            case AnimatePacket::NETWORK_ID:
                /** @var AnimatePacket $packet */
                if($packet->action === AnimatePacket::ACTION_SWING_ARM){
                    $event->cancel();
                    $player->getServer()->broadcastPackets($player->getViewers(), [$packet]);
                }
                break;
            case InventoryTransactionPacket::NETWORK_ID:
                if($packet->trData->getTypeId() == InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
                    $trData = $packet->trData;
                    if ($trData->getActionType() == UseItemOnEntityTransactionData::ACTION_ATTACK){
                        $entityId = $trData->getActorRuntimeId();
                        $hitParticles = new Config($this->plugin->getDataFolder() . "settings/HitParticles.yml", Config::YAML);

                        if ($hitParticles->exists($player->getXuid())){
                            $player->getServer()->broadcastPackets([$player], [AnimatePacket::create($entityId, AnimatePacket::ACTION_CRITICAL_HIT)]);
                        }
                    }
                }
        }
    }

    public function packetReceive(DataPacketReceiveEvent $e) : void{
        $cpsPopup = new Config($this->plugin->getDataFolder() . "settings/CPSPopup.yml", Config::YAML);

        if ($cpsPopup->exists($e->getOrigin()->getPlayer()->getXuid())) {
            if (
                ($e->getPacket()::NETWORK_ID === InventoryTransactionPacket::NETWORK_ID && $e->getPacket()->trData instanceof UseItemOnEntityTransactionData) ||
                ($e->getPacket()::NETWORK_ID === LevelSoundEventPacket::NETWORK_ID && $e->getPacket()->sound === LevelSoundEvent::ATTACK_NODAMAGE) ||
                ($e->getPacket()::NETWORK_ID === PlayerActionPacket::NETWORK_ID && $e->getPacket()->action === PlayerAction::START_BREAK)
            ) {
                $this->plugin->addCPS($e->getOrigin()->getPlayer());
                $e->getOrigin()->getPlayer()->sendActionBarMessage("§b{$this->plugin->getCPS($e->getOrigin()->getPlayer())} §6CPS");
            }
        }
    }

    public function onChunkLoad(ChunkLoadEvent $event): void{
        $world = $event->getWorld();
        foreach($event->getChunk()->getTiles() as $tile){
            if(!$tile instanceof Placeholder){
                continue;
            }
            $pos = $tile->getPosition();
            $world->setBlockAt($pos->x, $pos->y, $pos->z, $tile->getExtendedBlock(), false);
            $tile->close();
        }
    }

    //public function onLoginPacket (DataPacketReceiveEvent $ev) {
    //	$pk = $ev->getPacket();
    //	if ($pk instanceof LoginPacket) {
    //		if (in_array($pk->protocol, [431, 440, 448, 465, 471, 475, 486, 503, 527, 512])) {
    //			$pk->protocol = ProtocolInfo::CURRENT_PROTOCOL;
    //		}
    //	}
    //}

    public function onDiscordMessage(MessageSent $event): void{

        if(($msg = $event->getMessage()) instanceof Webhook){
            $this->plugin->getLogger()->debug("Ignoring message '{$msg->getId()}', Sent via webhook.");
            return;
        }

        $channel = Storage::getChannel($msg->getChannelId());
        if($channel === null){
            //shouldn't happen, but can.
            $this->plugin->getLogger()->warning("Failed to process discord message event, channel '{$msg->getChannelId()}' does not exist in local storage.");
            return;
        }
        if(!in_array($channel->getId()??"Will never be null", ["1005759118688141393"])){
            $this->plugin->getLogger()->debug("Ignoring message from channel '{$channel->getId()}', ID is not in list.");
            return;
        }

        $server_id = $msg->getServerId();
        if($server_id === null){
            //DM Channel.
            $this->plugin->getLogger()->debug("Ignoring message '{$msg->getId()}', Sent via DM to bot.");
            return;
        }
        $server = Storage::getServer($server_id);
        if($server === null){
            //shouldn't happen, but can.
            $this->plugin->getLogger()->warning("Failed to process discord message event, server '{$msg->getServerId()}' does not exist in local storage.");
            return;
        }
        $member = Storage::getMember($msg->getAuthorId()??""); //Member is not required, but preferred.
        $user_id = (($member?->getUserId()) ?? (explode(".", $msg->getAuthorId()?? "na.na")[1]));
        $user = Storage::getUser($user_id);
        if($user === null){
            //shouldn't happen, but can.
            $this->plugin->getLogger()->warning("Failed to process discord message event, author user '$user_id' does not exist in local storage.");
            return;
        }
        $content = trim($msg->getContent());
        if(strlen($content) === 0){
            //Files or other type of messages.
            $this->plugin->getLogger()->debug("Ignoring message '{$msg->getId()}', No text content.");
            return;
        }

        $name = $member?->getNickName() ?? $user->getUsername();

        //Broadcast.
        $this->plugin->getServer()->broadcastMessage("§5[Discord] §7" . $name . "#" . $user->getDiscriminator() . ": §f" . $content);
    }
}

class PackSendEntry {
	/** @var DataPacket[] */
	protected $packets = [];
	/** @var int */
	protected $sendInterval = 30;
	/** @var Player */
	protected $player;

	public function __construct(Player $player) {
		$this->player = $player;
	}

	public function addPacket(DataPacket $packet): void {
		$this->packets[] = $packet;
	}

	public function setSendInterval(int $value): void {
		$this->sendInterval = $value;
	}

	public function tick(int $tick): void {
		if (!$this->player->isConnected()) {
			unset(EventsListener::$packSendQueue[$this->player->getName()]);
			return;
		}

		if (($tick % $this->sendInterval) === 0) {
			if ($next = array_shift($this->packets)) {
				$this->player->getNetworkSession()->sendDataPacket($next);
			} else {
				unset(EventsListener::$packSendQueue[$this->player->getName()]);
			}
		}
	}
}
