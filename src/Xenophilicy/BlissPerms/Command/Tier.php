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
 * Class Tier
 * @package Xenophilicy\BlissPerms\Command
 */
class Tier extends Command implements PluginIdentifiableCommand {
    
    /** @var BlissPerms */
    private $plugin;
    
    public function __construct(string $name, BlissPerms $plugin){
        parent::__construct($name);
        $this->plugin = $plugin;
        $this->setDescription("Manage BlissPerms tiers");
        $this->setPermission("blissperms.tier");
    }
    
    public function execute(CommandSender $sender, string $label, array $args): bool{
        if(count($args) === 0){
            $sender->sendMessage(TF::RED . "Usage: /tier <add <tier>|remove <tier>|set <player> <tier>|perm <player> <tier>|list>");
            return false;
        }
        $mode = array_shift($args);
        switch($mode){
            case "add":
            case "new":
            case "create":
                if(!$sender->hasPermission("blissperms.tier.add")){
                    $sender->sendMessage(TF::RED . "You don't have permission to create tiers");
                    return false;
                }
                if(count($args) !== 1){
                    $sender->sendMessage(TF::RED . "Usage: /tier add <tier>");
                    return false;
                }
                $name = array_shift($args);
                $result = $this->plugin->addTier($name);
                if($result === BlissPerms::SUCCESS){
                    $sender->sendMessage(TF::GREEN . "Created tier " . TF::AQUA . $name);
                }elseif($result === BlissPerms::EXISTS){
                    $sender->sendMessage(TF::RED . "That tier already exists");
                }else{
                    $sender->sendMessage(TF::RED . "That tier name is invalid");
                }
                return true;
            case "del":
            case "delete":
            case "rem":
            case "remove":
                if(!$sender->hasPermission("blissperms.tier.remove")){
                    $sender->sendMessage(TF::RED . "You don't have permission to delete tiers");
                    return false;
                }
                if(count($args) !== 1){
                    $sender->sendMessage(TF::RED . "Usage: /tier remove <tier>");
                    return false;
                }
                $name = array_shift($args);
                $result = $this->plugin->removeTier($name);
                if($result === BlissPerms::SUCCESS){
                    $sender->sendMessage(TF::GREEN . "Removed tier " . TF::AQUA . $name);
                }elseif($result === BlissPerms::INVALID){
                    $sender->sendMessage(TF::RED . "That tier name is invalid");
                }else{
                    $sender->sendMessage(TF::RED . "That tier doesn't exist");
                }
                return true;
            case "list":
            case "all":
            case "show":
                if(!$sender->hasPermission("blissperms.tier.list")){
                    $sender->sendMessage(TF::RED . "You don't have permission to list tiers");
                    return false;
                }
                $sender->sendMessage(TF::GREEN . "All registered tiers: ");
                foreach($this->plugin->getAllTiers() as $tier){
                    $sender->sendMessage(TF::AQUA . " - " . $tier);
                }
                return true;
            case "set":
            case "apply":
                if(!$sender->hasPermission("blissperms.tier.set")){
                    $sender->sendMessage(TF::RED . "You don't have permission to set a player's tier");
                    return false;
                }
                if(count($args) !== 2){
                    $sender->sendMessage(TF::RED . "Usage: /tier set <player> <tier>");
                    return false;
                }
                $name = array_shift($args);
                $player = $this->plugin->getServer()->getPlayer($name);
                if($player === null){
                    $sender->sendMessage(TF::RED . "Player is not online");
                    return false;
                }
                $tierName = array_shift($args);
                if(strtolower($tierName) === "none"){
                    $this->plugin->getPlayerManager()->setTier($player, null);
                    if($player instanceof Player){
                        $this->plugin->updatePermissions($player);
                        $player->sendMessage(TF::GREEN . "Your tier was reset to NONE");
                        if($sender === $player) return true;
                    }
                    $sender->sendMessage(TF::GREEN . "Reset tier for " . TF::YELLOW . $player->getName());
                    return true;
                }
                $tier = $this->plugin->getTier($tierName);
                if($tier === null){
                    $sender->sendMessage(TF::RED . "That tier doesn't exist");
                    return false;
                }
                $this->plugin->getPlayerManager()->setTier($player, $tier);
                if($player instanceof Player){
                    $this->plugin->updatePermissions($player);
                    $player->sendMessage(TF::GREEN . "Your tier was set to " . TF::AQUA . $tier->getName());
                    if($sender === $player) return true;
                }
                $sender->sendMessage(TF::GREEN . "Set tier for " . TF::YELLOW . $player->getName() . TF::GREEN . " to " . TF::AQUA . $tier->getName());
                return true;
            case "perm":
            case "permission":
                if(!$sender->hasPermission("blissperms.tier.set")){
                    $sender->sendMessage(TF::RED . "You don't have permission to apply player tier permissions");
                    return false;
                }
                if(count($args) !== 2){
                    $sender->sendMessage(TF::RED . "Usage: /tier perm <player> <tier>");
                    return false;
                }
                $name = array_shift($args);
                $player = $this->plugin->getServer()->getPlayer($name);
                if($player === null){
                    $sender->sendMessage(TF::RED . "Player is not online");
                    return false;
                }
                $tierName = array_shift($args);
                $tier = $this->plugin->getTier($tierName);
                if($tier === null){
                    $sender->sendMessage(TF::RED . "That tier doesn't exist or doesn't have permissions");
                    return false;
                }
                $permissions = $tier->getPermissions();
                foreach($permissions as $permission) $this->plugin->getPlayerManager()->setPermission($player, $permission);
                if($player instanceof Player) $this->plugin->updatePermissions($player);
                $sender->sendMessage(TF::GREEN . "Applied tier permissions for " . TF::YELLOW . $tier->getName() . TF::GREEN . " to " . TF::AQUA . $player->getName());
                return true;
            default:
                $sender->sendMessage(TF::RED . "Usage: /tier <add <tier>|remove <tier>|set <player> <tier>|perm <player> <tier>|list>");
                return false;
        }
    }
    
    public function getPlugin(): Plugin{
        return $this->plugin;
    }
}