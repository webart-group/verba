<?php

class profile_ordersList extends \Verba\Mod\Routine\Block\MakeList
{

    public $scripts = array(
        array('profile_order_buttons', 'profile/tools'),
    );

    public $otype = 'order';
    protected $_orderSide = false;
    /**
     * @var \Verba\Mod\User\Model\User
     */
    public $U;
    protected $userId;

    function init()
    {
        parent::init();

        if ($this->U && $this->U instanceof \Verba\Mod\User\Model\User) {
            $this->userId = $this->U->getId();
        }
    }

    function route()
    {

        if (!$this->U || !$this->U instanceof \Verba\Mod\User\Model\User
            || !$this->userId) {
            throw new \Verba\Exception\Routing('Bad user');
        }

        return $this;
    }

    function prepare()
    {
        parent::prepare();

        $_order = \Verba\_oh('order');

        $qm = $this->list->QM();

        list($ta) = $qm->createAlias();
        list($oi) = $qm->createAlias('orders_links', false, 'oi');

        $qm->addCJoin(array(array('a' => $oi)),
            array(
                array(
                    'p' => array('a' => $ta, 'f' => 'id'),
                    's' => array('a' => $oi, 'f' => 'p_iid'),
                ),
                array(
                    'p' => array('a' => $oi, 'f' => 'p_ot_id'),
                    's' => $_order->getID(),
                ),
            )
            , true);

        $qm->addSelectPastFrom('extra', 'oi', 'first_item_extra', null, false);

    }

}
