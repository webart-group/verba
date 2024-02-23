<?php

namespace Verba\Mod\Review\Act\Delete\Handler;

use Verba\Act\Delete\Handler;

class StoreRatingDown extends Handler
{
    function run()
    {
        if(!$this->row['storeId']){
            return null;
        }

        $_store = \Verba\_oh('store');

        $nominal = (int)\Verba\_mod('review')->getNominalFromRatingId($this->row['rating']);

        if(!$nominal){
            return null;
        }

        $Store = $_store->initItem($this->row['storeId']);

        if(!$Store || !$Store->getId()){
            return null;
        }

        $q = "UPDATE ".$_store->vltURI()." "
            . "SET reviews_count=reviews_count-1, reviews_stars=reviews_stars-".$nominal." "
            . "WHERE id='".$Store->getId()."' LIMIT 1";

        $this->DB()->query($q);

        $this->log()->event('Review removed, store rating changed');

        return true;
    }
}
