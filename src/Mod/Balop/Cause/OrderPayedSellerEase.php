<?php
namespace Mod\Balop\Cause;

class OrderPayedSellerEase extends Ordered
{

    protected $otype = 'balop';
    protected $block = 1;
    protected $_itemClassSuffixRequired = 'OrderPayedSellerGravity';

    function loadItem()
    {
        if ($this->iid) {
            return parent::loadItem();
        }

// !!! При инциации этой причины должен быть в параметрах передан
// primitiveId как id заказа
        if ($this->primitiveId) {
// Ищем балансовую операцию по зачислению средств
// На кошелек торговца в счет этого заказа

            $_balop = \Verba\_oh('balop');
            $_order = \Verba\_oh('order');

            $qm = new \Verba\QueryMaker($_balop,false, true);
            $qm->addWhere($this->Acc->getId(), 'accountId');
            $qm->addWhere($this->_itemClassSuffixRequired, 'cause');
            $qm->addWhere($_order->getID(), 'primitiveOt');
            $qm->addWhere($this->primitiveId, 'primitiveId');
            $qm->addWhere(1, 'block');
            $qm->addWhere(1, 'active');

            $sqlr = $qm->run();
            if (!$sqlr || $sqlr->getNumRows() != 1) {
                return false;
            }

            return new \Model\Item($sqlr->fetchRow());
        }
    }

    function calcSum()
    {
        return abs($this->_i->sumout) * -1;
    }

    function getDescription()
    {
        $r = \Verba\Lang::get('balop descriptions orderPayedSellerEase', array(
            'order_code' => $this->Order->getCode(),
        ));
        return $r;
    }
}
