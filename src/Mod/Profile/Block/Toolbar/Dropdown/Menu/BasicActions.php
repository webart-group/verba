<?php
namespace Mod\Profile\Block\Toolbar\Dropdown\Menu;

class BasicActions extends \Verba\Block\Html {

    function init(){

        if(User()->getAuthorized()){
            $this->addItems([
                new BasicActions\Logout($this)
            ]);
        }
        $this->addItems(array(
            new BasicActions\Acp($this)
        ));
    }
}
