<?php

namespace Xenophilicy\BlissPerms\Data;

use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\utils\Config;
use Xenophilicy\BlissPerms\BlissGroup;
use Xenophilicy\BlissPerms\BlissPerms;
use Xenophilicy\BlissPerms\BlissRank;

/**
 * Class PlayerManager
 * @package Xenophilicy\BlissPerms\Data
 */
class PlayerManager {
    
    /** @var BlissPerms */
    private $plugin;
    /** @var Config */
    private $players;
    
    public function __construct(BlissPerms $plugin){
        $this->plugin = $plugin;
        $this->plugin->saveResource("players.yml");
        $this->players = new Config($this->plugin->getDataFolder() . "players.yml", Config::YAML);
    }
    
    public function getGroup(Player $player): BlissGroup{
        $name = $this->getNode($player, "group");
        $group = $this->plugin->getGroup($name);
        if($group === null){
            $default = $this->plugin->getDefaultGroup();
            $this->setGroup($player, $default);
            return $default;
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
        return $userData[$node] ?? null;
    }
    
    public function getData(Player $player): array{
        return $this->getPlayerData($player);
    }
    
    public function getPlayerData(IPlayer $player): array{
        $userName = strtolower($player->getName());
        if(!$this->players->exists($userName)){
            return ["group" => $this->plugin->getDefaultGroup()->getName(), "rank" => $this->plugin->getDefaultRank()->getName(), "permissions" => []];
        }
        return $this->players->get($userName);
    }
    
    public function setGroup(Player $player, BlissGroup $group): void{
        $this->setNode($player, "group", $group->getName());
        $nametag = $this->plugin->getNametag($player);
        $player->setNameTag($nametag);
        $this->plugin->updatePermissions($player);
    }
    
    /**
     * @param Player $player
     * @param string $node
     * @param $value
     */
    public function setNode(Player $player, string $node, $value){
        $tempUserData = $this->getData($player);
        $tempUserData[$node] = $value;
        $this->setData($player, $tempUserData);
    }
    
    public function setData(Player $player, array $data): void{
        $this->setPlayerData($player, $data);
    }
    
    public function setPlayerData(IPlayer $player, array $tempUserData): void{
        $userName = strtolower($player->getName());
        if(!$this->players->exists($userName)){
            $this->players->set($userName, ["group" => $this->plugin->getDefaultGroup()->getName(), "rank" => $this->plugin->getDefaultRank()->getName(), "permissions" => []]);
        }
        if(isset($tempUserData["userName"])) unset($tempUserData["userName"]);
        $this->players->set($userName, $tempUserData);
        $this->players->save();
    }
    
    public function getRank(Player $player): BlissRank{
        $name = $this->getNode($player, "rank");
        $rank = $this->plugin->getRank($name);
        if($rank === null){
            $default = $this->plugin->getDefaultRank();
            $this->setRank($player, $default);
            return $default;
        }
        return $rank;
    }
    
    public function setRank(Player $player, BlissRank $rank): void{
        $this->setNode($player, "rank", $rank->getName());
        $nametag = $this->plugin->getNametag($player);
        $player->setNameTag($nametag);
        $this->plugin->updatePermissions($player);
    }
    
    public function getUserPermissions(Player $player): array{
        $permissions = $this->getNode($player, "permissions");
        if(!is_array($permissions)){
            return [];
        }
        return $permissions;
    }
    
    public function removeNode(Player $player, string $node): void{
        $tempUserData = $this->getData($player);
        if(isset($tempUserData[$node])){
            unset($tempUserData[$node]);
            $this->setData($player, $tempUserData);
        }
    }
    
    public function setPermission(Player $player, string $permission): void{
        $tempUserData = $this->getData($player);
        $tempUserData["permissions"][] = $permission;
        $this->setData($player, $tempUserData);
        $this->plugin->updatePermissions($player);
    }
    
    public function unsetPermission(Player $player, string $permission): void{
        $tempUserData = $this->getData($player);
        if(!in_array($permission, $tempUserData["permissions"])) return;
        $tempUserData["permissions"] = array_diff($tempUserData["permissions"], [$permission]);
        $this->setData($player, $tempUserData);
        $this->plugin->updatePermissions($player);
    }
}