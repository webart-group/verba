<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 10.11.2019
 * Time: 1:04
 */

namespace Verba\Mod\Acp\Router;

use Verba\Mod\Acp\Router\ObjectType;

class Aenow extends \Verba\Request\Http\Router
{
    function route()
    {
        // ищем роутер для ACP в модуле, если такой есть
        if(\Verba\Hive::isModExists($this->rq->node) && ($Mod = \Verba\_mod($this->rq->node)) && ($modRouter = '\\Mod\\'.$Mod->getName().'\\Router\\ACP') && class_exists($modRouter)) {
            $h = (new $modRouter($this->rq->shift()))->route();

            // предполагаем, что текущий вызов это ОТ
        }else{
            $this->rq->action = $this->rq->uf[count($this->rq->uf) - 1];
            if (!isset($_REQUEST['NewObject']) || !is_array($_REQUEST['NewObject']) || empty($_REQUEST['NewObject'])) {
                throw new \Verba\Exception\Routing();
            }
            reset($_REQUEST['NewObject']);
            $this->rq->ot_id = key($_REQUEST['NewObject']);

            $h = (new ObjectType($this->rq->shift()))->route();
        }

        if (!(isset($_REQUEST['_norelocate']) && $_REQUEST['_norelocate'] == 1)) {
            $newResponse = new \Verba\Response\Raw();
            $newResponse->addHeader('Location', '/acp');
        } else {
            $newResponse = new \Verba\Response\Json();
        }

        $newResponse->addItems($h);

        return $newResponse->route();
    }
}
