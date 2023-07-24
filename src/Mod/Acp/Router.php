<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.09.19
 * Time: 14:04
 */

namespace Verba\Mod\Acp;

use App\Layout\Acp as AcpLayout;
use App\Layout\Core;
use Verba\Block;
use Verba\Block\Html;
use Verba\Exception\Routing;
use Verba\Hive;
use Verba\Mod\ACP;
use Verba\Mod\Routine\Block\CUNow;
use Verba\Mod\User\Block\Login;
use Verba\Response;
use Verba\Response\Json;
use function Verba\_mod;
use function Verba\isOt;

class Router extends \Verba\Request\Http\Router
{
    function route()
    {
        /**
         * @var $mAcp ACP
         */
        $mAcp = _mod('acp');
        if (!$mAcp->checkAccess())
        {
            $layout = new Core();
            $layout->addItems(['CONTENT' => new Login($this)]);

            return $layout->route();
        }

        // изменение ввода
        if($this->rq->node == 'h'){
            $rq = $this->rq->shift();
        }else{
            $rq = $this->rq;
        }

        if($rq->node == '') {

            $h = new AcpLayout($rq);

        // если есть роутер в acp рутинге
        } elseif(($autoclass = '\\'.__NAMESPACE__.'\\Router\\'.ucfirst($rq->node)) && class_exists($autoclass)) {

            $h = new $autoclass($rq->shift());

        // если есть ACP-роутер вида <Node>AcpRouter в модуле
        } elseif(($autoclass = '\\'.__NAMESPACE__.'\\Router\\'.ucfirst($rq->node).'AcpRouter') && class_exists($autoclass)) {

            $h = new $autoclass($rq->shift());

            // если есть ACP-роутер в модуле (и модуль есть)
        } elseif(Hive::isModExists($rq->node) && ($Mod = _mod($rq->node)) && ($modRouter = '\\Verba\\Mod\\'.$Mod->getName().'\\Router\\ACP') && class_exists($modRouter)) {

            $h = new $modRouter($rq->shift());

        } elseif(($autoclass = '\\Verba\\App\\'.ucfirst($rq->node).'\\Router') && class_exists($autoclass)) {
            $h = new $autoclass($rq->shift());
        } elseif(isOt($rq->node)) {

            $rq->setOt($rq->node);
            $h = new Router\ObjectType($rq);

        }

        /*
        if(!isset($h)) {
            switch ($this->rq->node) {
                case 'tools':
                    $h = new Router\Tool($this->rq->shift());
                    break;

                case 'system':
                    $h = new Router\System($this->rq->shift());
                    break;
                case '':
                    $h = new Block\Page($this->rq);
                    break;
            }
        }
        */

        if(!isset($h) || !$h instanceof Block){
            throw new Routing();
        }

        $response = $h->route();

        // Обертки на вывод

        // Если возвращен не фактический объект Ответа, оборачиваем по возможности
        if(!$response instanceof Response && $response instanceof Block)
        {
            //если ae-процесс и не указан режим возврата, по умолчания для acp формат json-item-updated
            if($response instanceof CUNow && $response->getResponseAs() === false){
                $response->setResponseAs('json-item-updated');
            }
            // для всех html-блоков принудительное изменение типа на json
            if($response instanceof Html) {
                $response->contentType = 'json';
            }

            // для json-блока обертка в объект json-ответа
            if($response->contentType == 'json'){
                $newResponse = new Json($this);
                $newResponse->addItems($response);
                $response = $newResponse->route();
            }
        }
/*
            if($response instanceof \Verba\Block){
                if($this->rq->node === 'aenow'){

                    if(!(isset($_REQUEST['_norelocate']) && $_REQUEST['_norelocate'] == 1)){
                        $newResponse = new \Verba\Response\Raw();
                        $newResponse->addHeader('Location', '/acp');
                    }else{
                        $newResponse = new \Verba\Response\Json();
                    }

                    $newResponse->addItems($response);

                    $response = $newResponse->route();

                    // если путь совпадает или тип содержимого json
                }elseif( in_array($this->rq->node, ['h','rq']) || $response->contentType == 'json'){
                    $newResponse = new \Verba\Response\Json($this);
                    $newResponse->addItems($response);
                    $response = $newResponse->route();
                }
            }
        }
*/



        return $response;
    }
}
