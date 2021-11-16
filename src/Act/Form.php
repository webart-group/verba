<?php

namespace Verba\Act;

class Form extends AddEditHandler
{

    public $formName = 'MyForm';
    public $formWrapId;

    private $formElements = array();

    private $lcd_attrs = array();

    protected $_confDefaultPropName = 'config_default';
    protected $_confOnlyPubPropDirectSetAllowed = false;

    protected $title;
    public $Selection;
    public $founded_in_particle = array();
    private $_defaultParticlesTpl = '';
    protected $defaultFieldExtensions;

    protected $jsCfg = array(
        'E' => array(
            'wrap' => '#'
        ),
        'items' => array(),
        'ot_id' => false,
        'action' => false,
        'id' => false,
        'baseName' => false,
        'baseId' => false,
        'iid' => false,
        'validator' => false,
        'workers' => array(),
    );

    static $config_default;

    function __construct($cfg)
    {
        $this->initConfigurator(SYS_CONFIGS_DIR.'/forms', 'form', 'config');
        $this->config = self::$_config_default;
        $this->log();
        if (!is_array($cfg)) {
            throw new \Exception('Bad cfg');
        }

        if (array_key_exists('iid', $cfg)) {
            $this->setIid($cfg['iid']);
            unset($cfg['iid']);
        }

        if (array_key_exists('action', $cfg)) {
            $this->setAction($cfg['action']);
            unset($cfg['action']);
        }
        if (!$this->action) {
            throw new \Exception('Action is empty');
        }
        # apply cfg
        $this->initAndParseCfg($cfg);

        if (!$this->ot_id) {
            throw new \Exception('OType is empty');
        }

        if (!$this->keyId) {
            $this->setKeyId($this->oh->getBaseKey());
        }

        $fkey = \Verba\Hive::make_random_string(5, 5, 'l');
        $this->setFormName('aeform_' . $this->oh->getID() . '_' . $fkey);
        $this->setFormId('f' . $this->oh->getID() . '_' . $fkey);
        $this->setFormWrapId('aeform_' . $this->oh->getID() . '_' . $fkey . '_container');

        $this->tpl = \Verba\Hive::initTpl();
    }

    function _applyConfig($cfg, $params = array())
    {

        $params = (array)$params;

        if (!array_key_exists('subcases', $params)) {
            $params['subcases'] = array($this->getAction());
        }

        parent::_applyConfig($cfg, $params);
    }

    function setMultiMode($val)
    {
        $this->sC((int)((bool)$val), 'multi_mode');
    }

    function getMultiMode()
    {
        return $this->gC('multi_mode');
    }

    function getFormName()
    {
        return $this->formName;
    }

    function setFormName($val)
    {
        if (is_string($val) && !empty($val)) $this->formName = $val;
    }

    function getWrapId()
    {
        return $this->getFormWrapId();
    }

    function getFormWrapId()
    {
        return $this->formWrapId;
    }

    function setFormWrapId($val)
    {
        if (is_string($val) && !empty($val)) $this->formWrapId = $val;
    }

    function getId()
    {
        return $this->formId;
    }

    function getFormId()
    {
        return $this->getId();
    }

    function setFormId($val)
    {
        if (is_string($val) && $val) {
            $this->formId = $val;
        }
    }

    function getIdBase()
    {
        return $this->getId() . '_NewObject_' . $this->getOTID();
    }

    function getNameBase()
    {
        return 'NewObject[' . $this->getOTID() . ']';
    }

    // Forward URL
    function setForwardUrl($url)
    {
        $this->sC($url, 'url forward');
    }

    function getForwardUrl()
    {
        return $this->gC('url forward');
    }

    function makeForwardUrl()
    {
        $faction = $this->getAction() == 'new'
            ? 'create'
            : 'update';

        $url = new \Url($_SERVER['REQUEST_URI']);
        $path = explode('/', $url->getFullPath());
        $path[count($path) - 1] = $faction;

        $url->setFullPath(implode('/', $path));
        $url->setFile(false);
        $url = $url->get();
        return $url;
    }

    function setBackUrl($url)
    {
        $this->sC($url, 'url back');
    }

    function getBackUrl()
    {
        return $this->gC('url back');
    }

