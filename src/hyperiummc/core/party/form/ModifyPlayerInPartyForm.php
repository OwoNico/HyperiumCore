<?php

namespace hyperiummc\core\party\form;

use EasyUI\element\Button;
use EasyUI\variant\SimpleForm;
use hyperiummc\core\session\Session;
use hyperiummc\zenapi\system\party\Party;
use pocketmine\player\Player;

class ModifyPlayerInPartyForm extends SimpleForm {

    private Session $session;
    private Party $party;
    private Player $player;

    public function __construct(Session $session, Player $player, Party $party)
    {
        $this->session = $session;
        $this->party = $party;
        $this->player = $player;
        parent::__construct("Party - Modify " . $session->getUsername());
    }

    public function onCreation(): void
    {
        $session = $this->session;
        $party = $this->party;
        $setOwnerButton = new Button("Promote to party owner");
        $kickPlayerButton = new Button("Kick player");

        $setOwnerButton->setSubmitListener(

            function () use ($session, $party) {
                if ($session->getUsername() == $party->getOwner()->getUsername()){
                    $party->getOwner()->message("§cYou cant promote yourself to party owner!");
                    return;
                }
                $party->setOwner($session);

                foreach($party->getPlayers() as $player)
                    $player->message("§a" . $session->getUsername() . " is now the party owner.");

            });

        $kickPlayerButton->setSubmitListener(

            function () use ($session, $party){
                if ($session->getUsername() == $party->getOwner()->getUsername()){
                    $party->getOwner()->message("§cYou cant kick yourself!");
                    return;
                }

                $party->remove($session);

                foreach($party->getPlayers() as $player)
                    $player->message("§c" . $session->getUsername() . " was kicked from the party.");

            });

        if($party->isOwner($this->player)) {

            $this->addButton($kickPlayerButton);
            $this->addButton($setOwnerButton);

        }
    }


}
