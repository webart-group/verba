<?php

namespace Verba\Act\Form\Element;

class ForeignSelectPlusParentsGameServers extends ForeignSelectPlusParents
{
    function _init()
    {
        $this->listen('loadValuesBefore', 'setGameServersAsParents', $this);
    }

    function setGameServersAsParents()
    {
        $service = $this->ah()->getExtendedData('gameService');
        //$gameCat = $this->aef()->getExtendedData('gameCat');
        if (!is_object($service)) {
            throw  new \Verba\Exception\Building('Unable to find game service to add parents');
        }

        $this->parents = array(
            $service->ot_id => array($service->id)
        );
        $_cat = \Verba\_oh('catalog');
        $_game = \Verba\_oh('game');
        $gameOT = $_game->getID();
        $br = \Verba\Branch::get_branch(array($_cat->getID() => array('iids' => $service->id, 'aot' => $_game->getID())), 'up', 1);
        if (!isset($br['handled'][$gameOT])
            || !is_array($br['handled'][$gameOT])
            || !count($br['handled'][$gameOT])) {
            throw  new \Verba\Exception\Building('Unable to find game for service');
        }
        $gameId = current($br['handled'][$gameOT]);

        $this->parents[$gameOT] = array($gameId);
    }
}