    function setFields($fields)
    {
        if (!is_array($fields) || !count($fields)) {
            return false;
        }
        $cf = $this->gC('fields');
        $r = array();
        foreach ($fields as $name => $cfg) {
            if (is_numeric($name)) {
                if (is_string($cfg)) {
                    $name = $cfg;
                    $cfg = array();
                } else {
                    continue;
                }
            } else {
                if ($cfg === false || $cfg === null
                    && array_key_exists($name, $cf)) {
                    unset($cf[$name]);
                    continue;
                }
                if (!is_array($cfg)) {
                    settype($cfg, 'array');
                }
            }
            $r[$name] = $cfg;
        }
        $merged = array_replace_recursive($cf, $r);
        $this->sC($merged, 'fields');
    }

    function default_TPL_sets()
    {

        $this->addScripts(
            array('form formValidator', 'form'), 900
        //,array('old', 'form/validators')
        );
        $this->addCSS(['form'], 900);

        $this->tpl = $this->tpl();
        $cfg = $this->gC();
        $this->_defaultParticlesTpl = self::$_config_default['particles'];
        $tpls = array(
            'form' => $cfg['form']['tpl'],
            'particles' => $cfg['particles'],
            'default_particles_row' => $cfg['default_particles_row'],
            'aef_title' => $cfg['title']['tpl'],
            'field_label_tpl' => $cfg['field_default']['field_label_tpl'],
        );

        $this->tpl->clear_tpl(array_keys($tpls));
        $this->tpl->define($tpls);
        $wrapClass = 'addedit-form-container';
        if (!empty($cfg['class'])) {
            $wrapClass .= ' ' . $cfg['class'];
        }

        if (!empty($cfg['form']['class'])) {
            $formClass = $cfg['form']['class'];
        } else {
            $formClass = '';
        }

        $this->tpl->assign(array(
            'ITEM_IID' => $this->iid,
            'FORM_NAME' => $this->formName,
            'FORM_ID' => $this->formId,
            'FORM_CLASS' => $formClass,
            'AEF_CONTAINER_ID' => $this->formWrapId,
            'FORM_ACTION_SCRIPT' => $this->getForwardURL(),
            'AEF_OT_ID' => $this->ot_id,
            'AEF_TITLE' => '',
            'AEF_BLOCK_CONTENT' => '',
            'EXTENDED_HIDDEN_ELEMENTS' => '',
            'EXTERNAL_COMPONENTS' => '',
            'AEF_VIRTUAL_COMPONENTS' => '',
            'AEF_ROW_CLASS' => (string)$cfg['default_particles_row_class'],
            'CONTAINER_CLASS_ATTR' => ' class="' . $wrapClass . '"',
            'FORM_CONFIGS_STR' => str_replace('|', ',', implode(' ', $this->_confAppliedNames)),
        ));
        $this->addHidden(session_name(), session_id());
    }

    function defaultHiddens()
    {
        $this->addHidden('ot_id', $this->ot_id);
        if ($this->getAction() == 'edit' || $this->getAction() == 'editnow') {
            $this->addHidden('iid', $this->iid);
        }
        $this->addHidden('NewObject[' . $this->ot_id . '][ok]', is_numeric($this->keyId) ? $this->keyId : '0');
        $this->addHidden('NewObject[' . $this->ot_id . '][multi_mode]', $this->getMultiMode());
    }

    function parseParentsHiddens()
    {
        if ($this->getAction() == 'new' ||
            ($this->getAction() == 'edit' && $this->gC('forceIncludeParents'))) {
            if (count($this->parents)) {
                foreach ($this->parents as $pot => $piids) {
                    if (!is_array($piids) || !count($piids)) continue;
                    foreach ($piids as $piid) {
                        $this->addHidden('pot[' . $pot . '][' . $piid . ']', $piid);
                    }
                }
            }
        }
    }

    function setLocalesAllowed($var)
    {
        $this->sC((bool)$var, 'feats locales');
    }

    function getLocalesAllowed()
    {
        return $this->gC('feats locales');
    }

