<?php

namespace Xenophilicy\BlissPerms\Command;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;
use Xenophilicy\BlissPerms\BlissPerms;

/**
 * Class SetPrefix
 * @package Xenophilicy\BlissPerms\Command
 */
class SetPrefix extends PluginCommand {
    
    private $plugin;
    
    /**
     * @param string $name
     * @param BlissPerms $plugin
     */
    public function __construct(string $name, BlissPerms $plugin){
        parent::__construct($name, $plugin);
        $this->plugin = $plugin;
        $this->setDescription("Set your prefix");
        $this->setPermission("blissperms.prefix");
    }
    
    /**
     * @param CommandSender $sender
     * @param $label
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $label, array $args): bool{
        if(!$sender->hasPermission($this->getPermission() . "self")){
            $sender->sendMessage(TF::RED . "You don't have permission to set prefixes");
            return false;
        }
        if(count($args) < 1){
            $sender->sendMessage(TF::RED . "Usage: /setprefix <prefix|false> [player]");
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
                $sender->sendMessage(TF::RED . "You don't have permission to set another player's prefix");
                return false;
            }
            $name = array_shift($args);
            $player = $this->plugin->getServer()->getPlayer($name);
            if($player === null){
                $sender->sendMessage(TF::RED . "Player is not online");
                return false;
            }
            if($text === false || $text === "false"){
                $this->plugin->setPrefix($player, null);
                $sender->sendMessage(TF::GREEN . "Prefix for " . TF::AQUA . $player->getName() . TF::GREEN . " has been reset");
                return true;
            }
            $this->plugin->setPrefix($player, $text);
            $sender->sendMessage(TF::GREEN . "Prefix for " . TF::AQUA . $player->getName() . TF::GREEN . " has been set to " . TF::YELLOW . $text);
            return true;
        }
        if(!$sender instanceof Player){
            $sender->sendMessage(TF::RED . "This is an in-game command only");
            return false;
        }
        if($text === false || $text === "false"){
            $this->plugin->setPrefix($sender, null);
            $sender->sendMessage(TF::GREEN . "Your prefix has been reset");
            return true;
        }
        $this->plugin->setPrefix($sender, $text);
        $sender->sendMessage(TF::GREEN . "Your prefix has been set to " . TF::YELLOW . $text);
        return true;
    }
}