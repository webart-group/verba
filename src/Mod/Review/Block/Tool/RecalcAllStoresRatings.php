<?php
namespace Verba\Mod\Review\Block\Tool;

class RecalcAllStoresRatings extends \Verba\Block\Html{

    function build(){
        //exit;
        set_time_limit(82000);

        $_store = \Verba\_oh('store');
        $_review = \Verba\_oh('review');

        $q = "SELECT `id` FROM ".$_store->vltURI();
        $count = true;
        for($start = 0, $n = 200; $count; $start += $n){
            $cq = $q." LIMIT ".$start.', '.$n;
            $sqlr = $this->DB()->query($cq);
            $count = $sqlr->getNumRows();
            if(!$sqlr || !$sqlr->getNumRows()){
                break;
            }

            while($row = $sqlr->fetchRow()){
                $storeId = (int)$row['id'];
                if(!$storeId || $storeId < 1){
                    continue;
                }

                $update_q = "UPDATE ".$_store->vltURI()." "
                ."SET "
                ."reviews_count = (SELECT count(*) as tc FROM ".$_review->vltURI()." WHERE ".$_review->vltURI().".storeId = ".$storeId."),"
                ."reviews_stars = (SELECT sum(ratingNom) as rn FROM ".$_review->vltURI()." WHERE ".$_review->vltURI().".storeId = ".$storeId.") "
                ."WHERE id = ".$storeId."";

                $sqlr_u =  $this->DB()->query($update_q);
            }
        }
        return;
    }
}