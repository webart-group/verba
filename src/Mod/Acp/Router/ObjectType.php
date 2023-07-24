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

class ObjectType extends \Verba\Request\Http\Router
{

    function route()
    {
        if(!$this->request->ot_code){
            throw new \Verba\Exception\Routing('Unknown otype');
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
                $h = new \Verba\Mod\Routine\Block\Form($rq);
                $h->contentType = 'json';
                break;

            case 'create' :
            case 'update' :
            case 'editnow':
            case 'newnow' :
                $h = new \Verba\Mod\Routine\Block\CUNow($rq);
                if (!$h->responseAs) {
                    $h->responseAs = 'data';
                }
                break;

            case 'remove' :
                $h = new \Verba\Mod\Routine\Block\Delete\Json($rq);
                break;
            case 'delete' :
                $h = new \Verba\Mod\Routine\Block\Delete($rq);
                break;
        }

        if(!isset($h)){
            throw new \Verba\Exception\Routing();
        }

        return $h->route();
    }
}
