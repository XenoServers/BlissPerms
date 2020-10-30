<?php

namespace Xenophilicy\BlissPerms\Provider;

use pocketmine\utils\Config;
use Xenophilicy\BlissPerms\BlissPerms;
use Xenophilicy\BlissPerms\BlissTier;

/**
 * Class TierProvider
 * @package Xenophilicy\BlissPerms\Provider
 */
class TierProvider {
    
    private $config;
    private $plugin;
    
    /**
     * @param BlissPerms $plugin
     */
    public function __construct(BlissPerms $plugin){
        $this->plugin = $plugin;
        $this->plugin->saveResource("tiers.yml");
        $this->config = new Config($this->plugin->getDataFolder() . "tiers.yml", Config::YAML);
    }
    
    /**
     * @param BlissTier $tier
     * @return mixed
     */
    public function getData(BlissTier $tier){
        $name = $tier->getName();
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
     * @param BlissTier $tier
     * @param array $temp
     */
    public function setData(BlissTier $tier, array $temp){
        $name = $tier->getName();
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