    function fillAttrs()
    {
        if ($this->getAction() == 'edit') {
            $r = 'u';
            $denied_behaviors = array('hidden_edit');
        } elseif ($this->getAction() == 'new') {
            $r = 'c';
            $denied_behaviors = array('hidden_new');
        }
        $attrs = array_flip($this->oh->getAttrs(true, false, $denied_behaviors, $r));
        $attrsFromCfg = $this->gC('fields');
        $r = array();
        if (is_array($attrsFromCfg) && !empty($attrsFromCfg)) {
            foreach ($attrsFromCfg as $key => $cfg) {
                if (is_numeric($key) && is_string($cfg)) {
                    $attr_code = $cfg;
                    $attr_cfg = array();
                } else {
                    $attr_code = $key;
                    $attr_cfg = $cfg;
                }
                $A = $this->oh->A($attr_code);
                // is virtual attr
                if (!$A
                    && isset($attr_cfg['type']) && strtolower(trim($attr_cfg['type'])) == 'virtual') {
                    if (!array_key_exists('formElement', $attr_cfg)) {
                        $this->log()->error('FormElement is not defined for attr \'' . var_export($attr_code, true) . '\'');
                        continue;
                    }
                    $r[$attr_code] = $attr_cfg;
                } elseif (is_object($A) && array_key_exists($A->getCode(), $attrs)) {
                    $r[$attr_code] = $attr_cfg;
                    unset($attrs[$attr_code]);
                } else {
                    continue;
                }
            }
        }
        if (!$this->gC('onlyConfigFields')
            && !empty($attrs)) {
            foreach ($attrs as $attr_code => $attr_id) {
                $r[$attr_code] = array();
            }
        }

        if (is_array($ordered = $this->gC('fields_assembly_order')) && count($ordered)) {
            //$ordered = array_reverse($ordered);
            foreach ($ordered as $attr_code) {
                if (!array_key_exists($attr_code, $r)) {
                    continue;
                }
                $this->attributes[$attr_code] = $r[$attr_code];
                unset($r[$attr_id]);
            }
            if (count($r)) {
                $this->attributes += $r;
            }
        } else {
            $this->attributes = $r;
        }

        return true;
    }

    function makeBaseParticles()
    {
        $oh = \Verba\_oh($this->ot_id);

        if (!is_array($this->attributes) || count($this->attributes) < 1) {
            return false;
        }

        //default field extensions
        if (is_array($this->defaultFieldExtensions = $this->gC('field_default extensions items'))
            && count($this->defaultFieldExtensions)) {
            $this->defaultFieldExtensions = \Configurable::substNumIdxAsStringValues($this->defaultFieldExtensions);
        } else {
            $this->defaultFieldExtensions = false;
        }

        ### Содание базовых элементов для каждого из атрибутов.
        foreach ($this->attributes as $attr_code => $attr_cfg) {
            $A = $oh->A($attr_code);
            $formElement = false;

            if (array_key_exists('formElement', $attr_cfg)) {
                if (is_string($attr_cfg['formElement']) && !empty($attr_cfg['formElement'])) {
                    $formElement = $attr_cfg['formElement'];
                }
                unset($attr_cfg['formElement']);
            }

            if (!$formElement && is_object($A)) {
                $formElement = $A->form_element;
            }

            if (!$formElement) {
                $this->log()->error('Unknown form elemet class for attr \'' . var_export($attr_code, true) . '\'');
                continue;
            }

            $this->formElements[$attr_code] = $this->createFormElement($formElement, $attr_cfg, !$A ? $attr_code : $A);
        }
        return true;
    }

