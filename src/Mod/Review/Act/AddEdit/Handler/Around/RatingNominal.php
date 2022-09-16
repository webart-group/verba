<?php

namespace Verba\Mod\Review\Act\AddEdit\Handler\Around;

use Verba\Act\AddEdit\Handler\Around;

class RatingNominal extends Around
{
    function run()
    {
        $ratingVal = $this->ah->getTempValue('rating');
        if($ratingVal === null){
            return null;
        }

        $this->value = \Verba\_mod('review')->getNominalFromRatingId($ratingVal);

        if(!isset($this->value)){
            return false;
        }

        return $this->value;
    }
}