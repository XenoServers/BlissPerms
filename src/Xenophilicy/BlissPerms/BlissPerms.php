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
use Xenophilicy\BlissPerms\Command\Rank;
use Xenophilicy\BlissPerms\Command\SetPerm;
use Xenophilicy\BlissPerms\Command\SetPrefix;
use Xenophilicy\BlissPerms\Command\SetSuffix;
use Xenophilicy\BlissPerms\Data\PlayerManager;
use Xenophilicy\BlissPerms\Factions\FactionsPro;
use Xenophilicy\BlissPerms\Factions\PiggyFactions;
use Xenophilicy\BlissPerms\Provider\GroupProvider;
use Xenophilicy\BlissPerms\Provider\RankProvider;

/**
 * Class BlissPerms
 * @package Xenophilicy\BlissPerms
 */
class BlissPerms extends PluginBase {
    
    const MISSING = null;
    const INVALID = -1;
    const EXISTS = 0;
    const SUCCESS = 1;
    /** @var array */
    public static $settings;
    /** @var PlayerManager */
    private $playerManager;
    /** @var array */
    private $attachments = [];
    /** @var FactionsPro|PiggyFactions */
    private $factions;
    /** @var GroupProvider */
    private $groupProvider;
    /** @var RankProvider */
    private $rankProvider;
    /** @var array */
    private $groups = [];
    /** @var array */
    private $ranks = [];
    
    public function onLoad(): void{
        $this->saveDefaultConfig();
        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        self::$settings = $config->getAll();
        $this->playerManager = new PlayerManager($this);
    }
    
