<?php

namespace Xenophilicy\BlissPerms\Provider;

use pocketmine\utils\Config;
use Xenophilicy\BlissPerms\BlissPerms;
use Xenophilicy\BlissPerms\BlissRank;

/**
 * Class RankProvider
 * @package Xenophilicy\BlissPerms\Provider
 */
class RankProvider {
    
    private $config;
    private $plugin;
    
    /**
     * @param BlissPerms $plugin
     */
    public function __construct(BlissPerms $plugin){
        $this->plugin = $plugin;
        $this->plugin->saveResource("ranks.yml");
        $this->config = new Config($this->plugin->getDataFolder() . "ranks.yml", Config::YAML);
    }
    
    /**
     * @param BlissRank $rank
     * @return mixed
     */
    public function getData(BlissRank $rank){
        $name = $rank->getName();
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
     * @param BlissRank $rank
     * @param array $temp
     */
    public function setData(BlissRank $rank, array $temp){
        $name = $rank->getName();
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