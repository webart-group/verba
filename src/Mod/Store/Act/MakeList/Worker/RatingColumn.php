<?php
namespace Verba\Mod\Store\Act\MakeList\Worker;

use Verba\Act\MakeList\Worker;

class RatingColumn extends Worker {

    public $priority = 0;
    public $code;

    function init(){
        $this->parent->listen('beforeStart', 'addFieldToListCfg', $this);
    }

    function addFieldToListCfg(){
        $this->ah->applyConfigDirect(
            [
                'fields' => [
                    $this->code => [
                        'priority' => $this->priority,
                        'type' => 'virtual',
                        'handler' => '\Mod\Store\Act\MakeList\Handler\Field\OffersRating'
                    ]
                ],
                'headers' => [
                    'fields' => [
                        $this->code => ['title' => \Verba\Lang::get('review common title')]
                    ]
                ],
                'order' => [
                    'subst' => [
                        'store_rating' => ['rating', 'stores']
                    ]
                ]
            ]
        );
        return true;
    }
}
