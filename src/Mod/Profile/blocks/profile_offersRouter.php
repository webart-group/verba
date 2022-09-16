<?php

class profile_offersRouter extends \Verba\Block
{

    function route()
    {
        switch ($this->rq->node) {
            case 'list':
                $b = new profile_offersActions($this->rq->shift());
                break;
            case '':
                $b = new profile_offersTab($this);
        }

        if (!isset($b)) {
            throw new \Verba\Exception\Routing();
        }

        return $b->route();
    }

}
