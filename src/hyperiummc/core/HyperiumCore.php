<?php

namespace hyperiummc\core;

use _64FF00\PurePerms\PurePerms;
use hyperiummc\core\commands\AnnounceCommand;
use hyperiummc\core\commands\BWCommand;
use hyperiummc\core\commands\CoreCommands;
use hyperiummc\core\commands\HubCommand;
use hyperiummc\core\commands\NickCommand;
use hyperiummc\core\commands\PartyCommand;
use hyperiummc\core\commands\PracticeCommand;
use hyperiummc\core\commands\ReportCommand;
use hyperiummc\core\commands\StaffchatCommand;
use hyperiummc\core\commands\UnnickCommand;
use hyperiummc\core\entity\BedwarsNPCEntity;
use hyperiummc\core\entity\StatsEntity;
use hyperiummc\core\event\EventsListener;
use hyperiummc\core\event\PartyEvents;
use hyperiummc\core\event\PlayerEvents;
use hyperiummc\core\event\SeparateChatEvent;
use hyperiummc\core\libs\muqsit\simplepackethandler\interceptor\IPacketInterceptor;
use hyperiummc\core\libs\muqsit\simplepackethandler\SimplePacketHandler;
use hyperiummc\core\manager\FormManager;
use hyperiummc\core\manager\ItemManager;
use hyperiummc\core\nbs\NBSFile;
use hyperiummc\core\nbs\Song;
use hyperiummc\core\skin\PersonaSkinAdapter;
use hyperiummc\core\task\AutoBroadcastTask;
use hyperiummc\core\task\CosmeticsTask;
use hyperiummc\core\task\LobbyTask;
use hyperiummc\core\task\MOTDTask;
use hyperiummc\core\task\SettingsTask;
use hyperiummc\core\task\StatsEntityTask;
use hyperiummc\core\tile\Placeholder;
use hyperiummc\core\utils\Converter;
use hyperiummc\zenapi\system\rank\RankManager;
use JaxkDev\DiscordBot\Plugin\Main as DiscordBot;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Spawnable;
use pocketmine\block\tile\TileFactory;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\Command;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\entity\Skin;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\convert\SkinAdapter;
use pocketmine\network\mcpe\convert\SkinAdapterSingleton;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\BiomeArray;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;
use pocketmine\world\sound\NoteInstrument;
use pocketmine\world\World;
use hyperiummc\core\task\MusicPlayerTask;
use hyperiummc\core\commands\MusicCommand;
use SebastianBergmann\CodeCoverage\Report\Text;


class HyperiumCore extends PluginBase implements Listener {

    /** @var FormManager */
    public $formManager = null;

    /** @var ItemManager */
    public $itemManager = null;

    /** @var PurePerms */
    public $purePerms = null;

    public $allowBuilding = [];

    public array $staffvanish;

    public array $nickedPlayer;
    public array $nickedName;

    private IPacketInterceptor $handler;

    private \Closure $handleBlockActor;
    private \Closure $handleUpdateBlock;

    private ?Player $lastPlayer = null;

    private array $oldBlockIds;
    private array $oldTilesSerializedCompound;

    private ?SkinAdapter $originalAdaptor = null;

    private Chunk $tempChunk;

    public array $playerSkin;

    public static array $skins;

    public array $encryptionKeys;

    public array $servername;

    public array $shouldSendAnimatedTitle;

    public array $shouldSendTitleMore;

    public array $playerTimeZone;

    public array $chatcooldown;

    public static $bwnpcedit = true;

    public static $bwnpcleft = true;

    /** @var DiscordBot  */
    public static $discordapi;

    public array $lobbyParticle;

    public array $gadgetcooldown;

    public bool $timeshouldblink = false;

    public array $cps;

    public const SKIN_WIDTH_MAP = [
        64 * 32 * 4   => 64,
        64 * 64 * 4   => 64,
        128 * 128 * 4 => 128,
    ];
    public const SKIN_HEIGHT_MAP = [
        64 * 32 * 4   => 32,
        64 * 64 * 4   => 64,
        128 * 128 * 4 => 128,
    ];


