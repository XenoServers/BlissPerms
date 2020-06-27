<?php

namespace Xenophilicy\BlissPerms\Command;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use Xenophilicy\BlissPerms\BlissPerms;

/**
 * Class SetSuffix
 * @package Xenophilicy\BlissPerms\Command
 */
class SetSuffix extends PluginCommand {
    
    private $plugin;
    
    /**
     * @param string $name
     * @param BlissPerms $plugin
     */
    public function __construct(string $name, BlissPerms $plugin){
        parent::__construct($name, $plugin);
        $this->plugin = $plugin;
        $this->setDescription("Set your suffix");
        $this->setPermission("blissperms.suffix");
    }
    
    /**
     * @param CommandSender $sender
     * @param $label
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $label, array $args): bool{
        if(!$sender->hasPermission($this->getPermission() . "self")){
            $sender->sendMessage(TF::RED . "You don't have permission to set suffixes");
            return false;
        }
        if(count($args) < 1){
            $sender->sendMessage(TF::RED . "Usage: /setsuffix <suffix|false> [player]");
            return false;
        }
        if($sender->hasPermission("blissperms.colorchat")){
            $text = TF::colorize(array_shift($args));
        }else{
            $text = TF::clean(array_shift($args));
        }
        if(in_array($text, BlissPerms::$settings["prohibited-names"])){
            $sender->sendMessage(TF::RED . "That name is not allowed");
            return false;
        }
        if(count($args) > 0){
            if(!$sender->hasPermission($this->getPermission())){
                $sender->sendMessage(TF::RED . "You don't have permission to set another player's suffix");
                return false;
            }
            $name = array_shift($args);
            $player = $this->plugin->getServer()->getPlayer($name);
            if($player === null){
                $sender->sendMessage(TF::RED . "Player is not online");
                return false;
            }
            if($text === false || $text === "false"){
                $this->plugin->setSuffix($player, null);
                $sender->sendMessage(TF::GREEN . "Suffix for " . TF::AQUA . $player->getName() . TF::GREEN . " has been reset");
                return true;
            }
            $this->plugin->setSuffix($player, $text);
            $sender->sendMessage(TF::GREEN . "Suffix for " . TF::AQUA . $player->getName() . TF::GREEN . " has been set to " . TF::YELLOW . $text);
            return true;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TF::RED . "This is an in-game command only");
            return false;
        }
        if($text === false || $text === "false"){
            $this->plugin->setSuffix($sender, null);
            $sender->sendMessage(TF::GREEN . "Your suffix has been reset");
            return true;
        }
        $this->plugin->setSuffix($sender, $text);
        $sender->sendMessage(TF::GREEN . "Your suffix has been set to " . TF::YELLOW . $text);
        return true;
    }
}