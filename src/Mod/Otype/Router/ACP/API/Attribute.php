<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 15.09.19
 * Time: 20:14
 */

namespace Mod\Otype\Router\ACP\API;

use Mod\ACP\Router\ObjectType;
use Mod\Otype\Block\ACP\API\Attribute\Form\Inside;
use Mod\Otype\Block\ACP\API\Attribute\GetAttrs;
use Mod\Otype\Block\ACP\API\Attribute\Load;

class Attribute extends \Verba\Request\Http\Router
{

    function route()
    {

        switch ($this->rq->node){
            case 'get':
                $h = new GetAttrs($this->rq->shift());
                break;
            case 'load':
                $h = new Load($this);
                break;

            case 'cuform':
                $rq = clone $this->request;
                $rq->addParam(array(
                    'cfg' => 'acp acp-ot_attribute acp-ot_attribute-otwidget'
                ));
                $rq->setOt('ot_attribute');
                $h = (new ObjectType($rq))->route();
                break;

            case 'inside':
                $h = (new Inside($this->rq->shift()))->route();
                break;

            case 'ah':
                $h = new Attribute\Handler($this->request->shift());
                break;
        }

        if(!isset($h)){
            throw new \Exception\Routing();
        }

        return $h->route();
    }
}
