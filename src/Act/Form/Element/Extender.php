<?php

namespace Verba\Act\Form\Element;

use \Verba\Act\Action;

class Extender
{

    /**
     * @var \Verba\Html\Element
     */
    private $fe;
    /**
     * @var \Verba\Act\Form|bool
     */
    public $aef = false;

    /**
     * @var \Verba\Act\Form
     */
    public $ah;

    /**
     * @var bool|\Verba\ObjectType\Attribute
     */
    public $A = false;
    public $acode;
    /**
     * @var \Verba\Model|bool
     */
    public $oh = false;
    /**
     * @var bool|\Verba\FastTemplate
     */
    public $tpl = false;
    /**
     * @var
     */
    public $ebox;
    public $wrap = array(
        '_class' => '\Verba\Html\Div',
        'classes' => '',
        'templates' => [
            'ebox_inner' => false,
        ],
    );
    public $isLcd = false;
    public $disabled = false;
    public $wrapWithEbox = true;
    public $forcedFreshTpl = true;

    function __construct($fe, &$cfg, $attr = false, $ah = false)
    {
        $this->fe = $fe;
        $this->locale = SYS_LOCALE;

        if (is_string($attr) && !empty($attr)) {
            $this->acode = $attr;
        }

        if ($ah) {
            $this->ah = $ah;
            $this->aef = $this->ah;

            if ($attr && is_object($this->A = $this->ah->oh()->A($attr))) {
                $this->acode = $this->A->getCode();
            }

            $this->oh = $this->ah->oh();
        }

        if (!$this->forcedFreshTpl && $this->ah instanceof \Verba\Act\Action) {
            $this->fe->tpl = $this->ah->tpl();
        } else {
            $this->fe->tpl = new \Verba\FastTemplate(SYS_TEMPLATES_DIR);
        }
        $this->tpl = $this->fe->tpl;

        if (is_object($this->A)) {
            //Locale depended
            $this->isLcd = $this->A->get_lcd();

            //display Name
            $this->setDisplayName($this->A->display());

            if ($this->ah instanceof \Verba\Act\Action) {
                //disabled or readonly
                $this->disabled = $this->oh->in_behavior('avtofield', $this->A->getID())
                    || ($this->ah->getAction() == 'edit' && $this->oh->in_behavior('not_editable', $this->A->getID()));

                $this->setDisableByAttr();

                // obligatory
                $this->setObligatory($this->oh->in_behavior('obligatory', $this->A->getID()));
                if ($this->getObligatory() && !$this->fe->haveClass('required')) {
                    $this->fe->addClasses('required');
                }
            }
        }

        if (!property_exists($this->fe, 'templates') || !is_array($this->fe->templates)) {
            $this->fe->templates = array();
        }
        if (!property_exists($this->fe, 'hidden')) {
            $this->fe->hidden = null;
        }

        $this->fe->listen('prepare', 'prepareDefaults', $this);
        $this->fe->listen('initAfter', '_init', $this);

        if ($this->ah) {
            if ($this->ah instanceof \Verba\Act\Form) {
                $cfg['value'] = $this->extractExistsValue(isset($cfg['value']) && !empty($cfg['value'])
                    ? $cfg['value']
                    : null
                );
            }

            if (isset($cfg['wrap']) && is_array($cfg['wrap'])) {
                $this->wrap = array_replace_recursive($this->wrap, $cfg['wrap']);
                unset($cfg['wrap']);
            }
        }
    }

    function _init()
    {

        if (!$this->fe->getName() && $this->ah && $this->ah instanceof \Verba\Act\Form) {
            $this->fe->setName($this->makeName());
        }
        if (!$this->fe->getId() && $this->ah && $this->ah instanceof \Verba\Act\Form) {
            $this->fe->setId($this->makeId());
        }

        $this->initEbox();
    }

    function prepareDefaults()
    {

        //default Css classes by cfg[field_default]
        $defaultFeClass = $this->aef->gC('field_default classes');
        if (!empty($defaultFeClass)) {
            $this->fe->addClasses($defaultFeClass);
        }
        $this->fe->addClasses('f-field ff-' . $this->acode);
        if (is_object($this->A)) {
            $this->fe->addClasses('ff-datatype-' . $this->A->data_type);
        }

        $this->fe->fire('addClasses');
        $this->fe->fire('addEvents');
    }

    function extractExistsValue($valueFromCfg = null)
    {

        $existsValue = null;

        if (is_object($this->aef)) {
            $existsValue = $this->aef->getExistsValue($this->acode);
        }

        if ($existsValue === null
            || ($existsValue === '' && is_object($this->A) && $this->A->oh()->in_behavior('obligatory', $this->A->getID()))) {
            if ($valueFromCfg !== null) {
                return $valueFromCfg;
            } else {
                if (is_object($this->A)) {
                    $defaultValue = $this->A->getDefaultValue();
                    if ($defaultValue !== null) {
                        return $defaultValue;
                    }
                }
            }
        }

        return $existsValue;
    }

    function setDisableByAttr()
    {
        if (property_exists($this->fe, 'readonly')) {
            $p = 'Readonly';
        } elseif (property_exists($this->fe, 'disabled')) {
            $p = 'Disabled';
        } else {
            return;
        }

        if ($this->fe->{'get' . $p}() !== null || !$this->disabled) return;
        $this->fe->{'set' . $p}($this->disabled);
        return;
    }

