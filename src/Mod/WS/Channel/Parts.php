<?php
namespace Mod\WS\Channel;

class Parts extends \Verba\Base {

    protected $valid = false;

    public $type;
    public $namespace;
    public $private;
    public $name;
    public $userId;
    public $storeId;

    public $ChannelClassName;


    function __construct($name, $parse = true)
    {
        $this->name = trim($name);

        if($parse){
            $this->parse();
        }
    }

    function isValid(){
        return $this->valid;
    }

    function parse(){
        $this->valid = false;
        if(!is_string($this->name)
            || !preg_match(
                "/^(\\$)?(?:([a-z]+)\:)?([a-z]+)(?:(\d+)u(\d+)|(?:\#(\d+))|(\d+))$/i"
                , $this->name, $_buf)){
            return false;
        }
        $this->private = $_buf[1] == '$' ? true : false;
        $this->namespace = $_buf[2];
        $this->type = $_buf[3];

        // Канал магазина
        if($this->type == 'str'){
            $this->storeId = (int)$_buf[4];
            $this->userId = (int)$_buf[5];
            $this->loadable = true;
            $this->ChannelClassName = '\Mod\Chatik\Channel\Store';

            // канал извещения пользователя
        }elseif($this->type == 'usrntf'){
            $this->userId = (int)$_buf[6];
            $this->loadable = false;
            $this->ChannelClassName = '\Mod\Notifier\WS\Channel\User';
        }elseif($this->type == 'store'){
            $this->loadable = false;
            $this->storeId = (int)$_buf[7];
            $this->ChannelClassName = '\Mod\Notifier\WS\Channel\Store';
        }else{
            return false;
        }

        $this->valid = true;

        return $this->valid;
    }
}
