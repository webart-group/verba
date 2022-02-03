<?php

class feedback_addRequest extends \Verba\Mod\Routine\Block\CUNow
{

    public $valid_otype = 'feedback';
    public $responseAs = 'json-item-keys';
    public $responseAsKeys = array(
        'id'
    );

    function init()
    {
        $this->rq->setOt('feedback');
    }

    function routedActions(){
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

        $this->content = \Verba\Lang::get('feedback add success message');
        return $this->content;
    }
}
