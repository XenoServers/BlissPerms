<?php

namespace Xenophilicy\BlissPerms\Data;

use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\utils\Config;
use Xenophilicy\BlissPerms\BlissGroup;
use Xenophilicy\BlissPerms\BlissPerms;
use Xenophilicy\BlissPerms\BlissRank;
use Xenophilicy\BlissPerms\BlissTier;

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
        $rank = is_null($this->plugin->getDefaultRank()) ? null : $this->plugin->getDefaultRank()->getName();
        $tier = is_null($this->plugin->getDefaultTier()) ? null : $this->plugin->getDefaultTier()->getName();
        if(!$this->players->exists($userName)){
            return ["group" => $this->plugin->getDefaultGroup()->getName(), "rank" => $rank, "tier" => $tier, "permissions" => []];
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
            $rank = is_null($this->plugin->getDefaultRank()) ? null : $this->plugin->getDefaultRank()->getName();
            $tier = is_null($this->plugin->getDefaultTier()) ? null : $this->plugin->getDefaultTier()->getName();
            $this->players->set($userName, ["group" => $this->plugin->getDefaultGroup()->getName(), "rank" => $rank, "tier" => $tier, "permissions" => []]);
        }
        if(isset($tempUserData["userName"])) unset($tempUserData["userName"]);
        $this->players->set($userName, $tempUserData);
        $this->players->save();
    }
    
    public function getTier(Player $player): ?BlissTier{
        $name = $this->getNode($player, "tier");
        return $this->plugin->getTier($name);
    }
    
    public function setTier(Player $player, ?BlissTier $tier): void{
        $tierName = is_null($tier) ? null : $tier->getName();
        $this->setNode($player, "tier", $tierName);
        $nametag = $this->plugin->getNametag($player);
        $player->setNameTag($nametag);
        $this->plugin->updatePermissions($player);
    }
    
    public function getRank(Player $player): ?BlissRank{
        $name = $this->getNode($player, "rank");
        return $this->plugin->getRank($name);
    }
    
    public function setRank(Player $player, ?BlissRank $rank): void{
        $rankName = is_null($rank) ? null : $rank->getName();
        $this->setNode($player, "rank", $rankName);
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