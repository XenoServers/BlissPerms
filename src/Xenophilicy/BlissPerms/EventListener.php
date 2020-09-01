<?php

namespace Xenophilicy\BlissPerms;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

/**
 * Class EventListener
 * @package Xenophilicy\BlissPerms
 */
class EventListener implements Listener {
    
    /** @var BlissPerms */
    private $plugin;
    
    public function __construct(BlissPerms $plugin){
        $this->plugin = $plugin;
    }
    
    /**
     * @param PlayerLoginEvent $event
     * @priority LOWEST
     */
    public function onPlayerLogin(PlayerLoginEvent $event){
        $player = $event->getPlayer();
        $this->plugin->registerPlayer($player);
    }
    
    /**
     * @param PlayerQuitEvent $event
     * @priority HIGHEST
     */
    public function onPlayerQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $this->plugin->unregisterPlayer($player);
    }
    
    /**
     * @param PlayerJoinEvent $event
     * @priority HIGH
     */
    public function onPlayerJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $nametag = $this->plugin->getNametag($player);
        $player->setNameTag($nametag);
    }
    
    /**
     * @param PlayerChatEvent $event
     * @priority HIGH
     */
    public function onPlayerChat(PlayerChatEvent $event){
        if($event->isCancelled()) return;
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $chatFormat = $this->plugin->getChatFormat($player, $message);
        $event->setFormat($chatFormat);
    }
}