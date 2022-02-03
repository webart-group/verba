<?php
namespace Mod\Balop;

class Cause extends \Verba\Configurable
{
    protected $otype;
    protected $_i_class = '\Model\Item';
    protected $_check_i_active = true;
    public $iid;
    protected $currencyId;
    protected $sum;
    protected $holdOffset;
    protected $description;
    protected $holdTill = '~';
    protected $block = 1;
    protected $_valid;

    protected $primitiveOt;
    protected $primitiveId;

    protected $causeOt;
    protected $causeId;

    /**
     *
     * Равно true если эта операция проходит в рамках одного аккаунта (перемещение сумм  balance <=> hbalance)
     * @var bool
     */
    protected $internal = false;
    /**
     * @var \Model\Item
     */
    protected $_i;
    protected $oh;
    /**
     * @var \Mod\Account\Model\Account
     */
    protected $Acc;
    protected $accId;

    protected $_itemClassSuffixRequired;

    function __construct($data)
    {

        if (is_object($data)) {
            $this->set_i($data);
        } elseif (is_numeric($data)) {

            $this->iid = $data;

        } elseif (is_array($data)) {

            $this->applyConfigDirect($data);

        }

        if (\Verba\isOt($this->otype)) {

            $this->oh = \Verba\_oh($this->otype);

            if (!is_object($this->_i)) {
                if (!is_object($this->oh)) {
                    throw new \Exception('AccBalCause bad params');
                }
                $this->set_i($this->loadItem());
            }

            if(!is_object($this->oh)){
                if(is_object($this->_i) && $this->_i->getOh()){
                    $this->oh = $this->_i->getOh();
                }
            }
        }

        if(is_object($this->oh)) {
            $this->otype = $this->oh->getCode();
        }

        $this->init();
    }

    function init(){

    }

    function setAcc($acc)
    {
        if (!$acc instanceof \Mod\Account\Model\Account) {
            return false;
        }
        $this->Acc = $acc;
        $this->accId = $this->Acc->getId();

        return $this->Acc;
    }

    function setAccId($val)
    {

        $this->Acc = new \Mod\Account\Model\Account($val);

        if (!$this->Acc instanceof \Mod\Account\Model\Account) {
            $this->Acc = false;
            $this->accId = false;
            return false;
        }

        $this->accId = $this->Acc->getId();

        return $this->accId;
    }

    function i()
    {
        return $this->_i;
    }

    function getOType()
    {
        return $this->otype;
    }

    function setOtype($otype){
        $this->otype = $otype;
    }

    protected function loadItem()
    {
        return $this->oh->initItem($this->iid);
    }

    function set_i($val)
    {
        if (!$val instanceof $this->_i_class) {
            return;
        }
        $this->_i = $val;
        $this->iid = $this->_i->getId();

        $this->causeOt = $this->_i->getOtId();
        $this->causeId = $this->_i->getId();

        $_balop = \Verba\_oh('balop');
        if ($this->causeOt == $_balop->getID()) {
            $this->primitiveOt = $this->_i->primitiveOt;
            $this->primitiveId = $this->_i->primitiveId;
        } else {
            $this->primitiveOt = $this->causeOt;
            $this->primitiveId = $this->causeId;
        }
    }

    function getSum()
    {
        if($this->sum === null){
            $this->sum = $this->calcSum();
        }

        return $this->sum;
    }
    function setSum($val)
    {
        $this->sum = 0;
        return $this->sum;
    }

    function calcSum()
    {
        return 0;
    }

    function getCurrencyId()
    {

        if (is_object($this->_i)) {
            if (method_exists($this->_i, 'getCurrencyId')) {
                return $this->_i->getCurrencyId();
            } elseif (method_exists($this->_i, 'getRawValue')) {
                return $this->_i->getRawValue('currencyId');
            }
        }

        if($this->currencyId === null){
            if(is_object($this->Acc)){
                return $this->Acc->currencyId;
            }
        }

        return $this->currencyId;
    }

    function getCauseOt()
    {
        return $this->causeOt;
    }

    function getCauseId()
    {
        return $this->causeId;
    }

    function getPrimitiveOt()
    {
        return $this->primitiveOt;
    }

