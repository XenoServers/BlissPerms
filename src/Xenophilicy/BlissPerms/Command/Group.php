<?php

namespace Xenophilicy\BlissPerms\Command;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use Xenophilicy\BlissPerms\BlissPerms;

/**
 * Class Group
 * @package Xenophilicy\BlissPerms\Command
 */
class Group extends PluginCommand {
    
    private $plugin;
    
    /**
     * @param string $name
     * @param BlissPerms $plugin
     */
    public function __construct(string $name, BlissPerms $plugin){
        parent::__construct($name, $plugin);
        $this->plugin = $plugin;
        $this->setDescription("Manage BlissPerms groups");
        $this->setPermission("blissperms.group");
    }
    
    /**
     * @param CommandSender $sender
     * @param string $label
     * @param string[] $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $label, array $args): bool{
        if(count($args) === 0){
            $sender->sendMessage(TF::RED . "Usage: /group <add <group>|remove <group>|set <player> <group>|list>");
            return false;
        }
        $mode = array_shift($args);
        switch($mode){
            case "add":
            case "new":
            case "create":
                if(!$sender->hasPermission("blissperms.group.add")){
                    $sender->sendMessage(TF::RED . "You don't have permission to create groups");
                    return false;
                }
                if(count($args) !== 1){
                    $sender->sendMessage(TF::RED . "Usage: /group add <group>");
                    return false;
                }
                $name = array_shift($args);
                $result = $this->plugin->addGroup($name);
                if($result === BlissPerms::SUCCESS){
                    $sender->sendMessage(TF::GREEN . "Created group " . TF::AQUA . $name);
                }elseif($result === BlissPerms::EXISTS){
                    $sender->sendMessage(TF::RED . "That group already exists");
                }else{
                    $sender->sendMessage(TF::RED . "That group name is invalid");
                }
                return true;
            case "del":
            case "delete":
            case "rem":
            case "remove":
                if(!$sender->hasPermission("blissperms.group.remove")){
                    $sender->sendMessage(TF::RED . "You don't have permission to delete groups");
                    return false;
                }
                if(count($args) !== 1){
                    $sender->sendMessage(TF::RED . "Usage: /group remove <group>");
                    return false;
                }
                $name = array_shift($args);
                $result = $this->plugin->removeGroup($name);
                if($result === BlissPerms::SUCCESS){
                    $sender->sendMessage(TF::GREEN . "Removed group " . TF::AQUA . $name);
                }elseif($result === BlissPerms::INVALID){
                    $sender->sendMessage(TF::RED . "That group name is invalid");
                }else{
                    $sender->sendMessage(TF::RED . "That group doesn't exist");
                }
                return true;
            case "list":
            case "all":
            case "show":
                if(!$sender->hasPermission("blissperms.group.list")){
                    $sender->sendMessage(TF::RED . "You don't have permission to list groups");
                    return false;
                }
                $sender->sendMessage(TF::GREEN . "All registered groups: ");
                foreach($this->plugin->getAllGroups() as $group){
                    $sender->sendMessage(TF::AQUA . " - " . $group);
                }
                return true;
            case "set":
            case "apply":
                if(!$sender->hasPermission("blissperms.group.set")){
                    $sender->sendMessage(TF::RED . "You don't have permission to set a player's group");
                    return false;
                }
                if(count($args) !== 2){
                    $sender->sendMessage(TF::RED . "Usage: /group set <player> <group>");
                    return false;
                }
                $name = array_shift($args);
                $player = $this->plugin->getServer()->getPlayer($name);
                if($player === null){
                    $sender->sendMessage(TF::RED . "Player is not online");
                    return false;
                }
                $groupName = array_shift($args);
                $group = $this->plugin->getGroup($groupName);
                if($group === null){
                    $sender->sendMessage(TF::RED . "That group doesn't exist");
                    return false;
                }
                $this->plugin->getPlayerManager()->setGroup($player, $group);
                if($player instanceof Player){
                    $this->plugin->updatePermissions($player);
                    $player->sendMessage(TF::GREEN . "Your group was set to " . TF::AQUA . $group->getName());
                    if($sender === $player) return true;
                }
                $sender->sendMessage(TF::GREEN . "Set group for " . TF::YELLOW . $player->getName() . TF::GREEN . " to " . TF::AQUA . $group->getName());
                return true;
            default:
                $sender->sendMessage(TF::RED . "Usage: /group <add <group>|remove <group>|set <player> <group>|list>");
                return false;
        }
    }
}