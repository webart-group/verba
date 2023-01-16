<?php

namespace Verba\Act\AddEdit\Handler\Before;

use \Verba\Act\AddEdit\Handler\Before;
use Verba\Model\Store;
use function Verba\_oh;

class StoreIdDetector extends Before
{

    function run()
    {
        $extendedStore = $this->ah->getExtendedData('store') instanceof Store
            ? $this->ah->getExtendedData('store')
            : null;

        $_store = _oh('store');

        $storeId = $this->ah->getGettedValue('storeId');
        if($storeId) {
            goto STORE_ID_KNOWN;
        }

        if($extendedStore){
            $storeId = $extendedStore->getId();
            goto STORE_ID_KNOWN;
        }

        $storeId = $this->ah->getActualValue('storeId');
        if($storeId) {
            goto STORE_ID_KNOWN;
        }

        throw new \Exception('Unknown store id for product');

        STORE_ID_KNOWN:

        if(!$extendedStore || $storeId != $extendedStore->id){
            $extendedStore = $_store->initItem($storeId);
            $this->ah->addExtendedData([
                'store' => $extendedStore
            ]);
        }

        return true;
    }
}
