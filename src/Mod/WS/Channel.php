<?php
namespace Mod\WS;

class Channel extends \Verba\Base implements ChannelInterface{

    /**
     * @var Channel\Parts
     */
    public $parts;

    public $loadable;
    /**
     * @var \Model\Item
     */
    public $OItem;


    /**
     * \Mod\WS\Channel constructor.
     * @param $Chchp Channel\Parts
     */
    function __construct($Chchp)
    {
        $this->parts = $Chchp;
    }

    function valid(){
        return is_object($this->parts) && $this->parts->isValid();
    }

    function __get($propName){
        if(is_object($this->parts) && property_exists($this->parts, $propName)){
            return $this->parts->$propName;
        }

        if(is_object($this->OItem)){
            return $this->OItem->$propName;
        }

        return null;
    }

    function getName(){
        return $this->parts->name;
    }

    function userHasAccess($U = null){
        if($U === null){
            $U = User();
        }
        if($U instanceof \Verba\User\Model\User){
            $userId = $U->getId();
        }else{
            $userId = (int)$U;
        }

        return $userId && $this->parts->userId == $userId;
    }

    function setParts($parts){
        if(!$parts instanceof Channel\Parts){
            return false;
        }

        $this->parts = $parts;
        return $this->parts;
    }

    static function initObject($channelName, $preload = true){
        if(is_object($channelName) && $channelName instanceof Channel\Parts){
            $Chchp = $channelName;
        }else{
            $Chchp = new Channel\Parts($channelName);
        }

        if(!$Chchp->isValid()){
            return false;
        }

        if($Chchp->ChannelClassName === null){
            $ChannelObject = new Channel($Chchp);
        }else{
            if(!class_exists($Chchp->ChannelClassName)){
                return false;
            }
            $ChannelObject = new $Chchp->ChannelClassName($Chchp);
        }

        if($ChannelObject->loadable && $preload){
            $ChannelObject->load();
        }

        return $ChannelObject;
    }

    function load(){}
}
