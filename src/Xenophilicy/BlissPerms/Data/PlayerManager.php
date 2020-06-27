<?php

namespace Xenophilicy\BlissPerms\Data;

use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\utils\Config;
use Xenophilicy\BlissPerms\BlissGroup;
use Xenophilicy\BlissPerms\BlissPerms;

/**
 * Class PlayerManager
 * @package Xenophilicy\BlissPerms\Data
 */
class PlayerManager {
    
    private $plugin;
    private $players;
    
    /**
     * @param BlissPerms $plugin
     */
    public function __construct(BlissPerms $plugin){
        $this->plugin = $plugin;
        $this->plugin->saveResource("players.yml");
        $this->players = new Config($this->plugin->getDataFolder() . "players.yml", Config::YAML);
    }
    
    /**
     * @param Player $player
     * @return BlissGroup|null
     */
    public function getGroup(Player $player){
        $groupName = $this->getNode($player, "group");
        $group = $this->plugin->getGroup($groupName);
        if($group === null){
            $defaultGroup = $this->plugin->getDefaultGroup();
            $this->setGroup($player, $defaultGroup);
            return $defaultGroup;
        }
        return $group;
    }
    
    /**
     * @param Player $player
     * @param $node
     * @return null|mixed
     */
    public function getNode(Player $player, $node){
        $userData = $this->getData($player);
        if(!isset($userData[$node])) return null;
        return $userData[$node];
    }
    
    /**
     * @param Player $player
     * @return array
     */
    public function getData(Player $player){
        return $this->getPlayerData($player);
    }
    
    /**
     * @param IPlayer $player
     * @return array|bool|mixed
     */
    public function getPlayerData(IPlayer $player){
        $userName = strtolower($player->getName());
        if(!$this->players->exists($userName)){
            return ["group" => $this->plugin->getDefaultGroup()->getName(), "permissions" => []];
        }
        return $this->players->get($userName);
    }
    
    /**
     * @param Player $player
     * @param BlissGroup $group
     */
    public function setGroup(Player $player, BlissGroup $group){
        $this->setNode($player, "group", $group->getName());
        $nametag = $this->plugin->getNametag($player);
        $player->setNameTag($nametag);
    }
    
    /**
     * @param Player $player
     * @param $node
     * @param $value
     */
    public function setNode(Player $player, $node, $value){
        $tempUserData = $this->getData($player);
        $tempUserData[$node] = $value;
        $this->setData($player, $tempUserData);
    }
    
    /**
     * @param Player $player
     * @param array $data
     */
    public function setData(Player $player, array $data){
        $this->setPlayerData($player, $data);
    }
    
    /**
     * @param IPlayer $player
     * @param array $tempUserData
     */
    public function setPlayerData(IPlayer $player, array $tempUserData){
        $userName = strtolower($player->getName());
        if(!$this->players->exists($userName)){
            $this->players->set($userName, ["group" => $this->plugin->getDefaultGroup()->getName(), "permissions" => []]);
        }
        if(isset($tempUserData["userName"])) unset($tempUserData["userName"]);
        $this->players->set($userName, $tempUserData);
        $this->players->save();
    }
    
    /**
     * @param Player $player
     * @return array
     */
    public function getUserPermissions(Player $player){
        $permissions = $this->getNode($player, "permissions");
        if(!is_array($permissions)){
            return [];
        }
        return $permissions;
    }
    
    /**
     * @param Player $player
     * @param $node
     */
    public function removeNode(Player $player, $node){
        $tempUserData = $this->getData($player);
        if(isset($tempUserData[$node])){
            unset($tempUserData[$node]);
            $this->setData($player, $tempUserData);
        }
    }
    
    /**
     * @param Player $player
     * @param $permission
     */
    public function setPermission(Player $player, $permission){
        $tempUserData = $this->getData($player);
        $tempUserData["permissions"][] = $permission;
        $this->setData($player, $tempUserData);
        $this->plugin->updatePermissions($player);
    }
    
    /**
     * @param Player $player
     * @param $permission
     */
    public function unsetPermission(Player $player, $permission){
        $tempUserData = $this->getData($player);
        if(!in_array($permission, $tempUserData["permissions"])) return;
        $tempUserData["permissions"] = array_diff($tempUserData["permissions"], [$permission]);
        $this->setData($player, $tempUserData);
        $this->plugin->updatePermissions($player);
    }
}