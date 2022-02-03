<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 15:42
 */
namespace Mod\Paysys\Balance\Transaction;

class Send extends \Verba\Mod\Payment\Transaction\Send
{

    protected $_paysysCode = 'persBal';

    /**
     * @var \Mod\Paysys\Balance
     */
    protected $mod;

    function __construct($orderId){

        $this->mod = \Verba\_mod('Paysys_Balance');
        $this->Order = \Mod\Order::i()->getOrder($orderId);
        $this->currency = $this->Order->getCurrency();

        $this->_paysysCode .= $this->currency->code;

        parent::__construct($orderId);

        $this->url = '/pay';

        $this->request =  new \Mod\Paysys\Balance\Request\Send($this, $this->genRequestData());

        $this->validate();
        $this->status = $this->genStatus();

        $this->createTx(array(
            'request' => $this->request->exportAsSerialized(),
            'status' => $this->status,
            'description' => $this->description,
        ));
    }

    function genRequestData(){
        $data = array(
            'orderId' => $this->Order->getCode(),
        );
        return $data;
    }

    function validate(){

        if(!($this->isValid = parent::validate())){
            return $this->isValid;
        }

        $this->isValid = true;

        return $this->isValid;
    }

}
