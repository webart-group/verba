<?php

namespace Verba\Act\AddEdit\Handler\After;

use Act\AddEdit\Handler\After;

class StoreCurrencyChange extends After
{

    function run()
    {

        if ($this->ah->getAction() != 'edit') {
            return true;
        }

        $r = $this->update();

        return $r;
    }

    function update()
    {
        $exists_value = $this->ah->getExistsValue('currencyId');
        $new_value = $this->ah->getTempValue('currencyId');
        if (!$new_value || $exists_value == $new_value) {
            return true;
        }

        $storeId = $this->ah->getIID();
        $store = $this->ah->getActualItem();
        $Currency = \Mod\Currency::getInstance();
        $newCur = $Currency->getCurrency($new_value);
        $newCurId = $newCur->getId();
        $_bid = \Verba\_oh('bid');

        //$_bid->getData;

        $q = "SELECT `" . $_bid->getPAC() . "`, `prodOtId`, `prodId` FROM " . $_bid->vltURI() . " 
    WHERE `storeId` = '" . $storeId . "' 
    ORDER BY `" . $_bid->getPAC() . "` DESC
    LIMIT";

        $nr = $step = 100;

        for ($i = 0; $nr == $step; $i = $i + $step) {
            $qc = $q . ' ' . $i . ',' . $step;
            $sqlr = $this->DB()->query($qc);
            $nr = $sqlr->getNumRows();
            if (!$nr) {
                break;
            }
            while ($row = $sqlr->fetchRow()) {
                if (!$row['prodOtId'] || !\Verba\isOt($row['prodOtId'])
                    || !$row['prodId']) {
                    $this->log()->error('Losted BID id: ' . $row['id'] . ', ProdOtId: ' . var_export($row['prodOtId'], true) . ', ProdId: ' . var_export($row['prodId'], true));
                    continue;
                }
                $_prod = \Verba\_oh($row['prodOtId']);

                //достаем данные по продукту
                $ae = $_prod->initAddEdit('edit');
                $ae->setIID($row['prodId']);
                if (!$ae->loadExistsValues()) {
                    $this->log()->error('Broken BID Product entry. Bid id: ' . $row['id'] . ', ProdOtId: ' . var_export($row['prodOtId'], true) . ', ProdId: ' . var_export($row['prodId'], true));
                    continue;
                }

                $newPrice = $Currency->crossConvert($ae->getExistsValue('price'), $ae->getExistsValue('currencyId'), $newCurId);

                $ae->setGettedData(array(
                    'currencyId' => $newCur->getId(),
                ));

                $ae->setExtendedData([
                    'store' => $store,
                ]);

                $ae->addedit_object();
            }
        }

        $mStore = \Mod\Store::getInstance();
        $mStore->refreshStoreCPK($this->ah->getActualData());

        return true;
    }
}
