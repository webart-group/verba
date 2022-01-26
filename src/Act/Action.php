<?php

namespace Verba\Act;

class Action extends Parents
{
    /**
     * @var \Block\Html
     */
    protected $block;
    protected $otId;
    protected $keyId;
    /**
     * @var \Verba\Model
     */
    protected $oh;
    protected $ot_id;
    protected $iid;

    protected $action;

    protected $hidden_elements = array();

    private $loaded_configs_by_path = array();

    // параметры для хендлеров
    protected $_handlers_path_base = '';
    protected $_handlers_class_prefix = '';
    protected $_handlers_load_results = array();

    // Workers
    protected $workers = [];
    protected $_workersJsScriptsUrlBase;
    protected $_workersJsScriptsDirBase;

    //Extended Data array
    protected $extendedData = array();

    protected $jsCfg = array(
        'otId' => null,
        'keyId' => null,
    );

    /**
     * @var \FastTemplate
     */
    protected $tpl;

    use \Verba\Html\Includes;

    protected static $staticData = array();

    /**
     * @return \FastTemplate
     */
    function tpl()
    {
        return $this->tpl;
    }

    function getBlock()
    {
        return $this->block;
    }

    function setBlock($block)
    {
        if ($block instanceof \Verba\BlockInterface) {
            $this->block = $block;
        }
        return $this->block;
    }

    function setAction($action)
    {
        $action = $this->make_action_sign($action, $this->iid);
        $this->action = $action;
    }

    function getAction()
    {
        return $this->action;
    }

    static public function make_action_sign($action = false, $iid = false)
    {
        return $action;
    }

    function setExtendedData($val)
    {
        $this->extendedData = (array)$val;
    }

    function addExtendedData($array)
    {
        if (!is_array($array) || !count($array)) {
            return false;
        }
        $this->extendedData = array_replace_recursive($this->extendedData, $array);
    }

    function getExtendedData($key = null)
    {
        if ($key === null) {
            return $this->extendedData;
        }
        return isset($this->extendedData[$key])
            ? $this->extendedData[$key]
            : null;
    }

    function initAndParseCfg($cfg)
    {

        $this->parseCfg($cfg);

        //if its block-tied action
        // try to apply cfg by path
        if (is_object($this->block) && $this->block instanceof \Block) {
            $path = $this->convertRequestUriToCfgPath($this->block->rq);

            if (!array_key_exists($path, $this->loaded_configs_by_path)) {
                $this->loaded_configs_by_path[$path] = array();
                if (file_exists($path)) {
                    $this->loaded_configs_by_path[$path] = @include($path);
                }
            }

            $cfgBody = $this->loaded_configs_by_path[$path];

            if (is_array($cfgBody)) {
                $params = array(
                    'name' => '_by_path',
                    'path' => ''
                );
                $this->applyConfigDirect($cfgBody, $params);
            }
        }
    }

    /**
     * @param $rq \Verba\Request
     * @return bool|string
     */
    function convertRequestUriToCfgPath($rq)
    {
        $rqUri = $rq->getRequestUri();
        if (!is_string($rqUri)) {
            return false;
        }
        $uri = explode('/', $rq->getRequestUri());

        array_shift($uri);
        $uri = implode('~', $uri);
        return $this->getConfPath() . '/_by_path/' . $uri . '.php';
    }

    function parseCfg($cfg)
    {

        if (is_string($cfg)) {
            $this->applyConfig($cfg);
            return;
        }

        if (!is_array($cfg)) {
            return;
        }

        if (array_key_exists('cfg', $cfg)) {
            $this->applyConfig($cfg['cfg']);
            unset($cfg['cfg']);
        }

        if (array_key_exists('dcfg', $cfg)) {
            $this->applyConfigDirect($cfg['dcfg']);
            unset($cfg['dcfg']);
        }

        if (count($cfg)) {
            $this->applyConfigDirect($cfg);
        }
    }

    function setKey_id($val)
    {
        return $this->setKeyId($val);
    }

    function setKeyId($val)
    {
        $val = (int)$val;
        $this->keyId = !$val ? $this->oh->getBaseKey() : $val;
    }

    function getKeyId()
    {
        return $this->keyId;
    }

