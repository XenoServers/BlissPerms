<?php

namespace Xenophilicy\BlissPerms;

/**
 * Class BlissGroup
 * @package Xenophilicy\BlissPerms
 */
class BlissGroup {
    
    private $name;
    private $plugin;
    private $parents = [];
    
    /**
     * @param BlissPerms $plugin
     * @param $name
     */
    public function __construct(BlissPerms $plugin, $name){
        $this->plugin = $plugin;
        $this->name = $name;
    }
    
    /**
     * @return mixed
     */
    public function __toString(){
        return $this->name;
    }
    
    /**
     * @return mixed
     */
    public function getName(){
        return $this->name;
    }
    
    /**
     * @return mixed
     */
    public function getAlias(){
        if($this->getNode("alias") === null) return $this->name;
        return $this->getNode("alias");
    }
    
    /**
     * @param $node
     * @return null|mixed
     */
    public function getNode($node){
        if(!isset($this->getData()[$node])) return null;
        return $this->getData()[$node];
    }
    
    /**
     * @return mixed
     */
    public function getData(){
        return $this->plugin->getGroupProvider()->getData($this);
    }
    
    /**
     * @return array
     */
    public function getPermissions(){
        $permissions = $this->getNode("permissions");
        if(!is_array($permissions)){
            return [];
        }
        foreach($this->getParents() as $parent){
            $parentPerms = $parent->getPermissions();
            if($parentPerms === null) $parentPerms = [];
            $permissions = array_merge($parentPerms, $permissions);
        }
        return $permissions;
    }
    
    /**
     * @return BlissGroup[]
     */
    public function getParents(){
        if($this->parents === []){
            if(!is_array($this->getNode("inheritance"))){
                return [];
            }
            foreach($this->getNode("inheritance") as $parentGroupName){
                $parentGroup = $this->plugin->getGroup($parentGroupName);
                if($parentGroup !== null) $this->parents[] = $parentGroup;
            }
        }
        return $this->parents;
    }
    
    /**
     * @return bool
     */
    public function isDefault(){
        return ($this->getNode("default") === true);
    }
    
    /**
     * @param $node
     * @param $value
     */
    public function setNode($node, $value){
        $temp = $this->getData();
        $temp[$node] = $value;
        $this->setData($temp);
    }
    
    /**
     * @param array $data
     */
    public function setData(array $data){
        $this->plugin->getGroupProvider()->setData($this, $data);
    }
    
    public function sortPermissions(){
        $temp = $this->getData();
        if(isset($temp["permissions"])){
            $temp["permissions"] = array_unique($temp["permissions"]);
            sort($temp["permissions"]);
        }
        $this->setData($temp);
    }
}