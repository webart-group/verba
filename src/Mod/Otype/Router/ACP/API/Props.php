<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.12.2019
 * Time: 3:06
 */

namespace Verba\Mod\Otype\Router\ACP\API;

use Verba\Mod\Acp\Router\ObjectType;

class Props extends \Verba\Request\Http\Router
{
    function route()
    {

        $_otype = \Verba\_oh('otype');
        $_otype_prop = \Verba\_oh('otype_prop');
        $rq = array(
            'ot_id' => $_otype_prop->getID()
        );

        switch($this->request->node){
            case 'load':
                $action = 'list';
                $ot_iid = $this->rq->iid;
                if(!$ot_iid){
                    throw new \Verba\Exception\Routing('Ot iid required');
                }
                $rq['pot'] = \Verba\potToArray($_otype->getID(), $ot_iid);
                break;
            default:
                $action = $this->request->action;
                break;
        }
        $rq['action'] = $action;

        $router = new ObjectType($rq);

        $h = $router->route();

        if(!isset($h)){
            throw new \Verba\Exception\Routing();
        }

        return $h;
    }
}