    function setOt_id($val)
    {
        return $this->setOtId($val);
    }

    function setOtId($val)
    {
        $this->oh = \Verba\_oh($val);
        $this->ot_id = $this->oh->getID();
    }

    function getOtId()
    {
        return $this->ot_id;
    }

    function oh()
    {
        return $this->getOh();
    }

    function getOh()
    {
        return $this->oh;
    }

    function mergeHtmlIncludesWithTiedBlock()
    {
        if (!isset($this->block) || !$this->block instanceof \Block\Html) {
            return;
        }
        $this->block->mergeHtmlIncludes($this);
    }

    function addHidden($name, $value = false)
    {
        if (is_object($name) && $name instanceof \Html\Hidden) {
            $this->hidden_elements[$name->getName()] = $name;
        } elseif (is_string($name)) {
            $this->hidden_elements[$name] = $value;
        }
    }

    function parseToHiddens()
    {
        if (!is_array($this->hidden_elements) || count($this->hidden_elements) < 1) return false;

        $str = '';
        foreach ($this->hidden_elements as $name => $value) {
            if (is_string($value) || is_numeric($value)) {
                $str .= "\n" . '<input type="hidden" name="' . ((string)$name) . '" value="' . ((string)$value) . '"/>';
            } elseif (is_object($value)) {
                $value->tag = 'input';
                $value->type = 'hidden';
                $str .= "\n" . $value->build();
            }
        }
        $this->tpl->assign('EXTENDED_HIDDEN_ELEMENTS', $str, true);
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

    protected function parseJs_Part_($part)
    {
        $propName = 'js'.$part;
        $this->tpl()->assign('JAVASCRIPT_'.strtoupper($part), "<script type=\"text/javascript\">\n"
            . implode("\n", $this->$propName)
            . "\n</script>");
    }

    protected function prepareAndParseJs_Part_($part){
        $propName = 'js'.ucfirst($part);
        if(isset($this->_c['js']['wrap_onready'])
            && $this->_c['js']['wrap_onready']
            && count($this->$propName)){
            array_unshift($this->$propName, "$(document).ready(function(){");
            array_push($this->$propName, "});");
        }

        $this->{'parseJs'.$part}();
        $this->$propName = [];
        return $this->$propName;
    }

    function parseJsAfter()
    {
        return $this->parseJs_Part_('After');
    }

    function parseJsBefore(){
        return $this->parseJs_Part_('Before');
    }

    function parseJSInstance(){
        return '';
    }

    function prepareAndParseJsAfter(){

        if(isset($this->_c['js']['instance']) && $this->_c['js']['instance']){
            $this->tpl->define(['__js_instance'=> $this->_c['js']['instance']]);
            array_unshift(
                $this->jsAfter,
                $this->parseJSInstance()
            );
        }

        return $this->prepareAndParseJs_Part_('After');
    }

    function prepareAndParseJsBefore(){
        return $this->prepareAndParseJs_Part_('Before');
    }

    function genClassAndCfgForFieldHandler($attr_code, $set_data)
    {
        // Если хендлер указан без пространства имен
        $className = strpos($set_data['ah_name'], '\\') === false
            ? $this->_handlers_class_prefix . '\\' . $set_data['ah_name']
            : $set_data['ah_name'];

        // Это автосгенерированный хендлер?
        $isAutohandler = is_array($set_data) && array_key_exists('_autohandler', $set_data)
            ? (bool)$set_data['_autohandler']
            : false;

        // Если класс еще не вызывался
        if (!array_key_exists($className, $this->_handlers_load_results)) {

            $this->_handlers_load_results[$className] = false;
            // Хендлер не существует
            if (!class_exists($className)) {
                if (!$isAutohandler) {
                    $this->log()->error(__CLASS__ . ' Handler `' . var_export($className, true) . '` class file not found.');
                }
                return array(false, false);
            }

            $this->_handlers_load_results[$className] = 1;

        } elseif (!$this->_handlers_load_results[$className]) {
            return array(false, false);
        }

        if (isset($set_data['params']) && !empty($set_data['params'])) {
            $cfg = $set_data['params'];
        } else {
            $cfg = array();
        }
        $cfg['attr_code'] = $attr_code;
        return array($className, $cfg);
    }

    static function addStaticData($array)
    {
        if (!is_array($array) || !count($array)) {
            return false;
        }
        self::$staticData = array_replace_recursive(self::$staticData, $array);
    }

    static function getStaticData($key = null)
    {
        if ($key === null) {
            return self::$staticData;
        }
        return isset(self::$staticData[$key])
            ? self::$staticData[$key]
            : null;
    }

    // Workers
    function getDefaultWorkerPath($workerClassName = null)
    {
        $path = __NAMESPACE__ . '\\Worker';
        return is_string($workerClassName)
            ? $path . '\\' . $workerClassName
            : $path;
    }

    function setWorkers($cfg)
    {
        if (!is_array($cfg)) {
            return false;
        }

        $rq_scripts = array();
        foreach ($cfg as $wKey => $wCfg) {
            $wName = $wAlias = false;
            if (is_array($wCfg)) {
                if (isset($wCfg['_className'])) {
                    $wName = $wCfg['_className'];
                    unset($wCfg['_className']);
                }
                if (isset($wCfg['_alias'])) {
                    $wAlias = $wCfg['_alias'];
                    unset($wCfg['_alias']);
                }
            }
            if (!$wName) {
                if (is_string($wCfg)) {
                    $wName = $wCfg;
                } elseif (!is_numeric($wKey)) {
                    $wName = $wKey;
                }
            }

            if (!$wAlias) {
                if (!is_numeric($wKey)) {
                    $wAlias = $wKey;
                } else {
                    $wAlias = $wName;
                }
            }
            if (!$wName || !$wAlias) {
                continue;
            }

            if (isset($wCfg['_script'])) {
                $rq_scripts[] = $wCfg['_script'];
                $wCfg['_script'] = null;
            }
            $this->addWorker($wAlias, $wName, is_array($wCfg) ? $wCfg : null);
        }

        if (count($rq_scripts)) {
            $sc = $this->gC('scripts');
            if (is_string($sc) && !empty($sc)) {
                $sc = array(array($sc));
            }

            if (is_array($sc)) {
                $rq_scripts = array_merge($sc, $rq_scripts);
            }
            $this->sC($rq_scripts, 'scripts');
        }
    }

    function addWorker($alias, $className, $cfg = null)
    {
        $workerClassName = $className;
        try {

            // если это упрощенное определение воркера, бэз пространства имен
            // ищем файл в дефолтной директории воркеров
            if (strpos($workerClassName, '\\') === false) {
                $workerClassName = $this->getDefaultWorkerPath($workerClassName);
            }

            if (!class_exists($workerClassName)) {
                $workerClassName = $this->getDefaultWorkerPath();
            }

            /**
             * @var $Wrk \Verba\Act\Worker
             */
            $Wrk = new $workerClassName($this, $cfg);
            $Wrk->setAlias($alias);
            $Wrk->setClassName($className);
            $this->workers[$Wrk->getAlias()] = $Wrk;

        } catch (\Exception $e) {
            $this->log()->error($e);
            return false;
        }

        return $this->workers[$alias];
    }

    function addWorkersToJs()
    {
        if (!count($this->workers)) {
            return;
        }
        /**
         * @var $worker \Verba\Act\Worker;
         */
        foreach ($this->workers as $wA => $worker) {

            $this->jsCfg['workers'][$wA] = $worker->parseClientCfg();

            if (is_string($jsScriptFile = $worker->getJsScriptFile()) && strlen($jsScriptFile)) {
                $pi = pathinfo($jsScriptFile);
                if ($pi['dirname'] != '.') {
                    $dirname = $pi['dirname']{0} == '/' ? $pi['dirname'] : $this->_workersJsScriptsUrlBase . '/' . $pi['dirname'];
                } else {
                    $dirname = $this->_workersJsScriptsUrlBase;
                }
                $filename = $pi['filename'];

                $this->addScripts(array($filename, $dirname));
            } elseif (file_exists($this->_workersJsScriptsDirBase . '/' . $worker->getClassName() . '.js')) {
                $this->addScripts(array($worker->getClassName(), $this->_workersJsScriptsUrlBase));
            }

        }
    }

    function isWorkerExists($alias)
    {
        return array_key_exists($alias, $this->workers);
    }
}
