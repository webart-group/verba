<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 30.08.19
 * Time: 17:04
 */

namespace Verba\Mod\Routine\Block;


class AddEdit extends \Verba\Block\Json
{
    /**
     * @var \Act\AddEdit
     */
    public $ae;

    /**
     * @var bool|string
     */
    public $responseAs = false;

    public $responseAsKeys;

    function build()
    {
        $cfg = $this->rq->asArray();
        $cfg['responseAs'] = $this->responseAs;

        $this->oh = \Verba\_oh($this->rq->ot_id);

        $this->ae = $ae = $this->oh->initAddEdit(['action' => $this->request->action]);
        $ae->setIID($this->request->iid);

        if (!$ae->validateAccess()) {
            throw new \Exception(\Lang::get('access denied'));
        }
        if (isset($cfg['pot'])) {
            $ae->addMultipleParents($cfg['pot']);
        }
        if (isset($bp) && isset($bp['data'])) {
            $ae->setGettedObjectData($bp['data']);
        } else {
            $ae->setGettedObjectData($_REQUEST['NewObject'][$this->oh->getID()]);
        }

        $ae->addedit_object();
        $this->content = '';
        return $this->content;
    }
}