    function createFormElement($element, $attr_cfg, $attr)
    {
        // extract config extensions
        if (array_key_exists('extensions', $attr_cfg)) {
            $merge = isset($attr_cfg['extensions']['merge']) ? (bool)$attr_cfg['extensions']['merge'] : true;
            if (array_key_exists('items', $attr_cfg['extensions']) && is_array($attr_cfg['extensions']['items'])) {
                $cfg_exts = \Configurable::substNumIdxAsStringValues($attr_cfg['extensions']['items']);
            }
            unset($attr_cfg['extensions']);
        }

        $extensions = false;
        $baseExtensions = array();
        $dbExtensions = array();

        // Element Wrap cfg
        $ewrap_cfg = $this->gC('field_default wrap'); // from Form cfg
        if (!is_array($ewrap_cfg)) {
            $ewrap_cfg = array();
        }

        $defaultWrapClasses = 'ff-wrap ff-wrap-' . (is_object($attr) && $attr instanceof \ObjectType\Attribute ? $attr->getCode() : (string)$attr);

        $ewrap_cfg['classes'] = !array_key_exists('classes', $ewrap_cfg)
            ? $defaultWrapClasses
            : $ewrap_cfg['classes'] . $defaultWrapClasses;

        if (array_key_exists('wrap', $attr_cfg)
            && is_array($attr_cfg['wrap'])
            && !empty($attr_cfg['wrap'])) {
            $ewrap_cfg = array_replace_recursive($ewrap_cfg, $attr_cfg['wrap']);
        }

        $attr_cfg['wrap'] = $ewrap_cfg;

        if (strpos($element, '.') !== false) {
            $baseExtensions = explode('.', $element);
            $element = array_shift($baseExtensions);
            if (count($baseExtensions) > 0) {
                $baseExtensions = \Configurable::substNumIdxAsStringValues($baseExtensions);
            }
        }

        if ($this->defaultFieldExtensions) {
            $baseExtensions = array_replace_recursive($this->defaultFieldExtensions, $baseExtensions);
        }

        if (is_object($attr) && $attr instanceof \ObjectType\Attribute) {
            $dbHandlers = $attr->getHandlers('form');
            if (is_array($dbHandlers) && count($dbHandlers)) {
                foreach ($dbHandlers as $dbeId => $dbedata) {
                    $dbExtensions[$dbedata['ah_name']] = array();
                }

                $baseExtensions = array_replace_recursive($baseExtensions, $dbExtensions);
            }
        }

        //final extensions set
        if (isset($cfg_exts) && is_array($cfg_exts)) {
            $extensions = $merge
                ? array_replace_recursive($baseExtensions, $cfg_exts)
                : $cfg_exts;
        } else {
            $extensions = $baseExtensions;
        }
        $AEFEClassName = 'Act\Form\Element\\' . ucfirst($element);
        if (!is_string($element) || empty($element) || !class_exists($AEFEClassName)) {
            throw new \Exception('Unable to find AEF Element \'' . $element . '\' class: ' . var_export($AEFEClassName, true));
        }

        $el = new $AEFEClassName($attr_cfg, $extensions, $attr, $this);

        return $el;
    }

    function getFormElements()
    {
        return $this->formElements;
    }

    function assemblyParticles()
    {

        if (!is_array($this->formElements) || !count($this->formElements)) return '';

        $pd_cfg = $this->gC('parseDefault');
        if (!is_null($pd_cfg)) {
            $parseDefault = (bool)$pd_cfg;
        } else {
            $parseDefault = $this->gC('particles') == $this->_defaultParticlesTpl
                ? true
                : false;
        }

        foreach ($this->formElements as $fe) {
            $fe->fire('prepare');
        }
        $locales = $this->gc('feats locales') ? \Verba\Lang::getUsedLC() : false;
        $parseLabels = (bool)$this->gc('parseLabels');

        foreach ($this->formElements as $ecode => $fe) {
            $fe->fire('addJsBefore');
            $fe->fire('addJsAfter');
            $fe->fire('addScripts');

            if ($fe->getHidden()) {
                $this->addHidden(new \Html\Hidden($fe->exportAsCfg()));
            } else {
                if ($locales != false && $fe->isLcd) {
                    $E = '';
                    foreach ($locales as $lc) {
                        $fe->setLocale($lc);
                        $fe->setName($fe->makeName());
                        $fe->setId($fe->makeId());
                        $fe->getEbox()->attr('data-locale', $lc);
                        $E .= $fe->build();
                        $this->addValidators($fe->getId(), $fe->getValidators());
                    }
                } else {
                    $E = $fe->build();
                    $this->addValidators($fe->getId(), $fe->getValidators());
                }

                $this->tpl->assign(array(
                    'ATTRIBUTE_DISPLAY_NAME' => $fe->getDisplayName(),
                    'ATTRIBUTE_LABEL_ID' => $fe->getId() . '_label',
                    'ATTRIBUTE_LABEL_CLASS' => 'field-label fl-' . $fe->acode,
                ));

                $nameHtml = $parseLabels
                    ? $this->tpl->parse(false, 'field_label_tpl')
                    : '';

                if ($parseDefault) { // default layout

                    $this->tpl->assign(array(
                        'ATTRIBUTE_NAME' => $nameHtml,
                        'ATTRIBUTE_VALUE' => $E,
                        'ATTRIBUTE_CODE' => $fe->acode
                    ));
                    $this->tpl->parse('AEF_BLOCK_CONTENT', 'default_particles_row', true);

                } else { // custom layout

                    $this->tpl->assign(array(
                        'ANAME_' . strtoupper($fe->acode) . '_0' => $nameHtml,
                        'AVALUE_' . strtoupper($fe->acode) . '_0' => $E,
                    ));

                }
            }

            $this->jsCfg['items'][$ecode] = $fe->packToClient();
        }


        if (!isset($this->tpl->LOADED['particles'])) {
            $this->tpl->loadTemplate('particles');
        }
        preg_match_all("/\{AVALUE_([a-z_]+)_0\}/im", $this->tpl->LOADED['particles'], $_buff);
        if (isset($_buff[1]) && !empty($_buff[1])) {
            foreach ($_buff[1] as $attr_sign) {
                $attr_sign = strtolower($attr_sign);
                $this->founded_in_particle[$attr_sign] = $attr_sign;
            }
        }


        if (is_array($this->_c['include_templates']) && count($this->_c['include_templates'])) {

            foreach ($this->_c['include_templates'] as $tplvar => $inc_tpl) {
                $this->tpl->clear_tpl('aef_inc_tpl');
                if (is_string($inc_tpl) && strlen($inc_tpl)) {
                    $this->tpl->define(array('aef_inc_tpl' => $inc_tpl));
                    $this->tpl->parse($tplvar, 'aef_inc_tpl');
                } elseif (is_array($inc_tpl) && is_object($inc_tpl[0])) {
                    $args = array($this);
                    $this->tpl->assign($tplvar, call_user_func_array($inc_tpl, $args));
                } else {
                    $this->tpl->assign($tplvar, '');
                }
            }

        }
        return $this->tpl->parse(false, 'particles');
    }

