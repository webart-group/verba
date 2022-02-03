<?php
namespace Mod\Profile\Block\Toolbar;

class Tools  extends \Verba\Block\Html{

    protected $orderNotifyAgents = [];
    public $role = 'user-toolbar';

    function init() {

        $U = User();

        if($U->haveStore()){
            $this->addItems([
                new Tools\Divider($this),
                new Tool\Store\Events($this),
                new Tool\Store\Sells($this),
                new Tool\Store\Bids($this),
            ]);
        }

        $this->addItems(array(
            new Tool\User\Events($this),
            new Tool\User\Purchases($this),
            new Tool\User\Account($this),
        ));

        $this->addItems(new \Mod\Notifier\Block\Instance($this));
    }

    function orderNotifyAgent($Item){
        $this->orderNotifyAgents[] = $Item;
    }

    function build(){
        if(count($this->orderNotifyAgents)){
            $r = array();
            foreach ($this->orderNotifyAgents as $I){
                $r['i'.count($r)] = $I->notifierAgent;
            }

            $this->addJsAfter("

      if(!window.NotifierInstance){
        return false;
      }
      window.NotifierInstance.initAgents(".\json_encode($r, JSON_FORCE_OBJECT).");
      ");
        }
        parent::build();
    }
}
