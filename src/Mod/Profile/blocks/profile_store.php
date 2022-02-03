<?php

class profile_store extends profile_contentTraders
{

    public $bodyClass = 'profile-store';

    public $coloredPanelCfg = false;

    public $titleLangKey = false;

    public $templates = array(
        'content' => '/profile/store/tab.tpl'
    );

    public $tplvars = array(
        'ALERT_ACCOUNT_REQUIRE' => '',
    );

    function init()
    {

        parent::init();

        $this->addItems(array(
            'ALERT_ACCOUNT_REQUIRE' => new profile_warnNoAcc($this),
        ));
    }

    function route()
    {
        switch ($this->rq->node) {
            case 'update':
                $b = new store_update($this);
                break;
            case 'news':
                $b = new profile_storeNewsRouter($this->rq->shift());
                break;
        }

        if (isset($b)) {
            return $b->route();
        }


        $this->addItems(array(
            'STORE_NEWS' => new page_coloredPanel($this, array(
                'items' => array((new profile_storeNewsRouter($this->rq->shift()))->route()),
                'title' => \Verba\Lang::get('store news list title'),
                'scheme' => 'green',
            ))
        ));

        return $this;
    }

    // Profile Store Page
    function build()
    {

        if (is_string($this->content) || !is_integer($this->userId)) {
            return $this->content;
        }

        $_store = \Verba\_oh('store');

        $aef = $_store->initForm(array(
            'cfg' => 'public /public/profile/store',
            'block' => $this,
            'action' => 'edit',
            'iid' => $this->storeId,
        ));

        $storeProperties = new page_coloredPanel($this, array(
            'title' => \Verba\Lang::get('store propsPanel title'),
            'content' => $aef->makeForm(),
            'scheme' => 'green',
        ));

        $storeProperties->prepare();

        $this->tpl->assign(array(
            'STORE_SETTING_FORM' => $storeProperties->build(),
        ));

        $this->content = $this->tpl->parse(false, 'content');

        \Verba\Hive::saveBackURL();

        return $this->content;

    }

}
