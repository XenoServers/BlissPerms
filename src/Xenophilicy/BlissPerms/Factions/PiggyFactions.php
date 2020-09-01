<?php

namespace Xenophilicy\BlissPerms\Factions;

use DaPigGuy\PiggyFactions\utils\Roles;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

/**
 * Class PiggyFactions
 * @package Xenophilicy\BlissPerms\Factions
 */
class PiggyFactions implements FactionsInterface {
    
    /**
     * @param Player $player
     * @return mixed
     */
    public function getFaction(Player $player){
        return $this->getAPI()->getPlayerFaction($player->getName());
    }
    
    /**
     * @return Plugin|null
     */
    public function getAPI(){
        return Server::getInstance()->getPluginManager()->getPlugin("PiggyFactions");
    }
    
    /**
     * @param Player $player
     * @return string
     */
    public function getFactionRank(Player $player){
        if($player->getRole() === Roles::RECRUIT){
            return '';
        }
        if($player->getRole() === Roles::MEMBER){
            return '*';
        }
        if($player->getRole() === Roles::OFFICER){
            return '**';
        }
        if($player->getRole() === Roles::LEADER){
            return '***';
        }
        return '';
    }
}