    /**
     * @return \Verba\Html\Element
     */
    function fe()
    {
        return $this->fe;
    }

    /**
     * @return \Verba\Model
     */
    function oh()
    {
        return $this->oh;
    }

    /**
     * @return \Verba\Act\Form
     */
    function aef()
    {
        return $this->ah;
    }

    /**
     * @return \Verba\Act\Form
     */
    function ah()
    {
        return $this->ah;
    }

    /**
     * @return \Verba\ObjectType\Attribute
     */
    function A()
    {
        return $this->A;
    }

    function tpl()
    {
        return $this->tpl;
    }

    function getAcode()
    {
        return $this->acode;
    }

    function makeName()
    {
        $lc = $this->isLcd ? '[' . $this->locale . ']' : '';
        return $this->ah()->getNameBase() . '[' . $this->acode . ']' . $lc;
    }

    function getIdBase()
    {
        return $this->ah()->getIdBase();
    }

    function makeId()
    {
        $lc = $this->isLcd ? '_' . $this->locale : '';
        return $this->getIdBase() . '_' . $this->acode . $lc;
    }

    function setLocale($val)
    {
        if (\Verba\Lang::isLCValid($val))
            $this->locale = $val;
    }

    function getLocale()
    {
        return $this->locale;
    }

    function getValue()
    {
        if (is_object($this->A) && $this->isLcd) {
            if (is_array($this->fe->value) && isset($this->fe->value[$this->locale])) {
                return $this->fe->value[$this->locale];
            }
            return;
        }
        return $this->fe->value;
    }

    function setValue($val)
    {
        if (is_object($this->A) && $this->isLcd) {
            if (is_array($val)) {
                foreach (\Verba\Lang::getUsedLC() as $lc) {
                    if (isset($val[$lc])) {
                        $this->fe->value[$lc] = $val[$lc];
                    }
                }
            } else {
                $this->fe->value[$this->locale] = $val;
            }
            return;
        }
        $this->fe->value = $val;
        return;
    }

    function setWrapWithEbox($val)
    {
        $this->wrapWithEbox = (bool)$val;
    }

    function getWrapWithEbox()
    {
        return $this->wrapWithEbox;
    }

    function setObligatory($bool)
    {
        $this->fe->obligatory = (bool)$bool;
    }

    function getObligatory()
    {
        return $this->fe->obligatory;
    }

    function setDisplayName($str)
    {
        $this->fe->displayName = $str;
    }

    function getDisplayName()
    {
        return $this->fe->displayName;
    }

    function setTemplates($tpl)
    {
        if (!is_array($tpl)) return false;
        $this->fe->templates = array_replace_recursive($this->fe->templates, $tpl);
    }

    function getTemplates()
    {
        return $this->fe->templates;
    }

    function setHidden($val)
    {
        $this->fe->hidden = (bool)$val;
    }

    function getHidden()
    {
        return $this->fe->hidden;
    }

    function exportAsCfg()
    {
        $r = array();

        $r['name'] = $this->fe->getName();
        $r['id'] = $this->fe->getId();
        if ($this->fe->getTag() !== null) $r['tag'] = $this->fe->getTag();
        if ($this->fe->getValue() !== null) $r['value'] = $this->fe->getValue();
        if ($this->fe->getDisabled() !== null) $r['disabled'] = $this->fe->getDisabled();
        if (count($this->fe->getClasses()) > 0) $r['classes'] = $this->fe->getClasses();
        if (count($this->fe->getEvents()) > 0) $r['events'] = $this->fe->getEvents();
        if (count($this->fe->getTemplates()) > 0) $r['templates'] = $this->fe->getTemplates();

        return $r;
    }

    function setJsBefore($val)
    {
        $this->aef->addJsBefore($val);
    }

    function setJsAfter($val)
    {
        $this->aef->addJsAfter($val);
    }

    function setJsRemote()
    {
        $args = func_get_args();
        if (count($args) < 1) return false;
        call_user_func_array(array($this->aef(), 'addScripts'), $args);
    }

    function build()
    {

        $this->fe->makeE();

        if ($this->getWrapWithEbox()) {
            return $this->getFullBox();
        }

        return $this->fe->getE();
    }

    function getFullBox()
    {

        if (is_string($this->wrap['templates']['ebox_inner']) && !empty($this->wrap['templates']['ebox_inner'])) {

            if (!$this->tpl()->isDefined('ebox_inner')) {
                $this->tpl()->define('ebox_inner', $this->wrap['templates']['ebox_inner']);
            }
            $this->tpl()->assign(array(
                'EBOX_INNER_E' => $this->fe->getE(),
            ));

            $this->ebox->setValue($this->tpl()->parse(false, 'ebox_inner'));

        } else {
            $this->ebox->setValue($this->fe->getE());
        }

        return $this->ebox->build();
    }

    function getEbox()
    {
        return $this->ebox;
    }

    function initEbox()
    {

        $eboxClass =
            isset($this->wrap['_class'])
            && is_string($this->wrap['_class'])
            && class_exists($this->wrap['_class'], false)
                ? $this->wrap['_class']
                : 'Verba\Html\Div';

        if (!array_key_exists('id', $this->wrap)) {
            $this->wrap['id'] = $this->fe->getId() . '_ebox';
        }

        $this->ebox = new $eboxClass($this->wrap);
        return $this->ebox;
    }
}
