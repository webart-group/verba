<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.08.19
 * Time: 18:10
 */

namespace Mod\Routine;


class Router extends \Verba\Request\Http\Router {

    public $valid_otype;

    public $_handlers = [
        'update' => '\Mod\Routine\Block\CUNow',
        'create' => '\Mod\Routine\Block\CUNow',
        'cuform' => '\Mod\Routine\Block\Form',
        'remove' => '\Mod\Routine\Block\Delete',
    ];

    function route(){

        if(!array_key_exists($this->rq->node, $this->_handlers) || !$this->_handlers[$this->rq->node]
        || !class_exists($this->_handlers[$this->rq->node])) {
            throw new \Exception\Routing();
        }

        $rq = $this->rq->shift();
        $rq->setOt($this->valid_otype);

        $className = $this->_handlers[$this->rq->node];

        /**
         * @var $b \Block
         */
        $b = new $className($rq);

        return $b->route();
    }

}
