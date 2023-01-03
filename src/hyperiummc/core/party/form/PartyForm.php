<?php

namespace hyperiummc\core\party\form;

use EasyUI\element\Button;
use EasyUI\variant\SimpleForm;
use hyperiummc\core\session\SessionManager;
use hyperiummc\zenapi\ZenAPI;
use pocketmine\player\Player;


class PartyForm extends SimpleForm
{

    private Player $player;

    public function __construct(Player $player)
    {
        $this->player = $player;
        parent::__construct("Party");
    }

    public function onCreation(): void
    {
        $createButton = new Button("Create Party");
        $invitesButton = new Button("Party Invitations");
        $player = $this->player;

        if(!ZenAPI::getInstance()->getPartyManager()->isPlayerInParty($player)) {
            $createButton->setSubmitListener(

                function () use ($player) {
                    $player->sendForm(new CreatePartyForm());

                });

            $invitesButton->setSubmitListener(

                function () use ($player) {
                    $player->sendForm(new PartyInvitesForm($player));

                });

            $this->addButton($createButton);
            $this->addButton($invitesButton);

        } else {

            $button = new Button("Your Party");
            $leaveButton = new Button("Leave party");

            $leaveButton->setSubmitListener(

                function () use ($player) {

                    $session = SessionManager::getSession($player);
                    $session->message("§aYou left the party!");

                    $party = ZenAPI::getInstance()->getPartyManager()->getPartyOfPlayer($session);
                    $party->remove($session, true);

                });

            $button->setSubmitListener(

                function () use ($player) {

                    $player->sendForm(new YourPartyForm(ZenAPI::getInstance()->getPartyManager()->getPartyOfPlayer(SessionManager::getSession($player)), $player));

                });

            $this->addButton($button);

            $session = SessionManager::getSession($player);
            if(ZenAPI::getInstance()->getPartyManager()->getPartyOfPlayer($session) === null) return;
            if (!ZenAPI::getInstance()->getPartyManager()->getPartyOfPlayer($session)->isOwner($player)){
                $this->addButton($leaveButton);
            }

        }
    }

}
