<?php

class account_acpToolChangeBalance extends \Verba\Block\Json
{

    protected $accId = false;
    protected $block = false;
    protected $sum = false;
    protected $op = false;

    function build()
    {
        $targetAcc = new \Verba\Mod\Account\Model\Account($this->accId);

        if (!$targetAcc || !$targetAcc->getId()) {
            throw  new \Verba\Exception\Building('Bad Item');
        }

        $this->sum = \Verba\reductionToCurrency($this->sum);

        if (!$this->sum || !$this->op) {
            throw  new \Verba\Exception\Building('Bad input');
        }
        $sum = $this->op == 'plus' ? $this->sum : '-' . $this->sum;
        $balopCause = new \Verba\Mod\Balop\Cause\AdminBalanceChange(array(
            'sum' => $sum,
            'block' => (int)$this->block,
        ));
        $balopItem = $targetAcc->balanceUpdate($balopCause);

        if (!$balopItem && !$balopItem instanceof \Model\Item
            || !$balopItem->active) {
            throw  new \Verba\Exception\Building('Balop creation error or inactive');
        }

        $this->content = $this->sum > 0 ? '+' . $this->sum : $this->sum;
        return $this->content;
    }

    function setAccId($var)
    {
        $this->accId = intval($var);
        return $this->accId;
    }

    function setOp($var)
    {
        $var = strtolower($var);
        if ($var != 'minus' && $var != 'plus') {
            return;
        }
        $this->op = $var;
    }

    function setSum($sum)
    {
        $this->sum = $sum;
    }

    function setBlock($var)
    {
        $this->block = intval((bool)$var);
    }
}
