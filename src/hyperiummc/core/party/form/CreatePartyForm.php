<?php

namespace hyperiummc\core\party\form;

use EasyUI\element\Input;
use EasyUI\utils\FormResponse;
use EasyUI\variant\CustomForm;
use hyperiummc\core\session\SessionManager;
use hyperiummc\zenapi\ZenAPI;
use pocketmine\player\Player;

class CreatePartyForm extends CustomForm
{
    public function __construct() {
        parent::__construct("Create party");
    }

    protected function onCreation(): void {

        $this->addElement("party name", new Input("Party name:"));

    }

    protected function onSubmit(Player $player, FormResponse $response): void {

        $partyName = $response->getInputSubmittedText("party name");
        $party = ZenAPI::getInstance()->getPartyManager()->create(SessionManager::getSession($player), $partyName);
        $player->sendForm(new YourPartyForm($party, $player));

    }

}
