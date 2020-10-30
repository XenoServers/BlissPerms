<?php

namespace Xenophilicy\BlissPerms;

/**
 * Class BlissRank
 * @package Xenophilicy\BlissPerms
 */
class BlissRank {
    
    /** @var string */
    private $name;
    /** @var BlissPerms */
    private $plugin;
    /** @var BlissRank[] */
    private $parents = [];
    
    public function __construct(BlissPerms $plugin, string $name){
        $this->plugin = $plugin;
        $this->name = $name;
    }
    
    public function __toString(): string{
        return $this->name;
    }
    
    public function getName(): string{
        return $this->name;
    }
    
    public function getAlias(): string{
        return $this->getNode("alias") ?? $this->name;
    }
    
    /**
     * @param string $node
     * @return null|mixed
     */
    public function getNode(string $node){
        if(!isset($this->getData()[$node])) return null;
        return $this->getData()[$node];
    }
    
    public function getData(): array{
        return $this->plugin->getRankProvider()->getData($this);
    }
    
    public function getPermissions(): array{
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
     * @return self[]
     */
    public function getParents(): array{
        if($this->parents === []){
            if(!is_array($this->getNode("inheritance"))){
                return [];
            }
            foreach($this->getNode("inheritance") as $name){
                $parent = $this->plugin->getRank($name);
                if($parent !== null) $this->parents[] = $parent;
            }
        }
        return $this->parents;
    }
    
    public function isDefault(): bool{
        return ($this->getNode("default") === true);
    }
    
    public function sortPermissions(): void{
        $temp = $this->getData();
        if(isset($temp["permissions"])){
            $temp["permissions"] = array_unique($temp["permissions"]);
            sort($temp["permissions"]);
        }
        $this->setData($temp);
    }
    
    public function setData(array $data): void{
        $this->plugin->getRankProvider()->setData($this, $data);
    }
}