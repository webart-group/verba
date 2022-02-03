<?php

class profile_purchasesList extends profile_ordersList
{

    protected $ownerField;

    protected $_orderSide = 'purchase';
    public $cfg = 'public public/profile/orders public/profile/purchases';

    public $listId = 'my_purchases';

    function prepare()
    {

        // Добавляется выборка по первому товару в заказе
        parent::prepare();

        $this->ownerField = $this->oh->getOwnerAttributeCode();

        if (!$this->list || !$this->userId || !is_numeric($this->userId) || $this->userId < 1) {

            throw new \Exception\Routing('Bad input params');

        }

        $walias = 'profile_purchases_list';
        $qm = $this->list->QM();
        $where = $qm->getWhere($walias);
        if ($where) {
            $qm->removeWhere($walias);
        }
        $qm->addWhere($this->userId, $walias, $this->ownerField);

    }

}
