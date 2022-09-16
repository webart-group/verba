<?php

namespace Verba\Mod\Acp\Router;

class Paysys extends ObjectType
{

    function route()
    {
        switch ($this->rq->node) {
            case 'links':
                $rq = $this->rq->shifted(1);

                $lcfg = array(
                    'p' => array(
                        'ot' => 'paysys',
                        'attrs' => array('title'),
                    ),
                    's' => array(
                        'ot' => 'paysys',
                        'attrs' => array('title'),
                    ),
                    'extFields' => array(
                        'p_ratio',
                        'ch_ratio',
                        'k' => array(
                            'handlers' => array(array('payment', 'lkh_recountK'))
                        ),
                        'priority',
                    ),
                    'workers' => array(
                        'pratioSave' => array(
                            '_className' => 'FloatSave',
                            'field' => 'p_ratio',
                            'parentMakesPostData' => true,
                        ),
                        'chratioSave' => array(
                            '_className' => 'FloatSave',
                            'field' => 'ch_ratio',
                            'parentMakesPostData' => true,
                        ),
                        'prioritySave' => array(
                            '_className' => 'PrioritySave',
                            'parentMakesPostData' => true,
                        ),
                    )
                );
                $router = new \Verba\Mod\Links\Router\Acp\Routine($rq, [
                    'lcfg' => $lcfg,
                    'urlbase' => '/acp/h/paysys/links'
                ]);
                break;
        }

        if (!isset($router)) {
            $rq = clone $this->request;
            $rq->setOt('paysys');
            $router = parent::route();
        }
        $h = $router->route();

        return $h;
    }

}