    function getAefByAttr($attr)
    {
        $A = $this->oh->A($attr);
        if ($A) {
            $attr = $A->getCode();
        }

        return array_key_exists($attr, $this->formElements) ? $this->formElements[$attr] : false;
    }

    function getEByCode($attr)
    {

        return $this->getAefByAttr($attr);

    }

    function addValidators($elId, $validators)
    {
        if (!is_string($elId) || !is_array($validators) || !count($validators)) return false;

        foreach ($validators as $vName => $vData) {
            $this->validators[] = \TypeFormater::getAsCmpConf($vData['v'], $elId, $vData['e']);
        }
    }

    function parseValidators()
    {
        if (!is_array($this->validators) || count($this->validators) < 1) {
            return false;
        }
        //$this->addJsAfter("void([".implode(', ',$this->validators)."])");
        //$this->addScripts('old', 'form/validators');
    }

    function parseLocaleTools()
    {
        if (!$this->gC('feats locales')) {
            $this->tpl->assign(array(
                'AEF_LOCALE_SWITCHER' => ''));
            return;
        }
        $this->addScripts(array('form', 'form'));
        $this->addJsAfter('$(document).ready(function() {new aefLocaleWatcher({ formWrapId: \'' . $this->getFormWrapId() . '\', formId: \'' . $this->getFormId() . '\', locale: \'' . SYS_LOCALE . '\' });});');

        $this->tpl->assign(array(
            'AEF_LOCALE_SWITCHER' => $this->makeLocaleSwitcher(),
        ));
    }

    // locale switcher
    function makeLocaleSwitcher()
    {
        $lcselect = new \Html\Select();
        $lcselect->setName($this->getFormId() . '_locale_switcher');
        $lcselect->setId($lcselect->getName());
        $lcselect->setValue(SYS_LOCALE);
        $lcselect->setValues(array_combine(\Lang::getUsedLC(), \Verba\Lang::getUsedLC()));
        return $lcselect->build();
    }

