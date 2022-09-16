<?php
namespace Verba\Mod\Routine\Block;

trait Common
{

    public $valid_otype;

    function route()
    {
        if (!$this->rq->ot_id || !($oh = \Verba\_oh($this->rq->ot_id))
            || $this->valid_otype === false) {
            throw  new \Verba\Exception\Building('Bad params');
        }

        if ($this->valid_otype) {
            $_valid = \Verba\_oh($this->valid_otype);
            if ($oh->getCode() != $_valid->getCode()) {
                throw  new \Verba\Exception\Building('Params mismatch');
            }
        }

        return $this;
    }

    function routedActions(){
        return [];
    }

    function isRoutedAction($action)
    {
        if (!is_string($action) || !$action) {
            return false;
        }
        $action = strtolower($action);
        return is_array(($ra = $this->routedActions())) && array_key_exists($action, $ra) && $ra[$action];
    }
}

