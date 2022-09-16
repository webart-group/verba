<?php
namespace Verba\Mod\Notifier\Block;

class Ping extends \Verba\Block\Json{

    function build(){
        $this->content = 'pong';
        return $this->content;
    }

}
