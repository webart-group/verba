<?php

class profile_offersUpdate extends \Verba\Mod\Routine\Block\CUNow
{

    use profile_offersActionRoutine;
    use game_offers;

    public $responseAs = 'json-item-updated';

    function routedActions()
    {
        return [
            'update' => true,
        ];
    }

    function build()
    {
        parent::build();
    }
}

