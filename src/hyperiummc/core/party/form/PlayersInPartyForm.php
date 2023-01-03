<?php

namespace hyperiummc\core\party\form;

use EasyUI\element\Button;
use EasyUI\variant\SimpleForm;
use hyperiummc\zenapi\system\party\Party;
use pocketmine\player\Player;

class PlayersInPartyForm extends SimpleForm
{
    public Party $party;
    public Player $player;

    public function __construct(Party $party, Player $player)
    {
        $this->party = $party;
        $this->player = $player;
        parent::__construct("Party - Members");
    }

    public function onCreation(): void
    {

        $player = $this->player;
        $party = $this->party;

        foreach($this->party->getPlayers() as $session) {

            $button = new Button($session->getUsername());

            if($party->isOwner($player)) {

                $button->setSubmitListener(

                    function () use ($player, $party, $session) {

                        $player->sendForm(new ModifyPlayerInPartyForm($session, $player, $party));

                    });

            }

            $this->addButton($button);

        }
    }

}