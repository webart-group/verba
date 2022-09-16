<?php

class profile_ordersTab extends profile_contentCommon
{

    protected $_orderSide = false;

    public $U;
    protected $userId;
    protected $baseCfg = array();


    public $templates = array(
        'content' => '/profile/orders/tab.tpl'
    );
    public $bodyClass = 'profile-orders';
    public $coloredPanelCfg = false;

    public $tplvars = array(
        'MY_ORDERS_LIST' => 'My orders List'
    );

    function init()
    {

        parent::init();

        if (!$this->_orderSide) {
            throw new \Verba\Exception\Routing('Bad Params');
        }

        $this->bodyClass = 'profile-' . $this->_orderSide . 's';

        $listClassName = 'profile_' . $this->_orderSide . 'sList';


        $this->baseCfg['U'] = $this->U;
        $this->baseCfg['userId'] = $this->userId;

        $this->addItems(array(
            'MY_ORDERS_LIST' => new $listClassName($this, $this->baseCfg),
        ));
    }

    function build()
    {

        if (is_string($this->content)) {
            return $this->content;
        }

        $this->content = $this->tpl->parse(false, 'content');

        \Verba\Hive::setBackURL();

        return $this->content;
    }
}
