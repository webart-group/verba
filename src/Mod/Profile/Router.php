<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.08.19
 * Time: 18:13
 */

namespace Mod\Profile;


class Router extends \Verba\Block\Html
{
    function route(){

        if(!User()->getAuthorized()){
            throw new \Exception\Routing('Unknown request');
        }

        switch($this->rq->node){
            case 'store':
                $b = new \profile_store($this->rq->shift());
                break;
            case 'accounts':
                $b = new Router\Accounts($this->rq->shift());
                break;
            case 'prequisites':
                $b = new \profile_prequisitesRouter($this->rq->shift());
                break;
            case 'withdrawal':
                $b = new \profile_withdrawalRouter($this->rq->shift());
                break;
            case 'balops':
                $b = new \profile_balopsRouter($this->rq->shift(), array('userId' =>\Verba\User()->getId()));
                break;
            case 'offers':
                $b = new \profile_offersRouter($this->rq->shift());
                break;

            case 'purchases':
                $b = new \profile_purchases($this->rq->shift(), array('U' => User()));
                break;

            case 'sells':
                $b = new \profile_sells($this->rq->shift(), array('U' => User()));
                break;
            case 'msgs':
                $b = new \profile_msgs($this->rq->shift(), array('U' => User()));
                break;

            default:
                $b = new Block\Profile($this->rq);
        }
        $routed = $b->route();

        if($routed instanceof \profile_pageContent){
            $contentBlockCfg = array(
                'items' => array(
                    'CONTENT' => $routed,
                ),

            );
            if(property_exists($routed, 'coloredPanelCfg')
                && is_array($routed->coloredPanelCfg)){

                $cT = new \page_coloredPanel($this->rq,
                    array_replace_recursive($routed->coloredPanelCfg, $contentBlockCfg)
                );
            }else{
                $cT = new \page_contentTitled($this->rq, $contentBlockCfg);
            }

            $routed = $cT->route();
        }

        return $routed;
    }
}