    function parseButtons($cfg)
    {
        global $S;

        if (!is_array($cfg) || !count($cfg)) return '';
        $this->tpl->define(array('buttons_tbl' => $cfg['tbl']));

        $buttonsWrapClass = 'form-bottom-controls';
        if (!empty($cfg['class'])) {
            $buttonsWrapClass .= ' ' . $cfg['class'];
        }

        $this->tpl->assign(array(
            'BUTTONS_ROW' => '',
            'BUTTONS_CLASSES' => $buttonsWrapClass,
        ));
        if (!is_array($cfg['items']) || !count($cfg['items'])) {
            return $this->tpl->parse(false, 'buttons_tbl');
        }

        foreach ($cfg['items'] as $buttonKey => $buttonCfg) {
            if (isset($buttonCfg['rights']) && is_array($buttonCfg['rights'])) {
                reset($buttonCfg['rights']);
                foreach ($buttonCfg['rights'] as $key => $rights) {
                    if (!$S->U()->chr($key, $rights)) {
                        continue 2;
                    }
                }
            }
            // Кнопка передана как конфиг FE елемента
            if (is_array($buttonCfg['e'])) {
                $btn_class = isset($buttonCfg['e']['_class']) ? '\\Html\\' . ucfirst(strtolower($buttonCfg['e']['_class'])) : false;
                if (!class_exists($btn_class)) {
                    continue;
                }
                /**
                 * @var $btn \Html\Submit
                 */
                $btn = new $btn_class($buttonCfg['e']);
                $estr = $btn->build();
                $btnName = $btn->getName();
                // Передана строка - кнопка из шаблона
            } elseif (is_string($buttonCfg['e']) && !empty($buttonCfg['e'])) {
                $this->tpl->clear_tpl('_aef_button_item');
                $this->tpl->define(array('_aef_button_item' => $buttonCfg['e']));
                $estr = $this->tpl->parse(false, '_aef_button_item');
                $btnName = $buttonKey;
            } else {
                continue;
            }


            $this->tpl->assign(array(
                'LIST_BUTTON_' . strtoupper($btnName) => $estr,
                'BUTTONS_ROW' => $this->tpl->getVar('BUTTONS_ROW') . $estr
            ));
        }

        return $this->tpl->parse(false, 'buttons_tbl');
    }

    function setTitleValue($val)
    {
        if (is_string($val)) {
            $this->sC($val, 'title value');
        }
    }

    function getTitleValue()
    {
        $this->sC('title value');
    }

    function parseTitle()
    {
        $cfg = $this->gC('title');
        if (!is_string($cfg['value'])) {
            return '';
        }
        $this->tpl->clear_tpl('aef_title');
        $this->tpl->define('aef_title', $cfg['tpl']);
        $this->tpl->assign(array(
            'AEF_FORM_TITLE' => $cfg['value'],
            'AEF_TITLE_CLASS_ATTR' => is_string($cfg['class']) && !empty($cfg['class']) ? ' class="' . $cfg['class'] . '"' : '',
        ));
        return $this->tpl->parse(false, 'aef_title');
    }

    function getUnlinkArrayPrefix($ruleAlias = false)
    {
        return is_string($ruleAlias)
            ? 'NewObject[' . $this->ot_id . '][_unlink_by_rule]'
            : 'NewObject[' . $this->ot_id . '][_unlink]';
    }

    function getLinkArrayPrefix($ruleAlias = false)
    {
        return is_string($ruleAlias)
            ? 'NewObject[' . $this->ot_id . '][_link_by_rule]'
            : 'NewObject[' . $this->ot_id . '][_link]';
    }

    function getDefaultWorkerPath($workerClassName = null)
    {
        $path = __CLASS__ . '\\Worker';
        return is_string($workerClassName)
            ? $path . '\\' . $workerClassName
            : $path;
    }

