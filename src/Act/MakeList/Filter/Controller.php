<?php

namespace Verba\Act\MakeList\Filter;

class Controller extends \Verba\Configurable
{

    /**
     * @var \MakeList
     */
    protected $list;

    protected $filters = [];
    protected $filtersValues = [];

    protected $rq;
    /**
     * @var \Verba\FastTemplate
     */
    protected $tpl;
    /**
     * @var WorkingData
     */
    public $WorkingData;

    public $fcfg = array();

    function __construct($list)
    {
        $this->list = $list;
        $this->extractFiltersValues();
        $this->fcfg = $this->list->gC('filters');
        $this->tpl = \Verba\Hive::initTpl();
    }

    function __call($method, $args)
    {
        if (!method_exists($this->list, $method)) {
            throw new \Exception('Undefined class method called: ' . __CLASS__ . '::' . $method . '()');
        }

        return call_user_func_array(array($this->list, $method), $args);
    }

    function getWD()
    {
        if (!isset($this->FiltersWorkingData)) {
            $this->WorkingData = new WorkingData($this);
        }
        return $this->WorkingData;
    }

    function getList()
    {
        return $this->list;
    }

    function oh()
    {
        return $this->list->oh();
    }

    function getIdBase()
    {
        return $this->list->getListId();
    }

    function getEByCode($attr)
    {

        if (!array_key_exists($attr, $this->filters)) {
            return false;
        }
        $E = $this->filters[$attr]->getE();

        return $E;

    }

    function extractFiltersValues()
    {
        $fltRequestValues = $this->list->getRequest('flt');
        if (!is_array($fltRequestValues) || !count($fltRequestValues)) {
            return;
        }
        foreach ($fltRequestValues as $k => $v) {
            if (is_array($v)) {
                $this->filtersValues[$k] = $v;
            } else {
                $this->filtersValues[$k] = urldecode($v);
            }
        }
    }

    function getFilterValue($name)
    {
        $name = (string)$name;
        return array_key_exists($name, $this->filtersValues)
            ? $this->filtersValues[$name]
            : null;
    }

    function addFiltersFromCfg()
    {

        if (!isset($this->fcfg['items'])
            || !is_array($this->fcfg['items'])
            || !count($this->fcfg['items'])
        ) {
            return false;
        }

        $flt_wrap_class = 'list-filter';
        if (is_string($this->fcfg['item_wrap']['class']) && strlen($this->fcfg['item_wrap']['class'])) {
            $flt_wrap_class .= ' ' . $this->fcfg['item_wrap']['class'];
        }

        $defaultEcfg = array(
            'attr' => array(
                'list-func' => 'filter'
            ),
            'wrap' => array(
                'templates' => array(
                    'ebox_inner' => $this->fcfg['item_wrap']['tpl']
                ),
                'classes' => $flt_wrap_class,
            )
        );

        foreach ($this->fcfg['items'] as $key => $cfg) {
            $className = false;
            if (is_string($cfg)) {
                $className = $cfg;
                $cfg = array();
            } elseif (is_array($cfg)) {
                if (array_key_exists('className', $cfg)) {
                    $className = $cfg['className'];
                    unset($cfg['className']);
                } elseif (!is_numeric($key)) {
                    $className = $key;
                }
            }

            if (!$className) {
                continue;
            }

            $className = strpos($className,'\\') === false
                ? '\Verba\Act\MakeList\Filter\\'.ucfirst($className)
                : $className;

            if (!class_exists($className)) {
                $this->log()->error('Bad MakeList filter class key['.var_export($key, true).'], className ['.var_export($className, true).']');
                continue;
            }

            $cfg['captionInside'] = $this->fcfg['captionInside'];

            $defaultEcfg_copy = $defaultEcfg;
            if (isset($cfg['ecfg']['wrap']['classes'])) {
                if (is_array($cfg['ecfg']['wrap']['classes'])) {
                    $defaultEcfg_copy['wrap']['classes'][] = $flt_wrap_class;
                } else {
                    $cfg['ecfg']['wrap']['classes'] .= ' ' . $flt_wrap_class;
                }
            }

            if (!array_key_exists('ecfg', $cfg)) {
                $cfg['ecfg'] = $defaultEcfg_copy;
            } else {
                $cfg['ecfg'] = array_replace_recursive($defaultEcfg_copy, $cfg['ecfg']);
            }

            /**
             * @var $F \Verba\Act\MakeList\Filter
             */
            $F = new $className($this, $cfg);

            $F->extractValue();

            $this->filters[$F->getAlias()] = $F;
        }

        return true;
    }

    function apply()
    {
        if (!count($this->filters)) {
            return null;
        }
        foreach ($this->filters as $F) {
            $F->applyValue();
        }
        return true;
    }

    function parse()
    {

        if (!count($this->filters)) {
            return '';
        }
        $this->tpl->define(array(
            'list_filters' => $this->fcfg['wrap']['tpl'],
            'list_filters_item_wrap' => $this->fcfg['item_wrap']['tpl'],
            'list_filters_item_caption' => $this->fcfg['item_wrap']['caption_tpl'],
        ));

        foreach ($this->filters as $fe) {
            $fe->prepare();
        }


        foreach ($this->filters as $F) {
//      if($F->getHidden()){
//        continue;
//      }

            if ($this->fcfg['captionInside']) {
                $capElement = '';
            } else {
                $this->tpl->assign('LIST_FILTER_CAPTION', $F->getCaption());
                $capElement = $this->tpl->parse(false, 'list_filters_item_caption');
            }


            $F->getE()->tpl()->assign(array(
                'LIST_FILTER_CAPTION' => $capElement,
            ));

            $this->tpl->assign('FILTERS_ITEMS', $F->build(), true);

        }

        //FILERS BUTTONS
        $this->tpl->assign('FILTERS_BUTTONS_PANEL', '');
        if (is_array($this->fcfg['buttons'])) {
            if (is_array($this->fcfg['buttons']['items']) && count($this->fcfg['buttons']['items'])) {
                foreach ($this->fcfg['buttons']['items'] as $bkey => $blangkey) {
                    $this->tpl->assign(array(
                        'LIST_FILTERS_BUTTON_' . strtoupper($bkey) . '_TITLE' => \Verba\Lang::get($blangkey),
                    ));
                }
                $this->tpl->define('filters_buttons_panel', $this->fcfg['buttons']['tpl']);
                $this->tpl->parse('FILTERS_BUTTONS_PANEL', 'filters_buttons_panel');
            }
        }

        $wrap_class = 'list-' . $this->list->getListId() . '-filters list-filters-area';
        if (is_string($this->fcfg['wrap']['class']) && strlen($this->fcfg['wrap']['class'])) {
            $wrap_class .= ' ' . $this->fcfg['wrap']['class'];
        }

        $this->tpl->assign(array(
            'LIST_FILTERS_WRAP_CLASS' => $wrap_class,
        ));

        return $this->tpl->parse(false, 'list_filters');
    }

}
