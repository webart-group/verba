<?php
namespace Mod\Profile\Router;

use Mod\Profile\Block\Accounts\Tab;

class Accounts extends \Verba\Request\Http\Router {

    function route()
    {
        switch ($this->rq->node) {
            case '':
                $b = new Tab($this);
                break;
            case 'list':
                $b = new \Mod\Routine\Router($this->rq->shift(), array(
                    'valid_otype' => 'account',
                    '_handlers' => array(
                        'update' => '\profile_accountsCUNow',
                    )
                ));
        }

        if (!isset($b)) {
            throw new \Exception\Routing();
        }

        $handler = $b->route();

        return $handler;
    }
}