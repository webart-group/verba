<?php

namespace Verba\Act\AddEdit\Handler\After;

use Act\AddEdit\Handler\After;

class CurpsActiveUpdated extends After
{

    protected $_allowedNew = false;

    public $entryId;
    public $newValue;

    function validate()
    {

        if (!$this->ah->isUpdated()
            || !array_key_exists('active', $this->ah->getUpdatedData())) {
            return false;
        }

        $this->entryId = $this->ah->getIID();
        $this->newValue = (int)$this->ah->getUpdatedValue('active');

        return true;
    }

    /**
     * Обновление значения active для Реквизитов с этой валютой или платежкой
     *
     */
    function updatePrequisitesActivity()
    {
        $otype = $this->ah->oh()->getCode();
        if ($otype == 'currency') {
            $preqField = 'currencyId';
        } elseif ($otype == 'paysys') {
            $preqField = 'paysysId';
        }

        if (!isset($preqField)) {
            return null;
        }

        $_cur = \Verba\_oh('currency');
        $_ps = \Verba\_oh('paysys');
        $_preq = \Verba\_oh('prequisite');

        $q = "
    UPDATE
  " . $_preq->vltURI() . " `b`
  LEFT JOIN " . $_cur->vltURI() . " cur ON cur.id = b.currencyId
  LEFT JOIN " . $_ps->vltURI() . " ps ON ps.id = b.paysysId 
SET
  b.active = IF(cur.active IS NULL || ps.active IS NULL, 0, CONVERT((cur.active * ps.active), decimal))
WHERE `b`.`" . $preqField . "` = '" . \Verba\esc($this->ah->getIID()) . "'";

        $sqlr = $this->DB()->query($q);
        $this->log()->event(ucfirst($otype) . ' [' . var_export($this->entryId, true) . '] is unactive now. Обновлена активность ревизитов: ' . $sqlr->getAffectedRows());

        return true;
    }
}
