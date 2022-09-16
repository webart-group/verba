<?php
namespace Verba\Mod\Currency\Block\Base;

class Save extends \Verba\Block\Html
{

    function build()
    {
        $Cur = \Verba\Mod\Currency::getInstance();
        $curs = $Cur->getCurrency();
        $cid = $_REQUEST['basecurrencyid'];

        if (!$cid || !array_key_exists($cid, $curs)) {
            throw new \Exception('Bad currency ID');
        }
        $this->log();
        $_cur = \Verba\_oh('currency');
        $currentBaseCurrencyId = $Cur->getBaseCurrency($_cur->getPAC());
        $currentBaseCurrencyCode = $Cur->getBaseCurrency('code');
        $this->DB()->query("UPDATE " . $_cur->vltURI() . " SET isbase=0 WHERE 1");
        $this->log->event('Shop base currency is reseted. Previus [' . var_export($currentBaseCurrencyCode, true) . ']');
        $sqlr = $this->DB()->query("UPDATE " . $_cur->vltURI() . " SET `isbase`=1, `rate`=1 WHERE `" . $_cur->getPAC() . "` = '" . ($cid) . "'");
        if (!$sqlr->getAffectedRows()) {
            $this->log->error('Seting new base currency [' . $cid . '] failed. Item not found into DB');
            if ($currentBaseCurrencyId) {
                $sqlr = $this->DB()->query("UPDATE " . $_cur->vltURI() . " SET `isbase`=1, `rate`=1 WHERE `" . $_cur->getPAC() . "` = '" . $currentBaseCurrencyId . "'");
                if ($sqlr->getAffectedRows()) {
                    $this->log->warning('Previous base currency [' . $currentBaseCurrencyId . '] is restored.');
                } else {
                    $this->log->event('Previous base currency [' . $currentBaseCurrencyId . '] is NOT restored.');
                }
            } else {
                $this->log->event('Previous base currency is NOT FOUND. Restoration is canceled.');
            }

            throw new \Exception($this->log->getMessagesAsStr());
        }
        \Verba\_mod('payment')->resetCache();
        \Verba\_mod('currency')->resetCache();
        $this->log->event('Shop base currency is [' . $cid . '] now');

        $this->content = $this->log->getMessagesAsStr('event');
        return $this->content;
    }
}
