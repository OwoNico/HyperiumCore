<?php

namespace hyperiummc\core\party\form;

use EasyUI\element\Button;
use EasyUI\variant\SimpleForm;
use hyperiummc\zenapi\system\party\Party;
use hyperiummc\zenapi\ZenAPI;
use pocketmine\player\Player;

class YourPartyForm extends SimpleForm {

    private Party $party;
    private Player $player;

    public function __construct(Party $party, Player $player) {

        $this->party = $party;
        $this->player = $player;
        parent::__construct("Party");
    }

    public function onCreation(): void
    {


        $button = new Button("Invite Players");
        $playersButton = new Button("Party Members");
        $disbandButton = new Button("Disband Party");
        $party = $this->party;
        $player = $this->player;

        $button->setSubmitListener(

            function () use ($player, $party) {

                $player->sendForm(new InvitePartyForm($party));

            });

        $playersButton->setSubmitListener(

            function () use ($player, $party) {

                $player->sendForm(new PlayersInPartyForm($party, $player));

            });

        $disbandButton->setSubmitListener(

            function () use ($player, $party) {

                ZenAPI::getInstance()->getPartyManager()->delete($party->getName());

            });

        if($party->isOwner($player)) {
            $this->addButton($disbandButton);
            $this->addButton($button);
        }

        $this->addButton($playersButton);
    }



}
