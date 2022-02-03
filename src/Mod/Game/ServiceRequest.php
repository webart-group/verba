<?php

namespace Mod\Game;

class ServiceRequest
{

    protected $serviceIsOblivious;

    public $gameAction = 'buy';
    /**
     * @var bool|\Mod\Game\Item
     */
    public $game;
    /**
     * @var bool|\Mod\Game\Service
     */
    public $service;

    function isServiceOblivious()
    {
        return $this->serviceIsOblivious;
    }

    function __construct($game, $service = null)
    {
        /**
         * @var $mGame \Mod\Game
         */
        $mGame = \Verba\_mod('game');

        if (is_object($game)) {
            if ($game instanceof \Verba\Request) {
                $rq = $game;
                $game = array_key_exists(0, $rq->uf) ? $rq->uf[0] : false;
                $service = array_key_exists(1, $rq->uf) ? $rq->uf[1] : false;
            }
        }

        if (!$game) {
            return false;
        }

        $this->game = $mGame->getGame($game);

        if (!$this->game) {
            return false;
        }
        if (!$service) {
            $this->serviceIsOblivious = true;
            $this->service = $this->game->getFirstService();
        } else {
            $this->service = $this->game->getService($service);
        }

        return true;
    }

    function isValid()
    {
        return is_object($this->game) && is_object($this->service) && $this->game->active && $this->service->active;
    }

}
