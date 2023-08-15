<?php

namespace Verba\Mod\Lister;

class Router extends \Verba\Block\Html
{

    protected $rqMethod;

    function route()
    {
        $action = isset($this->request->action)
            ? $this->request->action
            : (!empty($this->request->uf)
                ? $this->request->uf[count($this->request->uf) - 1]
                : false);

        switch (strtolower($action)) {
            case 'selectitem'  :
                $this->rqMethod = 'selectItem';
                break;
            case 'unselectitem'  :
                $this->rqMethod = 'unselectItem';
                break;
            case 'optionsstate'  :
                $this->rqMethod = 'optionsState';
                break;
            default:
                throw new \Verba\Exception\Routing();
        }

        //$this->request->addParam($cfg);

        $response = new \Verba\Response\Json($this->request);
        $response->addItems($this);
        return $response;
    }

    function build()
    {
        if (!$this->rqMethod) {
            return false;
        }
        $mod = \Verba\_mod('lister');
        $this->content = $mod->{$this->rqMethod}($this->request->asArray());
        return $this->content;
    }
}