    public function onLoad(): void
    {
        $subchunks = [];
        for($y = 0; $y < Chunk::MAX_SUBCHUNKS; ++$y){
            $subchunks[$y] = new SubChunk(BlockLegacyIds::INVISIBLE_BEDROCK << 4, []);
        }

        $this->tempChunk = new Chunk($subchunks, BiomeArray::fill(5), false);
        $this->tempChunk->setFullBlock(0, 0, 0, VanillaBlocks::INVISIBLE_BEDROCK()->getId());
        $this->tempChunk->setPopulated();

        TileFactory::getInstance()->register(Placeholder::class);

        self::$discordapi = $this->getServer()->getPluginManager()->getPlugin("DiscordBot");
    }

    /**
     * @throws \ReflectionException
     */
    public function onEnable(): void
    {
        $this->getLogger()->info(TextFormat::YELLOW . "----------------------------------------");
        $this->getLogger()->info(TextFormat::GOLD . "HyperiumCore");
        $this->getLogger()->info(TextFormat::GOLD . "Version: " . TextFormat::GREEN . "2.1.1");
        $this->getLogger()->info(TextFormat::GOLD . "Enabling System...");
        $this->getLogger()->info(TextFormat::YELLOW . "----------------------------------------");

        $this->servername["HyperiumMC"] = $this->getName();

        $this->getScheduler()->scheduleRepeatingTask(new MOTDTask($this), 100);
        $this->getScheduler()->scheduleRepeatingTask(new CosmeticsTask($this), 40);
        $this->getScheduler()->scheduleRepeatingTask(new SettingsTask($this), 40);
        $this->getScheduler()->scheduleRepeatingTask(new StatsEntityTask($this), 20*30);
        $this->getScheduler()->scheduleRepeatingTask(new LobbyTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new AutoBroadcastTask($this), 12*200);

        $this->getServer()->getPluginManager()->registerEvents(new EventsListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerEvents($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new SeparateChatEvent(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PartyEvents(), $this);

        $this->getServer()->getCommandMap()->register('hub', new HubCommand($this));
        $this->getServer()->getCommandMap()->register('core', new CoreCommands($this));
        $this->getServer()->getCommandMap()->register('announce', new AnnounceCommand($this));
        $this->getServer()->getCommandMap()->register('staffchat', new StaffchatCommand($this));
        $this->getServer()->getCommandMap()->register('nick', new NickCommand($this));
        $this->getServer()->getCommandMap()->register('bw', new BWCommand($this));
        $this->getServer()->getCommandMap()->register('party', new PartyCommand());
        $this->getServer()->getCommandMap()->register('unnick', new UnnickCommand($this));
        $this->getServer()->getCommandMap()->register('report', new ReportCommand($this));
        $this->getServer()->getCommandMap()->register('practice', new PracticeCommand($this));

        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("about"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("defaultgamemode"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("dumpmemory"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("list"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("me"));
        //$this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("pardon"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("seed"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("timings"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("transferserver"));
        $this->getServer()->getCommandMap()->unregister($this->getServer()->getCommandMap()->getCommand("help"));

        EntityFactory::getInstance()->register(StatsEntity::class, function (World $world, CompoundTag $nbt): StatsEntity{
            return new StatsEntity(EntityDataHelper::parseLocation($nbt, $world), StatsEntity::parseSkinNBT($nbt));
        }, ["hyperiummc:statsentity", "statsentity"]);
        EntityFactory::getInstance()->register(BedwarsNPCEntity::class, function (World $world, CompoundTag $nbt): BedwarsNPCEntity{
            return new BedwarsNPCEntity(EntityDataHelper::parseLocation($nbt, $world), BedwarsNPCEntity::parseSkinNBT($nbt));
        }, ["hyperiummc.bedwarsnpc", "bedwarsnpc"]);

        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "settings");
        @mkdir($this->getDataFolder() . "cosmetics");
        @mkdir($this->getDataFolder() . "capes");
        @mkdir($this->getDataFolder() . "personaskin");
        @mkdir($this->getDataFolder() . "npc");
        @mkdir($this->getDataFolder() . "particles");

        $this->saveDefaultConfig();

        $bedwarspng = "npc/bedwars.png";
        $bedwarsjson = "npc/bedwars.json";
        foreach ([$bedwarspng, $bedwarsjson] as $bedwars){
            $this->saveResource($bedwars);
        }

        $bluecreeper = "capes/Blue Creeper.png";
        $enderman = "capes/Enderman.png";
        $fire = "capes/Fire.png";
        $firework = "capes/Firework.png";
        $redcreeper = "capes/Red Creeper.png";
        foreach ([$bluecreeper, $enderman, $fire, $firework, $redcreeper] as $cape){
            $this->saveResource($cape);
        }

        $skin1 = "personaskin/skin1.png";
        $skin2 = "personaskin/skin2.png";
        $skin3 = "personaskin/skin3.png";
        $skin4 = "personaskin/skin4.png";
        $skin5 = "personaskin/skin5.png";
        $skin6 = "personaskin/skin6.png";
        $skin7 = "personaskin/skin7.png";
        foreach ([$skin1, $skin2, $skin3, $skin4, $skin5, $skin6, $skin7] as $skins){
            $this->saveResource($skins);
        }

        $this->formManager = new FormManager($this);
        $this->itemManager = new ItemManager($this);
        $this->purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");



        //---------Blocks Fix----------
        $this->handler = SimplePacketHandler::createInterceptor($this, EventPriority::HIGHEST);
        $this->handleUpdateBlock = function(UpdateBlockPacket $packet, NetworkSession $target): bool{
            if($target->getPlayer() !== $this->lastPlayer){
                return true;
            }
            $blockHash = World::blockHash($packet->blockPosition->getX(), $packet->blockPosition->getY(), $packet->blockPosition->getZ());
            if(RuntimeBlockMapping::getInstance()->fromRuntimeId($packet->blockRuntimeId) !== ($this->oldBlocksFullId[$blockHash] ?? null)){
                return true;
            }
            unset($this->oldBlockIds[$blockHash]);
            if(count($this->oldBlockIds) === 0){
                if(count($this->oldTilesSerializedCompound) === 0){
                    $this->lastPlayer = null;
                }
                $this->handler->unregisterOutgoingInterceptor($this->handleUpdateBlock);
            }
            return false;
        };
        $this->handleBlockActor = function(BlockActorDataPacket $packet, NetworkSession $target): bool{
            if($target->getPlayer() !== $this->lastPlayer){
                return true;
            }
            $blockHash = World::blockHash($packet->blockPosition->getX(), $packet->blockPosition->getY(), $packet->blockPosition->getZ());
            if($packet->nbt !== ($this->oldTilesSerializedCompound[$blockHash] ?? null)){
                return true;
            }
            unset($this->oldTilesSerializedCompound[$blockHash]);
            if(count($this->oldTilesSerializedCompound) === 0){
                if(count($this->oldTilesSerializedCompound) === 0){
                    $this->lastPlayer = null;
                }
                $this->handler->unregisterOutgoingInterceptor($this->handleBlockActor);
            }
            return false;
        };
        $this->getServer()->getPluginManager()->registerEvent(PlayerInteractEvent::class, function(PlayerInteractEvent $event): void{
            if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK || !$event->getItem()->canBePlaced()){
                return;
            }
            $this->lastPlayer = $event->getPlayer();
            $clickedBlock = $event->getBlock();
            $replaceBlock = $clickedBlock->getSide($event->getFace());
            $this->oldBlockIds = [];
            $this->oldTilesSerializedCompound = [];
            foreach($clickedBlock->getAllSides() as $block){
                $pos = $block->getPosition();
                $posIndex = World::blockHash($pos->x, $pos->y, $pos->z);
                $this->oldBlockIds[$posIndex] = $block->getId();
                $tile = $pos->getWorld()->getTileAt($pos->x, $pos->y, $pos->z);
                if($tile instanceof Spawnable){
                    $this->oldTilesSerializedCompound[$posIndex] = $tile->getSerializedSpawnCompound();
                }
            }
            foreach($replaceBlock->getAllSides() as $block){
                $pos = $block->getPosition();
                $posIndex = World::blockHash($pos->x, $pos->y, $pos->z);
                $this->oldBlockIds[$posIndex] = $block->getId();
                $tile = $pos->getWorld()->getTileAt($pos->x, $pos->y, $pos->z);
                if($tile instanceof Spawnable){
                    $this->oldTilesSerializedCompound[$posIndex] = $tile->getSerializedSpawnCompound();
                }
            }
            $this->handler->interceptOutgoing($this->handleUpdateBlock);
            $this->handler->interceptOutgoing($this->handleBlockActor);
        }, EventPriority::MONITOR, $this);
        $this->getServer()->getPluginManager()->registerEvent(BlockPlaceEvent::class, function(BlockPlaceEvent $event): void{
            $this->oldBlockIds = [];
            $this->oldTilesSerializedCompound = [];
            $this->lastPlayer = null;
            $this->handler->unregisterOutgoingInterceptor($this->handleUpdateBlock);
            $this->handler->unregisterOutgoingInterceptor($this->handleBlockActor);
        }, EventPriority::MONITOR, $this, true);

        //fix weird gui crash bug in bedwars
        static $send = false;
        SimplePacketHandler::createInterceptor($this)
		->interceptIncoming(static function(ContainerClosePacket $packet, NetworkSession $session) use(&$send) : bool{
			$send = true;
			$session->sendDataPacket($packet);
			$send = false;
			return true;
		})
		->interceptOutgoing(static function(ContainerClosePacket $packet, NetworkSession $session) use(&$send) : bool{
			return $send;
		});

        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            public function __construct(HyperiumCore $plugin){
                $this->plugin = $plugin;
            }

            public function onRun(): void
            {
                $pass = $this->plugin->getConfig()->get("password");

                if (base64_decode($pass) !== "HyperiumCore2022"){
                    $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
                    $this->plugin->getLogger()->critical("§cWrong Password!");
                } elseif ($this->plugin->servername["HyperiumMC"] !== "HyperiumCore"){
                    $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
                    $this->plugin->getServer()->getLogger()->info("§cWrong Server Name!");
                }
            }
        }, 20);

        //-------------------------Skin---------------------------------
        $this->originalAdaptor = SkinAdapterSingleton::get();
        SkinAdapterSingleton::set(new PersonaSkinAdapter());

        $skinPaths = glob($this->getDataFolder() . "personaskin/*.png");

        if (is_array($skinPaths)) {
            foreach ($skinPaths as $id => $skinPath) {
                set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($skinPath) {
                    $this->getLogger()->warning("Skin " . basename($skinPath) . " could not be loaded. Error: #$errno - $errstr");
                });
                $img = imagecreatefrompng($skinPath);
                restore_error_handler();
                if ($img === false) {
                    continue;//just continue, log via error handler above
                }
                self::$skins[] = new Skin("personatoskin." . basename($skinPath), self::fromImage($img), "", "geometry.humanoid.custom");
                @imagedestroy($img);
            }
        }

        //--------------------Texture Pack Encrypt-------------------------------
        foreach($this->getServer()->getResourcePackManager()->getResourceStack() as $resourcePack){
            $uuid = $resourcePack->getPackId();
            if($this->getConfig()->getNested("resource-packs.{$uuid}", "") !== ""){
                $encryptionKey = $this->getConfig()->getNested("resource-packs.{$uuid}");
                $this->encryptionKeys[$uuid] = $encryptionKey;
                $this->getLogger()->debug("Loaded encryption key for resource pack $uuid");
            }
        }
        $this->getServer()->getPluginManager()->registerEvent(DataPacketSendEvent::class, function(DataPacketSendEvent $event) : void{
            $packets = $event->getPackets();
            foreach($packets as $packet){
                if($packet instanceof ResourcePacksInfoPacket){
                    foreach($packet->resourcePackEntries as $index => $entry){
                        if(isset($this->encryptionKeys[$entry->getPackId()])){
                            $contentId = $this->encryptionKeys[$entry->getPackId()];
                            $packet->resourcePackEntries[$index] = new ResourcePackInfoEntry($entry->getPackId(), $entry->getVersion(), $entry->getSizeBytes(), $contentId, $entry->getSubPackName(), $entry->getPackId(), $entry->hasScripts(), $entry->isRtxCapable());
                        }
                    }
                }
            }
        }, EventPriority::HIGHEST, $this);

        //--------------------Ender Pearl Improve------------------
        $this->getServer()->getPluginManager()->registerEvent(ProjectileHitEvent::class, static function (ProjectileHitEvent $event) : void{
            $projectile = $event->getEntity();
            $entity = $projectile->getOwningEntity();
            if ($projectile instanceof EnderPearl and $entity instanceof Player) {
                $vector = $event->getRayTraceResult()->getHitVector();
                $setPosition = new \ReflectionMethod($entity, 'setPosition');
                $setPosition->setAccessible(true);
                $setPosition->invoke($entity, $vector);
                $location = $entity->getLocation();
                $entity->getNetworkSession()->syncMovement($location, $location->yaw, $location->pitch);
                $projectile->setOwningEntity(null);
            }
        }, EventPriority::NORMAL, $this);
    }

