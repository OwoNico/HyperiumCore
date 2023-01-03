<?php

namespace hyperiummc\core\party\form;

use EasyUI\element\Button;
use EasyUI\variant\SimpleForm;
use hyperiummc\core\session\SessionManager;
use hyperiummc\zenapi\system\party\Party;
use hyperiummc\zenapi\system\party\PartyInvitation;
use hyperiummc\zenapi\ZenAPI;

class InvitePartyForm extends SimpleForm
{

    private Party $party;

    public function __construct(Party $party)
    {
        $this->party = $party;
        parent::__construct("Party - Invite Players");
    }

    public function onCreation(): void
    {

        foreach(ZenAPI::getInstance()->getServer()->getOnlinePlayers() as $invitePlayer) {
            $button = new Button($invitePlayer->getName());
            $button->setSubmitListener(

                function() use ($invitePlayer) {
                    if (!ZenAPI::getInstance()->getPartyManager()->isPlayerInParty($invitePlayer)){
                        $invitation = new PartyInvitation($this->party->getName(), SessionManager::getSession($invitePlayer));
                        ZenAPI::getInstance()->getPartyManager()->addInvitation($invitation);
                    } else{
                        $this->party->getOwner()->message("§cThe Player is already in a party!");
                    }

                });

            $this->addButton($button);
        }
    }

}
