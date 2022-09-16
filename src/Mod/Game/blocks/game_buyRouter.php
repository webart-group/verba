<?php

class game_buyRouter extends \Verba\Block
{
    function route()
    {
        if (isset($this->rq->uf[0]) && $this->rq->uf[0] == 'list') {
            $rq = $this->rq->shift();
            $b = new game_buyList($rq, array(
                'gsr' => new \Verba\Mod\Game\ServiceRequest($rq)
            ));
            $response = new \Verba\Response\Json($rq);
            $response->addItems($b);
            return $response;
        }

        $b = new game_catalog($this->rq, array(
            'gsr' => new \Verba\Mod\Game\ServiceRequest($this->rq)
        ));
        return $b->route();
    }
}