    public static function getDiscordAPI(): DiscordBot {
        return self::$discordapi;
    }

    public function onDisable(): void
    {
        if($this->originalAdaptor !== null){
            SkinAdapterSingleton::set($this->originalAdaptor);
        }
    }

    /**
     * @return FormManager
     */
    public function getFormManager(): ?FormManager
    {
        return $this->formManager;
    }

    /**
     * @return ItemManager
     */
    public function getItemManager(): ?ItemManager
    {
        return $this->itemManager;
    }

    public function getPlayerRank(Player $player): string{
        return RankManager::getPlayerRank($player)->getDisplayFormat();
    }

    public function handleStatsEntity(Player $player){
        foreach ($player->getWorld()->getEntities() as $entity){
            if ($entity instanceof StatsEntity){
                $entity->setSkin($player->getSkin());
                $entity->sendSkin([$player]);
            }
        }
    }

    public function prepareChunk(World $world, Vector3 $vec) : void{
        if ($world == null || $vec == null) return;
        $chunkX = $vec->getFloorX() >> 4;
        $chunkZ = $vec->getFloorZ() >> 4;
        if($world->loadChunk($chunkX, $chunkZ) !== null)
            return;

        $world->orderChunkPopulation($chunkX, $chunkZ, null);
        $world->setChunk($chunkX, $chunkZ, clone $this->tempChunk);
    }

