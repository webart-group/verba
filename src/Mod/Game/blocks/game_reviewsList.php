<?php

class game_reviewsList extends \Verba\Mod\Routine\Block\MakeList
{

    public $css = array('reviews');

    public $otype = 'review';

    public $cfg = 'public public/reviews/store';

    /**
     * @var \Model\Store
     */
    public $Store;

    function prepare()
    {

        parent::prepare();

        $qm = $this->list->QM();

        $qm->addWhere(1, 'active');

        if ($this->Store) {
            $_store = \Verba\_oh('store');
            $this->list->addMultipleParents(array(
                $_store->getID() => array($this->Store->getId()),
            ));
        }

        $_order = \Verba\_oh('order');

        list($ta) = $qm->createAlias();
        list($oi) = $qm->createAlias('orders_links', false, 'oi');
        $qm->addCJoin(array(array('a' => $oi)),
            array(
                array(
                    'p' => array('a' => $ta, 'f' => 'orderId'),
                    's' => array('a' => $oi, 'f' => 'p_iid'),
                ),
                array(
                    'p' => array('a' => $oi, 'f' => 'p_ot_id'),
                    's' => $_order->getID(),
                ),
                array(
                    'p' => array('a' => $oi, 'f' => 'ch_ot_id'),
                    's' => array('a' => $ta, 'f' => 'prodOt'),
                ),
                array(
                    'p' => array('a' => $oi, 'f' => 'ch_iid'),
                    's' => array('a' => $ta, 'f' => 'prodId'),
                ),
            )
            , true);

        $qm->addSelectPastFrom('title', 'oi', 'prodTitle', null, false);
        $qm->addSelectPastFrom('price_final', 'oi', 'prodPrice');
        $qm->addSelectPastFrom('rate', 'oi', 'prodCurrencyRate');
        $qm->addSelectPastFrom('currencyId', 'oi', 'prodCurrencyId');


    }
}