    // *** Action ***
    function makeForm()
    {

        if ($this->getAction() != 'edit' && $this->getAction() != 'new') {
            $error = 'Invalid AEF action';
        }
        if ($this->getAction() == 'edit' && empty($this->iid)) {
            $error = 'Object ID is empty';
        }
        if (isset($error)) {
            $this->log->error($error);
            throw new \Exception($error);
        }

        $tpl = $this->tpl();
        if (!is_string($this->getForwardUrl())) {
            $this->setForwardUrl($this->makeForwardUrl());
        }

        $this->fillAttrs();

        if (!count($this->attributes)) {
            $this->log()->error('Form have no one attribute');
            throw new \Exception($this->log()->getLastError());
        }
        // установка состояния локалезависимых атрибутов
        $locales = $this->gc('feats locales');
        if ($locales === null || $locales === true) {
            if (count(\Lang::getUsedLC()) < 2
                || !is_array($this->lcd_attrs = array_intersect($this->oh->getAttrsByBehaviors('lcd'), array_keys($this->attributes)))
                || !count($this->lcd_attrs)) {
                $this->sC(false, 'feats locales');
            } else {
                $this->sC(true, 'feats locales');
            }
        }

        //Дефолтные установки шаблонов
        $this->default_TPL_sets();
        $this->defaultHiddens();

        //Сведения об родителях
        $this->parseParentsHiddens();

        if ($this->action == 'edit' && !$this->isExistsValuesLoaded()) {
            $this->loadExistsValues();
        }

        // Генерация блоков атрибутов
        if (!$this->makeBaseParticles()) {
            $this->log->error('Attributes list is empty');
            throw new \Exception();
        }

        // Сборка частиц атрибутов в
        $tpl->assign('ADD_EDIT_OBJECT_FORM', $this->assemblyParticles());

        $this->fire('particlesAssembled');
        // Мультиязычность
        $this->parseLocaleTools();

        //Buttons Block
        $tpl->assign('AEF_SUBMIT_BLOCK', $this->parseButtons($this->gC('buttons')));

        // валидаторы в JSON формате
        $this->parseValidators();

        // Заголовок
        $tpl->assign('AEF_TITLE', $this->parseTitle());

        //External CSS
        $css = $this->gC('css');
        if ($css) {
            $this->addCSS($css);
        }
        //External Scripts
        $scripts = $this->gC('scripts');
        if ($scripts) {
            $this->addScripts($scripts);
        }

        $this->parseBackToList();

        $this->mergeHtmlIncludesWithTiedBlock();

        // парсинг хидденов
        $this->parseToHiddens();

        // Add Client Workers and initialization code
        $this->addWorkersToJs();

        //парсинг "передформенного" JavaScript-кода
        $this->prepareAndParseJsBefore();

        //парсинг "послеформенного" JavaScript-кода
        $this->prepareAndParseJsAfter();

        // готовая форма.
        return $tpl->parse(false, 'form');
    }

    //JavaScript

    function parseJSInstance()
    {
        if(!isset($this->_c['js']['instance']) || !$this->_c['js']['instance']) {
            return '';
        }

        $jsCfg = array(
            'E' => array(
                'wrap' => '#' . $this->getFormWrapId(),
                'form' => '#' . $this->getFormId(),
            ),
            'ot_id' => $this->oh->getID(),
            'action' => $this->getAction(),
            'id' => $this->getId(),
            'baseName' => $this->getNameBase(),
            'baseId' => $this->getIdBase(),
            'iid' => $this->getIID(),
        );

        if (!empty($this->_c['validator'])) {
            $jsCfg['validator'] = $this->_c['validator'];
        }

        $this->tpl->define(['__js_instance' => $this->_c['js']['instance']]);

        $jsCfg = array_replace_recursive($this->jsCfg, $jsCfg);

        $this->tpl->assign(array(
            'AEF_CLIENT_CFG' => json_encode($jsCfg, JSON_FORCE_OBJECT)
        ));

        return $this->tpl->parse(false, '__js_instance');
    }

    function parseBackToList()
    {
        $back_url = $this->getBackUrl();
        if (!$back_url) {
            $this->tpl()->assign('BACK_TO_LIST', '');
            return;
        }
        $this->tpl()->define('back_link', 'aef/base/back_link.tpl');
        $this->tpl()->assign(array(
            'BACK_URL' => $back_url,
        ));
        $this->tpl()->parse('BACK_TO_LIST', 'back_link');
    }

