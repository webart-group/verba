<?php

namespace Verba\Mod\Review\Act\AddEdit\Handler\After;

use Verba\Act\AddEdit\Handler\After;

class StoreRatingUp extends After
{
    protected $_allowedEdit = false;

    function run()
    {
        if($this->ah->getAction() != 'new'){
            return null;
        }

        $_store = \Verba\_oh('store');
        $parents = $this->ah->getParents();
        if(!isset($parents[$_store->getID()])
            || !is_array($parents[$_store->getID()])
            || count($parents[$_store->getID()]) != 1){

            return null;
        }

        $ratingValue = $this->ah->getActualValue('rating');

        $nominal = (int)\Verba\_mod('review')->getNominalFromRatingId($ratingValue);

        if(!$nominal){
            return false;
        }
        try {
            $storeId = (int)current($parents[$_store->getID()]);
            if(!$storeId){
                throw  new \Verba\Exception\Building('Bad store id parents: ['.var_export($parents, true));
            }

            $Store = $_store->initItem($storeId);
            if(!$Store || !$Store->active){
                throw  new \Verba\Exception\Building('Store not exists or inactive: ['.var_export($parents, true));
            }

            $q = "UPDATE ".$_store->vltURI()." "
                . "SET reviews_count=reviews_count+1, reviews_stars=reviews_stars+".$nominal." "
                . "WHERE id='".$Store->getId()."' LIMIT 1";

            $this->DB()->query($q);

            $this->log()->event('Review added, store rating changed');

        }catch ( \Verba\Exception\Building $e){
            $this->log()->error($e->getMessage());
            return false;
        }

        return true;
    }
}
