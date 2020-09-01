<?php

namespace Xenophilicy\BlissPerms\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat as TF;
use Xenophilicy\BlissPerms\BlissPerms;

/**
 * Class Rank
 * @package Xenophilicy\BlissPerms\Command
 */
class Rank extends Command implements PluginIdentifiableCommand {
    
    /** @var BlissPerms */
    private $plugin;
    
    public function __construct(string $name, BlissPerms $plugin){
        parent::__construct($name);
        $this->plugin = $plugin;
        $this->setDescription("Manage BlissPerms ranks");
        $this->setPermission("blissperms.rank");
    }
    
    public function execute(CommandSender $sender, string $label, array $args): bool{
        if(count($args) === 0){
            $sender->sendMessage(TF::RED . "Usage: /rank <add <rank>|remove <rank>|set <player> <rank>|perm <player> <rank>|list>");
            return false;
        }
        $mode = array_shift($args);
        switch($mode){
            case "add":
            case "new":
            case "create":
                if(!$sender->hasPermission("blissperms.rank.add")){
                    $sender->sendMessage(TF::RED . "You don't have permission to create ranks");
                    return false;
                }
                if(count($args) !== 1){
                    $sender->sendMessage(TF::RED . "Usage: /rank add <rank>");
                    return false;
                }
                $name = array_shift($args);
                $result = $this->plugin->addRank($name);
                if($result === BlissPerms::SUCCESS){
                    $sender->sendMessage(TF::GREEN . "Created rank " . TF::AQUA . $name);
                }elseif($result === BlissPerms::EXISTS){
                    $sender->sendMessage(TF::RED . "That rank already exists");
                }else{
                    $sender->sendMessage(TF::RED . "That rank name is invalid");
                }
                return true;
            case "del":
            case "delete":
            case "rem":
            case "remove":
                if(!$sender->hasPermission("blissperms.rank.remove")){
                    $sender->sendMessage(TF::RED . "You don't have permission to delete ranks");
                    return false;
                }
                if(count($args) !== 1){
                    $sender->sendMessage(TF::RED . "Usage: /rank remove <rank>");
                    return false;
                }
                $name = array_shift($args);
                $result = $this->plugin->removeRank($name);
                if($result === BlissPerms::SUCCESS){
                    $sender->sendMessage(TF::GREEN . "Removed rank " . TF::AQUA . $name);
                }elseif($result === BlissPerms::INVALID){
                    $sender->sendMessage(TF::RED . "That rank name is invalid");
                }else{
                    $sender->sendMessage(TF::RED . "That rank doesn't exist");
                }
                return true;
            case "list":
            case "all":
            case "show":
                if(!$sender->hasPermission("blissperms.rank.list")){
                    $sender->sendMessage(TF::RED . "You don't have permission to list ranks");
                    return false;
                }
                $sender->sendMessage(TF::GREEN . "All registered ranks: ");
                foreach($this->plugin->getAllRanks() as $rank){
                    $sender->sendMessage(TF::AQUA . " - " . $rank);
                }
                return true;
            case "set":
            case "apply":
                if(!$sender->hasPermission("blissperms.rank.set")){
                    $sender->sendMessage(TF::RED . "You don't have permission to set a player's rank");
                    return false;
                }
                if(count($args) !== 2){
                    $sender->sendMessage(TF::RED . "Usage: /rank set <player> <rank>");
                    return false;
                }
                $name = array_shift($args);
                $player = $this->plugin->getServer()->getPlayer($name);
                if($player === null){
                    $sender->sendMessage(TF::RED . "Player is not online");
                    return false;
                }
                $rankName = array_shift($args);
                if(strtolower($rankName) === "none"){
                    $this->plugin->getPlayerManager()->setRank($player, null);
                    if($player instanceof Player){
                        $this->plugin->updatePermissions($player);
                        $player->sendMessage(TF::GREEN . "Your rank was reset to NONE");
                        if($sender === $player) return true;
                    }
                    $sender->sendMessage(TF::GREEN . "Reset rank for " . TF::YELLOW . $player->getName());
                    return true;
                }
                $rank = $this->plugin->getRank($rankName);
                if($rank === null){
                    $sender->sendMessage(TF::RED . "That rank doesn't exist");
                    return false;
                }
                $this->plugin->getPlayerManager()->setRank($player, $rank);
                if($player instanceof Player){
                    $this->plugin->updatePermissions($player);
                    $player->sendMessage(TF::GREEN . "Your rank was set to " . TF::AQUA . $rank->getName());
                    if($sender === $player) return true;
                }
                $sender->sendMessage(TF::GREEN . "Set rank for " . TF::YELLOW . $player->getName() . TF::GREEN . " to " . TF::AQUA . $rank->getName());
                return true;
            case "perm":
            case "permission":
                if(!$sender->hasPermission("blissperms.rank.set")){
                    $sender->sendMessage(TF::RED . "You don't have permission to apply player rank permissions");
                    return false;
                }
                if(count($args) !== 2){
                    $sender->sendMessage(TF::RED . "Usage: /rank perm <player> <rank>");
                    return false;
                }
                $name = array_shift($args);
                $player = $this->plugin->getServer()->getPlayer($name);
                if($player === null){
                    $sender->sendMessage(TF::RED . "Player is not online");
                    return false;
                }
                $rankName = array_shift($args);
                $rank = $this->plugin->getRank($rankName);
                if($rank === null){
                    $sender->sendMessage(TF::RED . "That rank doesn't exist or doesn't have permissions");
                    return false;
                }
                $permissions = $rank->getPermissions();
                foreach($permissions as $permission) $this->plugin->getPlayerManager()->setPermission($player, $permission);
                if($player instanceof Player) $this->plugin->updatePermissions($player);
                $sender->sendMessage(TF::GREEN . "Applied rank permissions for " . TF::YELLOW . $rank->getName() . TF::GREEN . " to " . TF::AQUA . $player->getName());
                return true;
            default:
                $sender->sendMessage(TF::RED . "Usage: /rank <add <rank>|remove <rank>|set <player> <rank>|perm <player> <rank>|list>");
                return false;
        }
    }
    
    public function getPlugin(): Plugin{
        return $this->plugin;
    }
}