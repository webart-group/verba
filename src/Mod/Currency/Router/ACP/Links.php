<?php

namespace Verba\Mod\Currency\Router\ACP;

class Links extends \Verba\Request\Http\Router
{
    function route()
    {
        $urlBase = '/acp/h/currency/links';
        switch ($this->rq->node) {
            case 'input':
            case 'output':
                $cfgMeth = 'getLinksCfg' . ucfirst($this->rq->node);
                $lcfg = $this->$cfgMeth();
                $url = $urlBase . '/' . $this->rq->node;
                $rq = $this->rq->shift();
                break;

            default:
            case '':
                $lcfg = $this->getLinksCfgDefault();
                $url = $urlBase;
                $rq = $this->rq;
                break;
        }

        if (!isset($lcfg)) {
            throw new \Exception;
        }

        $router = new \Verba\Mod\Links\Router\ACP\Routine($rq, array(
            'lcfg' => $lcfg,
            'urlbase' => $url));

        return $router;
    }

    function getLinksCfgDefault()
    {
        return array(
            'p' => array(
                'ot' => 'currency',
                'attrs' => array('code'),
            ),
            's' => array(
                'ot' => 'currency',
                'attrs' => array('code'),
            ),
            'extFields' => array(
                'ex' => array(
                    'datatype' => 'float',
                    'title' => 'Ex, %',
                    'handlers' => array(array('currency', 'lkh_recountExPolOt'))
                ),
                'ot' => array(
                    'datatype' => 'float',
                    'title' => 'Отдадим'
                ),
                'p_ratio' => array(
                    'datatype' => 'float',
                ),

                'ch_ratio' => array(
                    'datatype' => 'float',
                    'handlers' => array(array('currency', 'lkh_handleBaseCurRateUpdated'))
                ),
                'pol' => array(
                    'datatype' => 'float',
                    'title' => 'Получим'
                ),
                'priority' => array(
                    'datatype' => 'integer',
                    'title' => 'Приор.'
                )
            ),
            'cols_order' => array(
                'ex', 'ot', 'p_data_code', 'p_ratio', 'ch_ratio', 's_data_code', 'pol'
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
                'curr_cross' => array(
                    '_className' => 'CurrencyCrossUI',
                    'baseCurCode' => \Verba\Mod\Currency::getInstance()->getBaseCurrency()->code
                ),
                'exSave' => array(
                    '_className' => 'FloatSave',
                    'field' => 'ex',
                    'parentMakesPostData' => true,
                ),
            )
        );
    }

    function getLinksCfgIO()
    {
        return array(
            'p' => array(
                'ot' => 'currency',
                'attrs' => array('code'),
            ),
            'rule' => '',//'input',
            's' => array(
                'ot' => 'paysys',
                'attrs' => array('code'),
            ),
            'order' => array(
                'p_data_code' => 'a',
                's_data_code' => 'a',
            ),
            'extFields' => array(
//        'kIn' => array(
//          'datatype' => 'float',
//          'title' => 'kIn'
//        )
            ),
            'cols_order' => array(),
            'workers' => array(
//        'kinSave' => array(
//          '_className' => 'FloatSave',
//          'field' => 'kIn',
//          'parentMakesPostData' => true,
//        ),
            ),
        );
    }

    function getLinksCfgInput()
    {
        $lcfg = $this->getLinksCfgIO();
        $lcfg['rule'] = 'input';
        $lcfg['extFields']['kIn'] = array(
            'datatype' => 'float',
            'title' => 'kIn'
        );
        $lcfg['workers']['kinSave'] = array(
            '_className' => 'FloatSave',
            'field' => 'kIn',
            'parentMakesPostData' => true,
        );
        return $lcfg;
    }

    function getLinksCfgOutput()
    {
        $lcfg = $this->getLinksCfgIO();
        $lcfg['rule'] = 'output';
        $lcfg['extFields']['kOut'] = array(
            'datatype' => 'float',
            'title' => 'kOut'
        );
        $lcfg['workers']['koutSave'] = array(
            '_className' => 'FloatSave',
            'field' => 'kOut',
            'parentMakesPostData' => true,
        );
        return $lcfg;
    }
}
