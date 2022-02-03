<?php

namespace Mod\Otype\Router\ACP;

use Mod\ACP\Router\ObjectType;

class Otype extends \Verba\Request\Http\Router
{

    function route()
    {
        $this->request->setOt('otype');
        $h = (new ObjectType($this->request))->route();

        if($h instanceof \Mod\Routine\Block\Form && $this->request->node == 'cuform-prod'){
            $cfg = $h->request->getParam('cfg');
            if(!$cfg){
                $cfg = 'acp-otype-prod';
            }else{
                $cfg .= ' acp-otype-prod';
            }
            $h->request->addParam(['cfg' => $cfg]);
        }

        if($h instanceof \Mod\Routine\Block\MakeList && $this->request->node == 'list-prod'){
            $h->request->addParam([
                'dcfg' => [
                    'url' => [
                        'new' => '/acp/otype/cuform'
                    ]
                ]
            ]);
            $h->listen(\Block::EV_PREPARE_AFTER, 'modifyList', $this, null, $h);
        }

        return $h;
    }

    function modifyList($makeList) {
        list($a) = $makeList->list->QM()->createAlias();
        $makeList->list->QM()->addWhere('`' . $a . "`.`role` IN('public_product', 'public_product_base')");
    }
}
