<?php

namespace Verba\Mod\Acp\Router;

use Verba\Request\Http\Router;


class Banner extends ObjectType
{

    function route(){

        switch($this->request->action){
            case 'cuform':
            case 'createform':
            case 'updateform':
                if(isset($this->request->pot) && is_array($this->request->pot)){
                    reset($this->request->pot);
                    $pot = key($this->request->pot);
                    if(is_array($this->request->pot[$pot])){
                        reset($this->request->pot[$pot]);
                        $piid = current($this->request->pot[$pot]);

                        $parentItem = \Verba\_oh($pot)->getData($piid, 1);
                        if(is_array($parentItem)
                            && !empty($parentItem)){
                            $this->request->addParam(array('cfg' => 'acp-banner acp-'.$parentItem['code']));
                        }
                    }
                }
                $h = new \Verba\Mod\Routine\Block\Form($this->rq);
                $h->contentType = 'json';
                break;
        }

        if(!isset($h)){
            $h = parent::route();
        }

        return $h;
    }
}
