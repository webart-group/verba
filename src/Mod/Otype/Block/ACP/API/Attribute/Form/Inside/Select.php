<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:11
 */

namespace Mod\Otype\Block\ACP\API\Attribute\Form\Inside;


class Select extends Base {

    function route(){

        switch ($this->rq->node){
            case 'avaible-pdsets':
                $h = new Select\AvaiblePdsets($this->rq->shift());
                break;
            case 'assign':
                $h = new Select\Assign($this->rq->shift());
                break;
            case 'unassign':
                $h = new Select\Unassign($this->rq->shift());
                break;
            case 'assign-update':
                $h = new Select\AssignUpdate($this->rq->shift());
                break;
            case 'pdset-update':
                $h = new Select\PdsetUpdate($this->rq->shift());
                break;

            case 'init':
                $h = new Select\Init($this->rq->shift());
                break;

        }

        if(!isset($h)){
            throw new \Exception\Routing('Attr Inside Select unknown action');
        }
        return $h;
    }
}