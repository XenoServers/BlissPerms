<?php

namespace Xenophilicy\BlissPerms\Command;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat as TF;
use Xenophilicy\BlissPerms\BlissPerms;

/**
 * Class SetPerm
 * @package Xenophilicy\BlissPerms\Command
 */
class SetPerm extends PluginCommand {
    
    private $plugin;
    
    /**
     * @param string $name
     * @param BlissPerms $plugin
     */
    public function __construct(string $name, BlissPerms $plugin){
        parent::__construct($name, $plugin);
        $this->plugin = $plugin;
        $this->setDescription("Set player permissions");
        $this->setPermission("blissperms.setperm");
    }
    
    /**
     * @param CommandSender $sender
     * @param $label
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $label, array $args): bool{
        if(!$sender->hasPermission($this->getPermission())){
            $sender->sendMessage(TF::RED . "You don't have permission to set permissions");
            return false;
        }
        if(count($args) !== 2){
            $sender->sendMessage(TF::RED . "Usage: /setperm <player> <permission>");
            return false;
        }
        $name = array_shift($args);
        $player = $this->plugin->getServer()->getPlayer($name);
        if($player === null){
            $sender->sendMessage(TF::RED . "Player is not online");
            return false;
        }
        $permission = array_shift($args);
        $this->plugin->getPlayerManager()->setPermission($player, $permission);
        $this->plugin->updatePermissions($player);
        $sender->sendMessage(TF::GREEN . "Permission " . TF::AQUA . $permission . TF::GREEN . " added to " . TF::AQUA . $player->getName());
        return true;
    }
}