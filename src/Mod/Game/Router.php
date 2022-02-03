<?php

namespace Mod\Game;

class Router extends \Verba\Request\Http\Router {

    function route()
    {

        switch ($this->rq->node) {
            case 'get-services-by-game':
                $b = new \game_loadServicesByGame($this);
                break;
            case 'get-service-form':
                $b = new \game_serviceForm($this);
                break;
            case 'all-games-panel':
                $b = new \game_allGamesSelectorPanel($this);
                break;
            case 'add-game-request-form':
                $b = new \game_addGameRequestForm($this);
                break;
            case 'add-game-request':
                $b = new \game_addGameRequest($this);
                break;
            case 'sell':
                $rq = $this->rq->shift();
                $gsr = new \Mod\Game\ServiceRequest($rq);
                $gsr->gameAction = 'sell';
                $b = new \game_pageSell($rq, ['gsr' => $gsr]);
                break;
            case 'buy':
                $b = new \game_buyRouter($this->rq->shift());
                break;
        }

        if (!isset($b)) {
            throw new \Exception\Routing();
        }

        $routed = $b->route();

        if ($routed instanceof \game_pageContent) {

            $contentBlockCfg = array(
                'items' => array(
                    'CONTENT' => $routed,
                ),
            );

            $titleContent = false;

            if (method_exists($routed, 'getTitle')) {
                $titleContent = $routed->getTitle();
            }

            if (is_string($titleContent)) {
                $contentBlockCfg['title'] = $titleContent;
            } elseif (is_object($titleContent)) {
                $titleAsBlock = $titleContent;
            } else {
                $titleAsBlock = new \game_pageMenu($this, array(
                    'gsr' => $routed->gsr
                ));
            }

            if (isset($titleAsBlock)) {
                $contentBlockCfg['items']['TITLE'] = $titleAsBlock;
                $contentBlockCfg['templates'] = array(
                    'title' => false
                );
            }

            $routed = new \page_contentTitled($this->rq, $contentBlockCfg);
        }

        return $routed;
    }
}