    function setPrimitiveOt($val){
        $oh = \Verba\_oh($val);
        if(!$oh){
            return;
        }
        $this->primitiveOt = $oh->getID();
    }

    function getPrimitiveId()
    {
        return $this->primitiveId;
    }

    function setPrimitiveId($val){
        if(!is_numeric($val)){
            return;
        }
        $this->primitiveId = (int)$val;
    }

    function validate()
    {

        $this->_valid = false;

        try {

            if (!is_object($this->Acc) || !$this->Acc instanceof \Mod\Account\Model\Account) {
                throw new \Exception('Invalid Acc instance');
            }

            if($this->otype){
                $_oh = \Verba\_oh($this->otype);
                if (!is_object($this->oh) || $this->oh->getCode() != $_oh->getCode()) {
                    throw new \Exception('Oh bad or mismatch');
                }
            }

            if (!$this->_i || !$this->_i instanceof $this->_i_class) {
                throw new \Exception('Bad _i');
            }

            // не убирать

            if(false !== $this->_check_i_active
                && (!$this->_i->isProp('active') || $this->_i->active != 1))
            {
                throw new \Exception('_i is inactive');
            }


            if (is_object($this->oh) && $this->oh->getCode() == 'balop') {
                if (!$this->_i) {
                    throw new \Exception('Base Balop invalid.');
                }
                if (!empty($this->_itemClassSuffixRequired)
                    && $this->_i->cause != $this->_itemClassSuffixRequired
                ) {
                    throw new \Exception('Base Balop class mismatch. Base Balop id:' . $this->_i->getId());
                }
                // Проверяем что причина все еще валидна
                if (!$this->_i->active) {
                    throw new \Exception('Base Balop inactive. Base Balop id:' . $this->_i->getId());
                }
            }
        } catch (\Exception $e) {

            $this->log()->error($e->getMessage());

            $this->_valid = false;

            return $this->_valid;
        }

        $this->_valid = true;
        return $this->_valid;
    }

    function isValid()
    {
        if ($this->_valid === null) {
            $this->validate();
        }
        return $this->_valid;
    }

    protected function getFieldsToSignature()
    {
        return array(
            // 'iid' => $this->iid,
            // 'accId' => $this->Acc->getId(),
        );
    }

    function toSignature()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    function calcHoldOffset()
    {
        return 0;
    }

    function getHoldOffset()
    {
        if ($this->holdOffset === null) {
            $this->holdOffset = $this->calcHoldOffset();
            if ($this->holdOffset === null) {
                $this->holdOffset = 0;
            }
        }
        return (int)$this->holdOffset;
    }

    function getHoldTill()
    {

        if ($this->holdTill === '~') {
            $this->holdTill = null;
            $offset = $this->getHoldOffset();
            $ts = $offset ? time() + $offset : false;
        }

        if (isset($ts) && $ts !== false) {
            $this->holdTill = date('Y-m-d H:i:s', $ts);
        }

        return $this->holdTill;
    }

    function getDescription(){
        return null;
    }

    /**
     * @param $cause_str
     * @return Cause|bool
     */
    static function recreateCause($cause_str, $causeOt, $causeId)
    {
        if (!is_string($cause_str) || empty($cause_str)) {
            return false;
        }

        $causeClassName = '\Mod\Balop\Cause\\' . $cause_str;
        if (!class_exists($causeClassName)) {
            return false;
        }
        $data = array(
            'otype' => $causeOt,
            'iid' => $causeId,
        );
        $recreatedCause = new $causeClassName($data);
        if (!$recreatedCause instanceof Cause) {
            return false;
        }
        return $recreatedCause;
    }

    function getBalopVisibility()
    {
        return 1;
    }

    function getBlock()
    {
        return $this->block;
    }

    function isBlockedBalance()
    {
        return (bool)$this->block;
    }

    function setBlock($val)
    {
        return;
    }

    function getBlockParams()
    {
        $block = $this->getBlock();
        $holdOffset = $this->getHoldOffset();
        $holdTill = $this->getHoldTill();

        return array($block, $holdTill, $holdOffset);
    }

    function getInternal()
    {
        return $this->internal;
    }
}