    /**
     * @param $oh
     * @param $dcfg
     * @param $config
     * @param \Verba\Model\Item $existsItem
     */
    static function extendCfgByUiConfigurator($oh, &$dcfg, $config, $existsItem = false)
    {
        if (isset($config['items'])
            && is_array($config['items'])
            && count($config['items'])) {
            if (!isset($dcfg['fields']) || !is_array($dcfg['fields'])) {
                $dcfg['fields'] = array();
            }
            if (!isset($dcfg['order']) || !is_array($dcfg['order'])) {
                $dcfg['order'] = array();
            }

            foreach ($config['items'] as $fieldData) {
                $A = $oh->A($fieldData['id']);

                if (!$A) {
                    \Verba\Loger::create(__CLASS__)->error('Unexists attr into cfg. OT: ' . $oh->getCode() . ', fieldData: ' . var_export($fieldData, true));
                    continue;
                }

                $acode = $A->getCode();

                if (!array_key_exists($acode, $dcfg['fields']) || !is_array($dcfg['fields'][$acode])) {
                    $dcfg['fields'][$acode] = array();
                }

                $dcfg['order']['priority'][] = $acode;
                if (isset($fieldData['required']) && $fieldData['required'] == 1) {
                    if (!array_key_exists('classes', $dcfg['fields'][$acode])) {
                        $dcfg['fields'][$acode]['classes'] = '';
                    }
                    $dcfg['fields'][$acode]['classes'] .= (empty($dcfg['fields'][$acode]['classes']) ? '' : ' ') . 'required';
                }
                // extensions
                if (isset($fieldData['handler']) && is_string($fieldData['handler']) && strlen($fieldData['handler'])) {

                    if (!isset($dcfg['fields'][$acode]['extensions']) || !is_array($dcfg['fields'][$acode]['extensions'])) {
                        $dcfg['fields'][$acode]['extensions'] = array();
                    }
                    if (!isset($dcfg['fields'][$acode]['extensions']['items']) || !is_array($dcfg['fields'][$acode]['extensions']['items'])) {
                        $dcfg['fields'][$acode]['extensions']['items'] = array();
                    }

                    $dcfg['fields'][$acode]['extensions']['items'] = array_merge(
                        $dcfg['fields'][$acode]['extensions']['items'],
                        \Verba\Hive::stringToHandlers($fieldData['handler'])
                    );

                }
                // formElement
                if (isset($fieldData['formElement']) && is_string($fieldData['formElement']) && strlen($fieldData['formElement'])) {
                    $dcfg['fields'][$acode]['formElement'] = $fieldData['formElement'];
                }
                // hidden
                if (isset($fieldData['hidden']) && $fieldData['hidden']) {
                    $dcfg['fields'][$acode]['hidden'] = true;
                }
                // value
                if (is_object($existsItem)
                    && isset($fieldData['rememberPrev']) && $fieldData['rememberPrev']) {
                    $dcfg['fields'][$acode]['value'] = $existsItem->getRawValue($acode);
                }
            }
        }
    }

}

Form::$_config_default = array(
    'class' => false,
    'parseDefault' => null,
    'parseLabels' => true,
    'url' => array(
        'forward' => false,
    ),
    'feats' => array(
        'locales' => null,
    ),
    'multi_mode' => false,
    'css' => false,
    'form' => array(
        'tpl' => 'aef/base/defaultForm.tpl',
        'class' => '',
    ),
    'particles' => 'aef/base/default_particles.tpl',
    'include_templates' => array(),
    'default_particles_row' => 'aef/base/default_particles_row.tpl',
    'default_particles_row_class' => '',
    'field_default' => array(
        'field_label_tpl' => 'aef/base/field-label.tpl',
        'classes' => false,
        'extensions' => array(
            'merge' => true,
            'items' => array(),
        ),
        'wrap' => array(
            '_class' => '\Html\Div',
            //'classes' => '',
            // .... Any cfg here for AEF Wrap Element
        ),
    ),
    'buttons' => array(    // кнопки
        'tbl' => 'aef/base/submit_block.tpl',
        'class' => '', // css-classes names
        'items' => array(
            'submit' => array(
                'e' => array(
                    '_class' => 'submit',
                    'classes' => 'btn btn-blue',
                    'name' => 'submitButton',
                    'value' => \Verba\Lang::get('aef buttons submit')
                ),
                'rights' => 'd'
            ),
        )
    ),
    'title' => array(
        'tpl' => 'aef/base/title.tpl',
        'class' => 'form-title'
    ),
    'locale' => array(
        'tpl' => 'aef/base/locale/block.tpl',
    ),
    'js' => [
        'instance' => 'aef/base/js/instance.tpl',
        'wrap_onready' => false,
    ],
    'onlyConfigFields' => false,
    'fields' => array(),
    'fields_assembly_order' => false,
    'forceIncludeParents' => false,
    'validator' => false,
    'workers' => array(),
);

/*
'fields' => array(

    'owner' => array(
        'displayName' => '',
        'formElement' => '',
        'alt' => '',
        'tabindex' => '',
        'accesskey' => '',
        'classes'    => '',
        'height'    => '',
        'width'    => '',
        'value'    => '',

        'extensions' => array(
            'merge' => false,
            'items' => array(
                'extensionName',
                'extensionName2' => array(
          'ext2Prop1' => '',
          'ext2Prop2' => '',
        )
            ),
        ),
    ),

),
*/
