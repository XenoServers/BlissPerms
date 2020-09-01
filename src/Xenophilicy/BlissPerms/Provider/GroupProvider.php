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
    
    /** @var Config */
    private $config;
    /** @var BlissPerms */
    private $plugin;
    
    public function __construct(BlissPerms $plugin){
        $this->plugin = $plugin;
        $this->plugin->saveResource("groups.yml");
        $this->config = new Config($this->plugin->getDataFolder() . "groups.yml", Config::YAML);
    }
    
    public function getData(BlissGroup $group): array{
        $name = $group->getName();
        if(!isset($this->getConfig()->getAll()[$name]) || !is_array($this->getConfig()->getAll()[$name])) return [];
        return $this->getConfig()->getAll()[$name];
    }
    
    public function getConfig(): Config{
        return $this->config;
    }
    
    public function setData(BlissGroup $group, array $temp): void{
        $name = $group->getName();
        $this->config->set($name, $temp);
        $this->config->save();
    }
    
    public function setAllData(array $temp): void{
        $this->config->setAll($temp);
        $this->config->save();
    }
}