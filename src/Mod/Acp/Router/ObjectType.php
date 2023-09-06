<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 28.08.19
 * Time: 17:29
 */

namespace Verba\Mod\Acp\Router;


use Verba\Exception\Routing;
use Verba\Mod\Routine\Block\CUNow;
use Verba\Mod\Routine\Block\Delete;
use Verba\Mod\Routine\Block\Delete\Json;
use Verba\Mod\Routine\Block\Form;
use Verba\Request\Http\Router;

class ObjectType extends Router
{

    function route()
    {
        if(!$this->request->ot_code){
            throw new Routing('Unknown otype');
        }

        if (!isset($this->request->action) && count($this->request->uf))
        {
            $this->request->action = $this->request->uf[count($this->request->uf) - 1];
        }

        $rq = $this->rq->shift();

        switch ($this->request->action) {
            case 'list':
                $acp_cfgs = 'acp/list acp-' . $rq->ot_code . ' acp/ots/' . $rq->ot_code;
                $cfg = $rq->getParam('cfg');
                $rq->addParam(array('cfg' => empty($cfg) ? $acp_cfgs : $acp_cfgs.' ' . $cfg));
                $h = new \Verba\Mod\Routine\Block\MakeList($rq);
                $h->contentType = 'json';
                break;

            case 'cuform' :
            case 'createform':
            case 'updateform':
                $cfg = $rq->getParam('cfg');
                if (!isset($cfg) || empty($cfg)) {
                    $rq->addParam(array('cfg' => 'acp acp-' . $rq->ot_code . ' acp/ots/' . $rq->ot_code));
                }
                $h = new Form($rq);
                $h->contentType = 'json';
                break;

            case 'create' :
            case 'update' :
            case 'editnow':
            case 'newnow' :
                $h = new CUNow($rq);
                break;

            case 'remove' :
                $h = new Json($rq);
                break;
            case 'delete' :
                $h = new Delete($rq);
                break;
        }

        if(!isset($h)){
            throw new Routing();
        }

        return $h->route();
    }
}
