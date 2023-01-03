<?php

namespace hyperiummc\core\task;

use hyperiummc\core\HyperiumCore;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class JoinTitleTask extends Task{

    public $title = 0;

    public function __construct(Player $player, HyperiumCore $plugin){
        $this->player = $player;
        $this->plugin = $plugin;
    }

    public function onRun(): void
    {
        if(!$this->player->isOnline()){
            $this->getHandler()->cancel();
            return;
        }
        switch ($this->title++){
            case 1:
                $this->player->sendTitle("§6§9");
                break;
            case 2:
                $this->player->sendTitle("§6§9");
                break;
            case 3:
                $this->player->sendTitle("§6§9");
                break;
            case 4:
                $this->player->sendTitle("§6§9");
                break;
            case 5:
                $this->player->sendTitle("§6§9");
                break;
            case 6:
                $this->player->sendTitle("§6§9");
                break;
            case 7:
                $this->player->sendTitle("§6§9");
                break;
            case 8:
                $this->player->sendTitle("§6§9");
                break;
            case 9:
                $this->player->sendTitle("§6§9");
                break;
            case 10:
                $this->player->sendTitle("§6§9");
                break;
            case 11:
                $this->player->sendTitle("§6§9");
                break;
            case 12:
                $this->player->sendTitle("§c");
                unset($this->plugin->shouldSendAnimatedTitle[$this->player->getName()]);
                $this->getHandler()->cancel();
                break;
        }
    }
}
