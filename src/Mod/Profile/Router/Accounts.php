<?php
namespace Verba\Mod\Profile\Router;

use Verba\Mod\Profile\Block\Accounts\Tab;

class Accounts extends \Verba\Request\Http\Router {

    function route()
    {
        switch ($this->rq->node) {
            case '':
                $b = new Tab($this);
                break;
            case 'list':
                $b = new \Verba\Mod\Routine\Router($this->rq->shift(), array(
                    'valid_otype' => 'account',
                    '_handlers' => array(
                        'update' => '\profile_accountsCUNow',
                    )
                ));
        }

        if (!isset($b)) {
            throw new \Verba\Exception\Routing();
        }

        $handler = $b->route();

        return $handler;
    }
}