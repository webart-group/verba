<?php
namespace Verba\Mod\Balop\Cause;

use Verba\Mod\Balop\Cause;


class PaymentSuccessFromBalance extends Cause{

    protected $block = 0;
    protected $_check_i_active = false;

    /**
     * @var \Verba\Mod\Order\Model\Order
     */
    protected $Order;

    function init()
    {
        if(is_object($this->_i) && !empty($this->_i->orderId)){
            $this->Order = \Verba\Mod\Order::i()->getOrder($this->_i->orderId);
        }
    }

    function calcSum()
    {
        // сумма, зачисляемая на счет в результате пополнения
        return  $this->Order->getByuerSum();
    }

    function getDescription()
    {
        $ps_tax = is_array($this->Order->price_map) && array_key_exists('ps_tax', $this->Order->price_map)
            ? $this->Order->price_map['ps_tax']
            : '??';

        $r = \Verba\Lang::get('balop descriptions paymentSuccess', array(
            'ps_tax' => \Verba\reductionToCurrency($ps_tax),
            'cur_symbol' => $this->Order->getCurrency()->symbol,
        ));

        return $r;
    }

    function validate(){

        if(!parent::validate()){
            return $this->_valid;
        }

        $this->_valid = false;

        if(!is_object($this->Order) || !$this->Order instanceof \Verba\Mod\Order\Model\Order
            || !$this->Order->active){
            throw new \Exception('Incorrect Order');
        }

        if($this->Order->payed){
            throw new \Exception('Order payment handling error');
        }

        if(\Verba\reductionToCurrency($this->Order->getTopay()) != $this->_i->sum){
            throw new \Exception('Order payment handling error');
        }

        $this->_valid = true;
        return $this->_valid;
    }
}