<?php
namespace Mod\Game\Act\Form\Element\Extension;

use Act\Form\Element\Extension;

/**
 *  Заполняет селект выбранными значениями стороннего атрибута.
 *  Значения стороннего атрибута доступны через Расширенные данные - prodItem
 *
 */
class ToggleEBySelected extends Extension
{

    /**
     * @var
     */
    public $binds;

    public $templates = array(
        'jsInit' => 'aef/exts/ToggleEBySelected/jsInit.tpl'
    );

    function init()
    {
        if (is_string($this->binds)) {
            $this->binds = \Verba\Hive::explodeHandlerParamAsArray($this->binds);
        }

    }

    function engage()
    {
        if (!is_array($this->binds) || !count($this->binds)) {
            return;
        }
        $this->fe->listen('prepare', 'addClassesToBinds', $this);
        $this->fe->listen('makeEFinalize', 'run', $this);
    }

    function addClassesToBinds()
    {
        foreach ($this->binds as $cvalueId => $cacode) {
            $cFE = $this->ah()->getEByCode($cacode);
            if (!$cFE) {
                continue;
            }
            $cFE->getEbox()->addClasses('tgebs-i');
        }
    }

    function run()
    {
        $this->tpl->define($this->templates);

        $binds = array();
        foreach ($this->binds as $cvalueId => $cacode) {
            $cFE = $this->ah()->getEByCode($cacode);
            if (!$cFE) {
                continue;
            }
            $binds[$cvalueId] = $cFE->getEbox()->getId();
        }

        if (!count($binds)) {
            return;
        }

        $this->tpl->assign(array(
            'EXT_FORM_ID' => $this->ah()->getWrapId(),
            'EXT_E_ID' => $this->fe->getId(),
            'EXT_DATA_CFG' => json_encode($binds, JSON_FORCE_OBJECT),
            'TGEBS_CODE' => $this->fe()->acode,
        ));

        $this->ah()->addJsAfter($this->tpl->parse(false, 'jsInit'));
    }
}
