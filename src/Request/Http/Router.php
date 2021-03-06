<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 28.08.19
 * Time: 17:11
 */

namespace Verba\Request\Http;

use Model\Product\Resource;
use Verba\Exception\Routing;
use Verba\Request;

class Router extends \Verba\Block {

    function route()
    {
        /**
         * @var $mUser \Verba\User\User
         */
        $mUser = \Verba\_mod('User');
        /**
         * @var $mLocal \Mod\Local
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
            goto PAGE_NOT_FOUND;
        }

        try
        {
            $RoutResult = $Router->route();
        }
        catch (\Exception $e)
        {
            $this->log()->error($e);
            if($e instanceof \Exception\Routing)
            {
                $RoutResult = (new \Verba\Response\Exception\NotFound($this))
                    ->setException($e);
            }
            else
            {
                $RoutResult = (new \Verba\Response\Exception())
                    ->setException($e);
            }
        }

        if (!isset($RoutResult)) {
            PAGE_NOT_FOUND:
            $RoutResult = new \Verba\Response\Exception\NotFound($this);
        }

        if ($RoutResult instanceof \Verba\Response) {
            return $RoutResult;
        }

        if ($RoutResult->contentType == 'json') { // if routed is json-block
            $Response = new \Verba\Response\Json($RoutResult);
            $Response->addItems($RoutResult);

        } else { // default - html block wraped by default page
            $Response = new \Layout\Local($this->rq);
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
            $className = '\Router\Index';
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

                $possibleClassName = '\Router\\'
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

        if(!isset($className)
            && false === (
                \Verba\Hive::isModExists($rq->uf[0])
                && ($className = '\\Mod\\'.ucfirst(strtolower($rq->uf[0])).'\\Router')
                && class_exists($className)
            )
        ){
            return false;
        }

        return new $className($rq->shift($shift));
    }
}
