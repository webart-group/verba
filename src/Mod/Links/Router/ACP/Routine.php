<?php
namespace Mod\Links\Router\ACP;

//links_acpRoutinRouter
class Routine extends Lcfg
{

    public $urlbase = '';

    function route()
    {
        $node = (string)$this->rq->node;
        switch ($node) {
            case '':
                $r = new \Mod\Links\Block\ACP\Routine\Tab($this, array(
                        'lcfg' => $this->lcfg,
                        'urlbase' => $this->urlbase)
                );
                $r->addItems(
                    new \Mod\Links\Block\Load($this, array(
                        'role' => 'linksLoader',
                        'lcfg' => $this->lcfg,
                    ))
                );
                break;
        }

        if (!isset($r)) {
            $router = new \Mod\Links\Router\ACP($this->rq, array('lcfg' => $this->lcfg));
            $gen = $router->route();

            $r = new \Mod\Links\Block\ACP\ActionsAdapter($gen->rq, array('p' => $this->p, 's' => $this->s));
            $r->addItems($gen);
            return $r;
        }

        if (!isset($r) || !$r) {
            throw new \Exception\Routing();
        }

        return $r;
    }

}

