<?php

class account_withdrawalCreate extends \Verba\Mod\Routine\Block\CUNow
{

    public $responseAs = 'json-item-keys';
    public $responseAsKeys = array(
        'accountId', 'active', 'sum', 'status', 'id'
    );

    function routedActions()
    {
        return [
            'create' => true,
        ];
    }

    function build()
    {

        parent::build();

        if (!$this->ae->getIID() || $this->ae->haveErrors()) {
            throw  new \Verba\Exception\Building($this->ae->log()->getMessagesAsStr('error'));
        }
    }

}

?>
