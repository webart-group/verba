<?php

namespace Verba\Act\Form\Element;

use \Html\Element;

class Datetimeselector extends Element
{
    public $templates = array(
        'body' => 'aef/fe/dateselector/dateselector.tpl',
    );
    public $classes = array('datetime-input');
    protected $defaultFormats = array(
        'displayFormat' => 'yy-mm-dd',
        'displayFormat_hm' => 'yy-mm-dd',
        'timestampToDisplay' => 'Y-m-d',
        'timestampToDisplay_hm' => 'Y-m-d H:i'
    );
    protected $displayFormat;
    protected $timestampToDisplay;
    protected $showHM = true;
    protected $userInput = true;
    protected $cfg = array(
        'showOn' => 'both',
        'buttonImage' => '', // calendar.jpg see constructor
        'buttonImageOnly' => true,
    );

    function __construct($cfg, $extensions = false, $attr = false, $aef = false)
    {
        $this->cfg['buttonImage'] = SYS_IMAGES_URL . '/ico/calendar.jpg';
        parent::__construct($cfg, $extensions, $attr, $aef);
    }

    function setDisplayFormat($val)
    {
        if (is_string($val) && !empty($val))
            $this->displayFormat = $val;
    }

    function getDisplayFormat()
    {
        return $this->displayFormat;
    }

    function setTimestampToDisplay($val)
    {
        if (is_string($val) && !empty($val))
            $this->timestampToDisplay = $val;
    }

    function getTimestampToDisplay()
    {
        return $this->timestampToDisplay;
    }

    function setShowHM($val)
    {
        $this->showHM = (bool)$val;
    }

    function getShowHM()
    {
        return $this->showHM;
    }

    function setUserInput($val)
    {
        $this->userInput = (bool)$val;
    }

    function getUserInput()
    {
        return $this->userInput;
    }

    function setCfg($val)
    {
        if (!is_array($val)) {
            return false;
        }
        $this->cfg = array_replace_recursive($this->cfg, $val);
    }

    function makeE()
    {
        $this->fire('makeE');

        $this->tpl->clear_tpl(array_keys($this->templates));
        $this->tpl->define($this->templates);

        if (!is_string($this->getDisplayFormat())) {
            $key = !$this->getShowHM() ? 'displayFormat' : 'displayFormat_hm';
            $this->setDisplayFormat($this->defaultFormats[$key]);
        }
        if (!is_string($this->getTimestampToDisplay())) {
            $key = !$this->getShowHM() ? 'timestampToDisplay' : 'timestampToDisplay_hm';
            $this->setTimestampToDisplay($this->defaultFormats[$key]);
        }
        //dateInput
        $dateInput = new \Html\Text(parent::exportAsCfg());

        if (is_string($this->getValue()) && !is_numeric($this->getValue())) {
            $ts = strtotime($this->getValue());

            $value = is_numeric($ts) && $ts > 0 ? date($this->getTimestampToDisplay(), $ts) : '';

            $dateInput->setValue($value);

        }
        if (!$this->getUserInput()) {
            $dateInput->setReadonly(true);
        }

        if (!isset($this->cfg['dateFormat'])) {
            $this->cfg['dateFormat'] = $this->getDisplayFormat();
        }
        $this->tpl->assign(array(
            'DATESELECT_FE_ID' => $dateInput->getId(),
            'DATESELECT_DISABLED' => $dateInput->makeDisabled(),
            'DATESELECT_CFG' => json_encode($this->cfg),
            'DATESELECT_REGION' => SYS_LOCALE,
            'DATESELECT_JS_CLASS_NAME' => $this->getShowHM() ? 'datetimepicker' : 'datepicker',
        ));

        $this->tpl->assign(array(
            'DATESELECT_FE' => $dateInput->build()
        ));
        $eb = $this->getEbox();
        $eb->addClasses('date-input-wrap');
        $this->setE($this->tpl->parse(false, 'body'));
        $this->aef()->addCSS(array(
            array('jquery-ui-timepicker-addon', '/js/jquery/timepicker-addon'),
        ));
        if (SYS_LOCALE !== 'en') {
            $this->aef->addScripts('jquery.ui.datepicker-' . SYS_LOCALE, 'jquery/ui');
        }
        $this->aef()->addScripts(array(
            array('jquery-ui-timepicker-addon', 'jquery/timepicker-addon'),
        ));


        $this->fire('makeEFinalize');
    }
}
