<?php

namespace Xenophilicy\BlissPerms\Factions;

use pocketmine\Player;
use pocketmine\plugin\Plugin;

/**
 * Interface FactionsInterface
 * @package Xenophilicy\BlissPerms\Factions
 */
interface FactionsInterface {
    
    /**
     * @return Plugin|null
     */
    public function getAPI();
    
    /**
     * @param Player $player
     * @return mixed
     */
    public function getFaction(Player $player);
    
    /**
     * @param Player $player
     * @return mixed
     */
    public function getFactionRank(Player $player);
}
