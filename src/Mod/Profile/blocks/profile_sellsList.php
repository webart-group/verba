<?php

class profile_sellsList extends profile_ordersList
{

    protected $_orderSide = 'sell';
    public $cfg = 'public public/profile/orders public/profile/sells';
    /**
     * @var \Model\Store
     */
    public $Store;

    function init()
    {

        parent::init();

        if ($this->U) {
            $this->Store = $this->U->Stores()->getStore();
        }
        if (!$this->Store
            || !$this->Store instanceof \Model\Store
            || !$this->Store->getId()
        ) {
            throw new \Exception\Routing('Unknown param');
        }

    }

    function route()
    {
        parent::route();
        if (!$this->Store || !$this->Store instanceof \Model\Store
            || !$this->userId
            || $this->Store->owner != $this->userId) {
            throw new \Exception\Routing('Bad user content');
        }
        return $this;
    }

    function prepare()
    {

        // Добавляется выборка по первому товару в заказе
        parent::prepare();


        if (!$this->list) {

            throw new \Exception\Routing('Bad input params');

        }
        // Добавление условия выборки - искать по магазину
        $walias = 'profile_store_where';
        $qm = $this->list->QM();
        $where = $qm->getWhere($walias);
        if ($where) {
            $qm->removeWhere($walias);
        }
        $qm->addWhere($this->Store->getId(), $walias, 'storeId');

    }

}

?>