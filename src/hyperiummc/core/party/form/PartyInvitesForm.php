<?php

namespace hyperiummc\core\party\form;

use EasyUI\element\Button;
use EasyUI\variant\SimpleForm;
use hyperiummc\core\session\SessionManager;
use hyperiummc\zenapi\ZenAPI;
use pocketmine\player\Player;

class PartyInvitesForm extends SimpleForm
{
    private Player $player;

    public function __construct(Player $player)
    {
        $this->player = $player;
        parent::__construct("Party - Invitations");
    }

    public function onCreation(): void
    {
        $closebutton = new Button("Close");
        foreach(ZenAPI::getInstance()->getPartyManager()->getInvitations() as $invitation) {

            if($invitation->getInvitingSession()->getUsername() === $this->player->getName()) {

                $player = $this->player;
                $button = new Button($invitation->getPartyName());
                $button->setSubmitListener(

                    function () use ($invitation, $player) {

                        $party = ZenAPI::getInstance()->getPartyManager()->getPartyByName($invitation->getPartyName());
                        if($party == null) return;
                        $party->add(SessionManager::getSession($player));
                        ZenAPI::getInstance()->getPartyManager()->removeInvitation($invitation);
                    });

                $this->addButton($button);

            }

        }
        $this->addButton($closebutton);
    }

}
