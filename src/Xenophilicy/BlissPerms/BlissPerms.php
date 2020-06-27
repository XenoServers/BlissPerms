<?php

namespace Xenophilicy\BlissPerms;

use pocketmine\IPlayer;
use pocketmine\permission\PermissionAttachment;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use Xenophilicy\BlissPerms\Command\Group;
use Xenophilicy\BlissPerms\Command\SetPerm;
use Xenophilicy\BlissPerms\Command\SetPrefix;
use Xenophilicy\BlissPerms\Command\SetSuffix;
use Xenophilicy\BlissPerms\Data\PlayerManager;
use Xenophilicy\BlissPerms\Factions\FactionsPro;
use Xenophilicy\BlissPerms\Provider\GroupProvider;

/**
 * Class BlissPerms
 * @package Xenophilicy\BlissPerms
 */
class BlissPerms extends PluginBase {
    
    const MISSING = null;
    const INVALID = -1;
    const EXISTS = 0;
    const SUCCESS = 1;
    public static $settings;
    private $playerManager;
    private $attachments = [];
    private $factions;
    private $groupProvider;
    private $groups = [];
    
    public function onLoad(){
        $this->saveDefaultConfig();
        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        self::$settings = $config->getAll();
        $this->playerManager = new PlayerManager($this);
    }
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->registerCommands();
        $this->setProvider();
        $this->registerPlayers();
        $this->loadFactionsPlugin();
    }
    
    private function registerCommands(){
        $cmdMap = $this->getServer()->getCommandMap();
        $cmdMap->register("group", new Group("group", $this));
        $cmdMap->register("setperm", new SetPerm("setperm", $this));
        $cmdMap->register("setprefix", new SetPrefix("setprefix", $this));
        $cmdMap->register("setsuffix", new SetSuffix("setsuffix", $this));
    }
    
    private function setProvider(){
        $provider = new GroupProvider($this);
        $this->groupProvider = $provider;
        $this->updateGroups();
    }
    
    private function updateGroups(){
        $this->groups = [];
        foreach(array_keys($this->getGroupProvider()->getConfig()->getAll()) as $groupName){
            $this->groups[$groupName] = new BlissGroup($this, $groupName);
            $this->groups[$groupName]->sortPermissions();
        }
    }
    
    /**
     * @return GroupProvider
     */
    public function getGroupProvider(){
        if(!$this->isValidProvider()) $this->setProvider();
        return $this->groupProvider;
    }
    
    /**
     * @return bool
     */
    private function isValidProvider(){
        if(!$this->groupProvider instanceof GroupProvider) return false;
        return true;
    }
    
    private function registerPlayers(){
        foreach($this->getServer()->getOnlinePlayers() as $player){
            $this->registerPlayer($player);
        }
    }
    
    /**
     * @param Player $player
     */
    public function registerPlayer(Player $player){
        $uniqueId = $this->getValidUUID($player);
        if(!isset($this->attachments[$uniqueId])){
            $attachment = $player->addAttachment($this);
            $this->attachments[$uniqueId] = $attachment;
            $this->updatePermissions($player);
        }
    }
    
    /**
     * @param Player $player
     * @return null|string
     */
    public function getValidUUID(Player $player){
        $uuid = $player->getUniqueId();
        if($uuid instanceof UUID) return $uuid->toString();
        return null;
    }
    
    /**
     * @param IPlayer $player
     */
    public function updatePermissions(IPlayer $player){
        if($player instanceof Player){
            $permissions = [];
            foreach($this->getPermissions($player) as $permission){
                if($permission === '*'){
                    foreach(PermissionManager::getInstance()->getPermissions() as $tmp){
                        $permissions[$tmp->getName()] = true;
                    }
                }else{
                    $isNegative = substr($permission, 0, 1) === "-";
                    if($isNegative) $permission = substr($permission, 1);
                    $permissions[$permission] = !$isNegative;
                }
            }
            $attachment = $this->getAttachment($player);
            $attachment->clearPermissions();
            $attachment->setPermissions($permissions);
        }
    }
    
    /**
     * @param IPlayer $player
     * @return array
     */
    public function getPermissions(IPlayer $player){
        $group = $this->playerManager->getGroup($player);
        $groupPerms = $group->getPermissions();
        $userPerms = $this->playerManager->getUserPermissions($player);
        return array_merge($userPerms, $groupPerms);
    }
    
    /**
     * @param Player $player
     * @return null|PermissionAttachment
     */
    public function getAttachment(Player $player){
        $uniqueId = $this->getValidUUID($player);
        return $this->attachments[$uniqueId];
    }
    
    private function loadFactionsPlugin(){
        $pluginName = self::$settings["factions-plugin"];
        switch(strtolower($pluginName)){
            case "factionspro":
                $plugin = $this->getServer()->getPluginManager()->getPlugin("FactionsPro");
                if($plugin instanceof Plugin){
                    $this->factions = new FactionsPro();
                }
                break;
            case "piggyfactions":
                $plugin = $this->getServer()->getPluginManager()->getPlugin("PiggyFactions");
                if($plugin instanceof Plugin){
                    $this->factions = new PiggyFactions();
                    break;
                }
                break;
            default:
                $this->factions = null;
                break;
        }
    }
    
    /**
     * @param Player $player
     * @param string $prefix
     * @return bool
     */
    public function setPrefix(Player $player, ?string $prefix){
        $prefix === null ? $text = "" : $text = $prefix;
        $this->getPlayerManager()->setNode($player, "prefix", $text);
        return true;
    }
    
    /**
     * @return PlayerManager
     */
    public function getPlayerManager(){
        return $this->playerManager;
    }
    
    /**
     * @param Player $player
     * @param string $suffix
     * @return bool
     */
    public function setSuffix(Player $player, ?string $suffix){
        $suffix === null ? $text = "" : $text = $suffix;
        $this->getPlayerManager()->setNode($player, "suffix", $text);
        return true;
    }
    
    public function onDisable(){
        $this->unregisterPlayers();
    }
    
    public function unregisterPlayers(){
        foreach($this->getServer()->getOnlinePlayers() as $player){
            $this->unregisterPlayer($player);
        }
    }
    
    /**
     * @param Player $player
     */
    public function unregisterPlayer(Player $player){
        $uniqueId = $this->getValidUUID($player);
        if($uniqueId !== null){
            if(isset($this->attachments[$uniqueId])) $player->removeAttachment($this->attachments[$uniqueId]);
            unset($this->attachments[$uniqueId]);
        }
    }
    
    /**
     * @param $groupName
     * @return int
     */
    public function addGroup($groupName){
        $groupsData = $this->getGroupProvider()->getConfig()->getAll();
        if(!$this->isValidGroupName($groupName)) return self::INVALID;
        if(isset($groupsData[$groupName])) return self::EXISTS;
        $groupsData[$groupName] = ["alias" => "", "default" => false, "inheritance" => [], "permissions" => []];
        $this->getGroupProvider()->setAllData($groupsData);
        $this->updateGroups();
        return self::SUCCESS;
    }
    
    /**
     * @param $groupName
     * @return int
     */
    public function isValidGroupName($groupName){
        return preg_match('/[0-9a-zA-Z\xA1-\xFE]$/', $groupName);
    }
    
    /**
     * @return BlissGroup|null
     */
    public function getDefaultGroup(){
        foreach($this->getAllGroups() as $group){
            if($group->isDefault()) return $group;
        }
        return null;
    }
    
    /**
     * @return BlissGroup[]
     */
    public function getAllGroups(){
        return $this->groups;
    }
    
    /**
     * @param $name
     * @return BlissGroup|null
     */
    public function getGroup($name){
        if(!isset($this->groups[$name])){
            foreach($this->groups as $group){
                if($group->getAlias() === $name) return $group;
            }
            return null;
        }
        $group = $this->groups[$name];
        if(empty($group->getData())){
            return null;
        }
        return $group;
    }
    
    /**
     * @param $groupName
     * @return bool
     */
    public function removeGroup($groupName){
        if(!$this->isValidGroupName($groupName)) return self::MISSING;
        $groupsData = $this->getGroupProvider()->getConfig()->getAll();
        if(!isset($groupsData[$groupName])) return self::MISSING;
        unset($groupsData[$groupName]);
        $this->getGroupProvider()->setAllData($groupsData);
        $this->updateGroups();
        return self::SUCCESS;
    }
    
    /**
     * @param Player $player
     * @return mixed
     */
    public function getNametag(Player $player){
        $group = $this->getPlayerManager()->getGroup($player);
        $nametag = $group->getNode("nametag");
        if($nametag === null){
            $default = str_replace("{group}", $group->getName(), self::$settings["default"]["nametag"]);
            $nametag = $default;
            $group->setNode("nametag", $nametag);
        }
        $nametag = TextFormat::colorize($nametag);
        $nametag = $this->applyTags($nametag, $player);
        return $nametag;
    }
    
    /**
     * @param string $text
     * @param Player $player
     * @param string $message
     * @return mixed
     */
    public function applyTags(string $text, Player $player, string $message = ""){
        $text = str_replace("{name}", $player->getDisplayName(), $text);
        if($player->hasPermission("blissperms.colorchat")){
            $text = str_replace("{chat}", TextFormat::colorize($message), $text);
        }else{
            $text = str_replace("{chat}", TextFormat::clean($message), $text);
        }
        if($this->factions !== null){
            $text = str_replace("{facName}", $this->factions->getPlayerFaction($player), $text);
            $text = str_replace("{facRank}", $this->factions->getPlayerRank($player), $text);
        }else{
            $text = str_replace("{facName}", "", $text);
            $text = str_replace("{facRank}", "", $text);
        }
        $text = str_replace("{prefix}", $this->getPrefix($player), $text);
        $text = str_replace("{suffix}", $this->getSuffix($player), $text);
        return $text;
    }
    
    /**
     * @param Player $player
     * @return mixed|null|string
     */
    public function getPrefix(Player $player){
        return $this->getPlayerManager()->getNode($player, "prefix");
    }
    
    /**
     * @param Player $player
     * @return mixed|null|string
     */
    public function getSuffix(Player $player){
        return $this->getPlayerManager()->getNode($player, "suffix");
    }
    
    /**
     * @param Player $player
     * @param $message
     * @return mixed
     */
    public function getChatFormat(Player $player, $message){
        $group = $this->getPlayerManager()->getGroup($player);
        $chatFormat = $group->getNode("chat");
        if($chatFormat === null){
            $default = str_replace("{group}", $group->getName(), self::$settings["default"]["chat"]);
            $chatFormat = $default;
            $group->setNode("chat", $chatFormat);
        }
        $chatFormat = TextFormat::colorize($chatFormat);
        $chatFormat = $this->applyTags($chatFormat, $player, $message);
        return $chatFormat;
    }
}
