<?php

namespace Verba\Mod\Links\Router\ACP;

class Lcfg extends \Verba\Request\Http\Router
{

    public $lcfg = array(
        'p' => array(
            'ot' => null,
            'attrs' => array(),
        ),
        's' => array(
            'ot' => null,
            'attrs' => array(),
        ),
        'rule' => '',
        'extFields' => array(),
        'workers' => array(),
        'cols_order' => array(),
        'order' => array(),
    );

    protected $poh;
    protected $soh;
    protected $_extFieldDefault = array(
        'type' => false,
        'handlers' => array(),
        'title' => false,
    );

    function setLcfg($val)
    {
        if (!is_array($val) || empty($val)) {
            return false;
        }

        foreach (array('p', 's') as $key) {
            if (isset($val[$key]['ot'])) {
                $this->{$key . 'oh'} = \Verba\_oh($val[$key]['ot']);
                $this->lcfg[$key]['ot'] = $this->{$key . 'oh'}->getID();
            }

            if (isset($val[$key]['attrs'])
                && is_array($val[$key]['attrs'])
                && !empty($val[$key]['attrs'])) {
                $this->lcfg[$key]['attrs'] = \Configurable::substNumIdxAsStringValues($val[$key]['attrs'], array());
            }
        }

        if (is_array($val['extFields'])) {
            $this->lcfg['extFields'] = \Configurable::substNumIdxAsStringValues($val['extFields'], $this->_extFieldDefault);
        }

        if (isset($val['rule'])
            && is_string($val['rule'])
            && !empty($val['rule'])) {
            $this->lcfg['rule'] = $val['rule'];
        }

        if (isset($val['workers'])
            && is_array($val['workers'])) {
            $this->lcfg['workers'] = $val['workers'];
        }

        if (isset($val['cols_order'])
            && is_array($val['cols_order'])) {
            $this->lcfg['cols_order'] = $val['cols_order'];
        }

        if (isset($val['order'])
            && is_array($val['order'])) {
            $this->lcfg['order'] = $val['order'];
        }

        return $this->lcfg;

    }


}

