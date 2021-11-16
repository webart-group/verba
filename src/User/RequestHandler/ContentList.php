<?php

namespace Verba\User\RequestHandler;

class ContentList extends \Verba\Mod\Routine\Block\MakeList
{

    public $userId;
    protected $ownerField;

    function init()
    {
        parent::init();
        if (!$this->userId || !is_numeric($this->userId) || $this->userId < 1) {
            throw new \Exception\Routing('Bad input params');
        }
    }

    function prepare()
    {
        parent::prepare();
        $this->ownerField = $this->oh->getOwnerAttributeCode();

        if (!$this->list) {
            throw new \Exception\Routing('Bad input params');
        }

        $walias = 'user_content_list';
        $qm = $this->list->QM();
        $where = $qm->getWhere($walias);
        if (!$where) {
            $qm->addWhere($this->userId, $walias, $this->ownerField);
        }
    }
}
