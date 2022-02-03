<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 12:30
 */

namespace Mod\Otype\Router\ACP\API\Attribute;

use Mod\Otype\Block\ACP\API\Attribute\Handler\GetAvaibleByType;
use Mod\Otype\Block\ACP\API\Attribute\Handler\Assign;
use Mod\Otype\Block\ACP\API\Attribute\Handler\Unassign;

class Handler extends \Verba\Request\Http\Router {

    function route(){


        switch($this->request->node){
            case 'avaible_by_type':
                $router = new GetAvaibleByType($this);
                break;
            case 'assign':
                $router = new Assign($this);
                break;
            case 'unassign':
                $router = new Unassign($this);
                break;
        }


        if(!isset($router)){
            throw new \Exception\Routing();
        }

        $h = $router->route();

        return $h;
    }
}