    public function toThin(string $str) : string{
        return preg_replace("/%*(([a-z0-9_]+\.)+[a-z0-9_]+)/i", "%$1", $str) . TextFormat::ESCAPE . "　";
    }

    public function createCape($capeName) {
        $path = $this->getDataFolder() . "capes/$capeName.png";
        $img = imagecreatefrompng($path);
        $bytes = "";
        try {
            for ($y = 0; $y < imagesy($img); $y++) {
                for ($x = 0; $x < imagesx($img); $x++) {
                    $rgba = @imagecolorat($img, $x, $y);
                    $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                    $r = ($rgba >> 16) & 0xff;
                    $g = ($rgba >> 8) & 0xff;
                    $b = $rgba & 0xff;
                    $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
                }
            }
            @imagedestroy($img);
        }catch (\Exception $exception){
            $this->getLogger()->info("Broken srgb profile");
        }

        return $bytes;
    }

    public function getAllCapes() {
        $list = array();

        foreach(array_diff(scandir($this->getDataFolder() . "capes/"), ["..", "."]) as $data) {
            $dat = explode(".", $data);

            if($dat[1] == "png") {
                array_push($list, $dat[0]);
            }
        }

        return $list;
    }

    public static function fromImage($img)
    {
        $bytes = '';
        for ($y = 0; $y < imagesy($img); $y++) {
            for ($x = 0; $x < imagesx($img); $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        return $bytes;
    }

    public static function getRandomSkin(): Skin
    {
        return self::$skins[array_rand(self::$skins)];
    }

    public function getPlayerTime(Player $player){
        $time = new \DateTime("now", new \DateTimeZone("Asia/Kuala_Lumpur"));
        return $time->format("H:i");
    }

    public function getTimeFormat(Player $player){
        if (!isset($this->playerTimeZone[$player->getName()])){
            $ip = $player->getNetworkSession()->getIp();
            $ipInfo = file_get_contents('http://ip-api.com/json/'.$ip);
            $ipInfo = json_decode($ipInfo);
            $timezone = $ipInfo->timezone;
            $this->playerTimeZone[$player->getName()] = $timezone;
        }
        $time = new \DateTime("now", new \DateTimeZone($this->playerTimeZone[$player->getName()]));

        return $time->format("H:i a");
    }

    public function checkSkin(Player $player, ?string $skinData = null) : bool {
        $skinData ??= $player->getSkin()->getSkinData();
        $size = strlen($skinData);
        $width = self::SKIN_WIDTH_MAP[$size];
        $height = self::SKIN_HEIGHT_MAP[$size];
        $pos = -1;
        $pixelsNeeded = (int)(100 - 75 / 100 * ($width * $height)); // visible pixels needed 80 = percent
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if (ord($skinData[$pos += 4]) === 255) {
                    if (--$pixelsNeeded === 0) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function isJohnnyWai(Player $player){
        if (strtolower($player->getName()) == "johnnywai666"){
            return true;
        }
        return false;
    }

    public function addCPS(Player $player): void{
        $time = microtime(true);
        $this->cps[$player->getName()][] = $time;
    }

    public function getCPS(Player $player): int{
        $time = microtime(true);
        return count(array_filter($this->cps[$player->getName()] ?? [], static function(float $t) use ($time):bool{
            return ($time - $t) <= 1;
        }));
    }
}
