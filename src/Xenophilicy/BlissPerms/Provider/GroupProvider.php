<?php

namespace Xenophilicy\BlissPerms\Provider;

use pocketmine\utils\Config;
use Xenophilicy\BlissPerms\BlissGroup;
use Xenophilicy\BlissPerms\BlissPerms;

/**
 * Class GroupProvider
 * @package Xenophilicy\BlissPerms\Provider
 */
class GroupProvider {
    
    private $config;
    private $plugin;
    
    /**
     * @param BlissPerms $plugin
     */
    public function __construct(BlissPerms $plugin){
        $this->plugin = $plugin;
        $this->plugin->saveResource("groups.yml");
        $this->config = new Config($this->plugin->getDataFolder() . "groups.yml", Config::YAML);
    }
    
    /**
     * @param BlissGroup $group
     * @return mixed
     */
    public function getData(BlissGroup $group){
        $name = $group->getName();
        if(!isset($this->getConfig()->getAll()[$name]) || !is_array($this->getConfig()->getAll()[$name])) return [];
        return $this->getConfig()->getAll()[$name];
    }
    
    /**
     * @return mixed
     */
    public function getConfig(){
        return $this->config;
    }
    
    /**
     * @param BlissGroup $group
     * @param array $temp
     */
    public function setData(BlissGroup $group, array $temp){
        $name = $group->getName();
        $this->config->set($name, $temp);
        $this->config->save();
    }
    
    /**
     * @param array $temp
     */
    public function setAllData(array $temp){
        $this->config->setAll($temp);
        $this->config->save();
    }
}