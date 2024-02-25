<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 25.02.24
 * Time: 10:01
 */

namespace Verba\Request\Console;
use \App;
use Verba\Exception\Routing;
use Verba\Hive;
use Verba\Request;
use Verba\Response;
use Verba\Response\Exception;
use Verba\Response\Exception\NotFound;
use Verba\Response\Json;

class Router extends \Verba\Block {

    function route()
    {
        /**
         * @var $mUser \Verba\Mod\User
         */
        $mUser = \Verba\_mod('User');
        /**
         * @var $mLocal \Verba\Mod\Local
         */
        $mLocal = \Verba\_mod('local');
        // if site is disabled
        if ((bool)$mLocal->gC('disableSite')
            && !in_array($_SERVER['SCRIPT_URL'], array(
                $mUser->getAuthorizationUrl(false),
                $mUser->getLoginfaildUrl(false),
            ))
            && !\Verba\User()->in_group(array(22, 23)))
        {
            $h = new \page_siteIsDisabled($this);
            $response = $h->route();
            return $response;
        }

        if(false === is_object($Router = $this->findRouter($this->request))){
            goto ROUTE_NOT_FOUND;
        }

        try
        {
            $RoutResult = $Router->route();
        }
        catch (\Exception $e)
        {
            $this->log()->error($e);
            if($e instanceof Routing)
            {
                $RoutResult = (new NotFound($this))
                    ->setException($e);
            }
            else
            {
                $RoutResult = (new Exception())
                    ->setException($e);
            }
        }

        if (empty($RoutResult)) {
            ROUTE_NOT_FOUND:
            $RoutResult = new NotFound($this);

        }

        if ($RoutResult instanceof Response) {
            return $RoutResult;
        }

        if ($RoutResult->contentType == 'json') { // if routed is json-block
            $Response = new Json($RoutResult);
            $Response->addItems($RoutResult);

        } else { // default - html block wraped by default page
            $Response = new \App\Layout\Local($this->rq);
            $Response->addItems(array(
                'CONTENT' => $RoutResult
            ));
        }

        return $Response->route();
    }

    public function findRouter(Request $request)
    {
        $rq = clone $request;
        $urlFragments = $rq->uf;

        if ($urlFragments[count($urlFragments) - 1] == 'index.php') {
            array_pop($urlFragments);
        }
        $shift = 0;
        if (!count($urlFragments)) {
            $className = '\\App\\Router\\Index';
        }else{
            $chank_i = 0;
            $b = function ($val) use (&$chank_i){
                $val = strtolower($val);
                return $chank_i++ === 0 ? $val : ucfirst($val);
            };

            $a = function($val) use ($b, &$chank_i) {
                $chanks = preg_split("/\W+/i", $val);
                $chank_i= 0;
                $chanks = array_map($b, $chanks);
                return implode('', $chanks);
            };
            $urlFragments = array_map($a, $urlFragments);

            do{
                $lastE = ucfirst(array_pop($urlFragments));

                $possibleClassName = '\\App\\Router\\'
                    . implode('\\', $urlFragments)
                    . '\\'.$lastE;

                $possibleClassName = str_replace('\\\\', '\\', preg_replace("/[^\w\\\]/i", '_', $possibleClassName));

                if(class_exists($possibleClassName)){
                    $className = $possibleClassName;
                    $shift = count($urlFragments) + 1;
                    break;
                }
            }while(count($urlFragments));
        }

        if(!isset($className)){
            if(\Verba\Hive::isModExists($rq->uf[0])
                    && ($className = '\\Verba\\Mod\\'.ucfirst(strtolower($rq->uf[0])).'\\Router')
                    && class_exists($className)
            ) {
                $shift = 1;
            } elseif($NotFoundRouter = App::$self->gC('not_found_router')) {
                $className = $NotFoundRouter;
            } else {
                return false;
            }
        }

        return new $className($rq->shift($shift));
    }
}