    public function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->registerCommands();
        $this->setProviders();
        $this->registerPlayers();
        $this->loadFactionsPlugin();
    }
    
    private function registerCommands(): void{
        $cmdMap = $this->getServer()->getCommandMap();
        $cmdMap->register("group", new Group("group", $this));
        $cmdMap->register("rank", new Rank("rank", $this));
        $cmdMap->register("setperm", new SetPerm("setperm", $this));
        $cmdMap->register("setprefix", new SetPrefix("setprefix", $this));
        $cmdMap->register("setsuffix", new SetSuffix("setsuffix", $this));
    }
    
    private function setProviders(): void{
        $this->groupProvider = new GroupProvider($this);
        $this->rankProvider = new RankProvider($this);
        $this->updateGroups();
        $this->updateRanks();
    }
    
    private function updateGroups(): void{
        $this->groups = [];
        foreach(array_keys($this->getGroupProvider()->getConfig()->getAll()) as $name){
            $this->groups[$name] = new BlissGroup($this, $name);
            $this->groups[$name]->sortPermissions();
        }
    }
    
    public function getGroupProvider(): GroupProvider{
        if(!$this->isValidProvider(true)) $this->setProviders();
        return $this->groupProvider;
    }
    
    private function isValidProvider(bool $group): bool{
        if($group) return $this->groupProvider instanceof GroupProvider;else return $this->rankProvider instanceof RankProvider;
    }
    
    private function updateRanks(): void{
        $this->ranks = [];
        foreach(array_keys($this->getRankProvider()->getConfig()->getAll()) as $name){
            $this->ranks[$name] = new BlissRank($this, $name);
            $this->ranks[$name]->sortPermissions();
        }
    }
    
    public function getRankProvider(): RankProvider{
        if(!$this->isValidProvider(false)) $this->setProviders();
        return $this->rankProvider;
    }
    
    private function registerPlayers(): void{
        foreach($this->getServer()->getOnlinePlayers() as $player){
            $this->registerPlayer($player);
        }
    }
    
    public function registerPlayer(Player $player): void{
        $uniqueId = $this->getValidUUID($player);
        if(!isset($this->attachments[$uniqueId])){
            $attachment = $player->addAttachment($this);
            $this->attachments[$uniqueId] = $attachment;
            $this->updatePermissions($player);
        }
    }
    
    public function getValidUUID(Player $player): ?string{
        $uuid = $player->getUniqueId();
        if($uuid instanceof UUID) return $uuid->toString();
        return null;
    }
    
    public function updatePermissions(IPlayer $player): void{
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
    
    public function getPermissions(Player $player): array{
        $group = $this->getPlayerManager()->getGroup($player);
        $rankPerms = is_null($this->getPlayerManager()->getRank($player)) ? [] : $this->getPlayerManager()->getRank($player)->getPermissions();
        $userPerms = $this->getPlayerManager()->getUserPermissions($player);
        return array_merge($userPerms, $group->getPermissions(), $rankPerms);
    }
    
    public function getPlayerManager(): PlayerManager{
        return $this->playerManager;
    }
    
    public function getAttachment(Player $player): ?PermissionAttachment{
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
    
    public function setPrefix(Player $player, ?string $prefix): void{
        $prefix === null ? $text = "" : $text = $prefix;
        $this->getPlayerManager()->setNode($player, "prefix", $text);
    }
    
    public function setSuffix(Player $player, ?string $suffix): void{
        $suffix === null ? $text = "" : $text = $suffix;
        $this->getPlayerManager()->setNode($player, "suffix", $text);
    }
    
    public function onDisable(): void{
        $this->unregisterPlayers();
    }
    
    public function unregisterPlayers(): void{
        foreach($this->getServer()->getOnlinePlayers() as $player){
            $this->unregisterPlayer($player);
        }
    }
    
    public function unregisterPlayer(Player $player): void{
        $uniqueId = $this->getValidUUID($player);
        if($uniqueId !== null){
            if(isset($this->attachments[$uniqueId])) $player->removeAttachment($this->attachments[$uniqueId]);
            unset($this->attachments[$uniqueId]);
        }
    }
    
    public function addRank(string $name): int{
        $data = $this->getRankProvider()->getConfig()->getAll();
        if(!$this->isValidName($name)) return self::INVALID;
        if(isset($data[$name])) return self::EXISTS;
        $data[$name] = ["alias" => "", "default" => false, "inheritance" => [], "permissions" => [], "format" => $name];
        $this->getRankProvider()->setAllData($data);
        $this->updateRanks();
        return self::SUCCESS;
    }
    
    /**
     * @param string $name
     * @return false|int
     */
    public function isValidName(string $name){
        return preg_match('/[0-9a-zA-Z\xA1-\xFE]$/', $name);
    }
    
    public function getDefaultRank(): ?BlissRank{
        foreach($this->getAllRanks() as $rank){
            if($rank->isDefault()) return $rank;
        }
        return null;
    }
    
    /**
     * @return BlissRank[]
     */
    public function getAllRanks(): array{
        return $this->ranks;
    }
    
    /**
     * @param $name
     * @return BlissRank|null
     */
    public function getRank($name): ?BlissRank{
        if(!isset($this->ranks[$name])){
            foreach($this->ranks as $rank){
                if($rank->getAlias() === $name) return $rank;
            }
            return null;
        }
        $rank = $this->ranks[$name];
        if(empty($rank->getData())){
            return null;
        }
        return $rank;
    }
    
    public function removeRank(string $name): ?int{
        if(!$this->isValidName($name)) return self::MISSING;
        $data = $this->getRankProvider()->getConfig()->getAll();
        if(!isset($data[$name])) return self::MISSING;
        unset($data[$name]);
        $this->getRankProvider()->setAllData($data);
        $this->updateRanks();
        return self::SUCCESS;
    }
    
    public function addGroup(string $name): int{
        $data = $this->getGroupProvider()->getConfig()->getAll();
        if(!$this->isValidName($name)) return self::INVALID;
        if(isset($data[$name])) return self::EXISTS;
        $data[$name] = ["alias" => "", "default" => false, "inheritance" => [], "permissions" => []];
        $this->getGroupProvider()->setAllData($data);
        $this->updateGroups();
        return self::SUCCESS;
    }
    
    public function getDefaultGroup(): ?BlissGroup{
        foreach($this->getAllGroups() as $group){
            if($group->isDefault()) return $group;
        }
        return null;
    }
    
    /**
     * @return BlissGroup[]
     */
    public function getAllGroups(): array{
        return $this->groups;
    }
    
    /**
     * @param $name
     * @return BlissGroup|null
     */
    public function getGroup($name): ?BlissGroup{
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
    
    public function removeGroup(string $name): ?int{
        if(!$this->isValidName($name)) return self::MISSING;
        $data = $this->getGroupProvider()->getConfig()->getAll();
        if(!isset($data[$name])) return self::MISSING;
        unset($data[$name]);
        $this->getGroupProvider()->setAllData($data);
        $this->updateGroups();
        return self::SUCCESS;
    }
    
    public function getNametag(Player $player): string{
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
    
    public function applyTags(string $text, Player $player, string $message = ""): string{
        $text = str_replace(["{name}", "{prefix}", "{suffix}"], [$player->getDisplayName(), $this->getPrefix($player), $this->getSuffix($player)], $text);
        if($player->hasPermission("blissperms.colorchat")){
            $text = str_replace("{chat}", TextFormat::colorize($message), $text);
        }else{
            $text = str_replace("{chat}", TextFormat::clean($message), $text);
        }
        if($this->factions !== null){
            $text = str_replace("{facName}", $this->factions->getFaction($player), $text);
            $text = str_replace("{facRank}", $this->factions->getFactionRank($player), $text);
        }else{
            $text = str_replace("{facName}", "", $text);
            $text = str_replace("{facRank}", "", $text);
        }
        $rank = is_null($this->getPlayerManager()->getRank($player)) ? "" : $this->getPlayerManager()->getRank($player)->getNode("format");
        $text = str_replace("{rank}", $rank, $text);
        return $text;
    }
    
    public function getPrefix(Player $player): ?string{
        return $this->getPlayerManager()->getNode($player, "prefix");
    }
    
    public function getSuffix(Player $player): ?string{
        return $this->getPlayerManager()->getNode($player, "suffix");
    }
    
    public function getChatFormat(Player $player, string $message): string{
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
