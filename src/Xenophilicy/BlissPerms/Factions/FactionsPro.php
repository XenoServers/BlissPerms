<?php

namespace Xenophilicy\BlissPerms\Factions;

use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

/**
 * Class FactionsPro
 * @package _64FF00\PureChat\Factions
 */
class FactionsPro implements FactionsInterface {
    
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
        return Server::getInstance()->getPluginManager()->getPlugin("FactionsPro");
    }
    
    /**
     * @param Player $player
     * @return string
     */
    public function getFactionRank(Player $player){
        if(!$this->getAPI()->isInFaction($player->getName())){
            return '';
        }
        if($this->getAPI()->isOfficer($player->getName())){
            return '*';
        }
        if($this->getAPI()->isLeader($player->getName())){
            return '**';
        }
        return '';
    }
}
