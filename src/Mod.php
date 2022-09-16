<?php
namespace Verba;

class Mod extends Configurable
{

    public $cfg;

    protected $name;
    protected $code;
    protected $path;
    protected $path_rel;
    protected $tpl;
    protected $configFileName = false;
    protected $iidAsFileNameAllowed = false;
    protected $substEmptyIIdAsIndex = false;

    /**
     * @var \Verba\Model\Collection
     */
    protected $OTIC = false;
    protected $otic_ot;
    protected $otic_oti_class;
    /**
     * Session save point
     * @var
     */
    protected $_ssp;

    protected function __construct()
    {
        $code = explode('\\',get_class($this));
        array_shift($code);
        array_shift($code);

        $this->setName(implode('\\', $code));

        $this->setPathRel(implode('/', $code));

        $this->setPath(SYS_PATH_MODULES . '/' . $this->path_rel);

        $this->setCode(implode('_', $code));

        $this->log = Loger::create($this->getLogKey());
        $this->initConfigurator(SYS_CONFIGS_DIR.'/modules', 'cfg', 'cfg');

        $this->cfg = self::__getDefaultConf();

        $this->tpl = Hive::initTpl();

        $this->applyConfig($this->code);

        if ($this->otic_ot) {
            $this->OTIC = new \Verba\Model\Collection($this->otic_ot);
        }
    }

    function init()
    {

    }

    /**
     * @return \Verba\Model\Collection
     */
    function OTIC()
    {
        return $this->OTIC;
    }

    function getLogKey()
    {
        return 'mod_' . $this->getCode();
    }

    function getConfFilename()
    {
        return strtolower($this->getName());
    }

    public function log()
    {
        return $this->log;
    }

    protected function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Устанавливает символьный код модуля. При установке переводит строку к нижнему регистру.
     * @param string $code символьный код модуля.
     * @return string код модуля
     */
    public function setCode($code)
    {
        $this->code = is_string($code) && !empty($code) ? strtolower($code) : false;

        return $this->code;
    }

    /**
     * Возвращает символьный код модуля. Все коды модулей хранятся в нижнем регистре
     *
     * @return string код модуля
     */
    public function getCode()
    {
        return $this->code;
    }

    public function setPath($path)
    {
        $this->path = is_string($path) && !empty($path) ? $path : false;

        return $this->path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPathRel($path)
    {
        $this->path_rel = is_string($path) && !empty($path) ? $path : false;

        return $this->path_rel;
    }

    public function getPathRel()
    {
        return $this->path_rel;
    }

    public function getTempDir()
    {
        return SYS_VAR_DIR . '/mod_' . $this->getCode();
    }

    public function getCacheDir()
    {
        return SYS_CACHE_DIR . '/mods/' . $this->code;
    }

    /**
     * Возвращает интерфейс шаблонизатора
     *
     * @return FastTemplate
     */
    function tpl()
    {
        return is_object($this->tpl) ? $this->tpl : ($this->tpl = new FastTemplate(SYS_TEMPLATES_DIR));
    }

    function extractBParams($bp = null)
    {
        global $S;
        if (!is_array($bp)) {
            $bp = array();
        }

        //Определение action
        if (isset($bp['action']) && !empty($bp['action'])) {
            $bp['action'] = strtolower($bp['action']);
        } elseif (isset($_REQUEST['action'])) {
            $bp['action'] = strtolower($_REQUEST['action']);
        } elseif (isset($S->url_fragments[$this->aURLPos])) {
            $bp['action'] = strtolower($S->url_fragments[$this->aURLPos]);
        } else {
            $bp['action'] = null;
        }

        //Определение ot_id
        if (isset($bp['ot_id'])) {
            $otsome = $bp['ot_id'];
        } elseif (isset($bp['ot_code'])) {
            $otsome = $bp['ot_code'];
        } elseif (isset($bp['ot'])) {
            $otsome = $bp['ot'];
        } elseif (isset($_REQUEST['ot_id'])) {
            $otsome = $_REQUEST['ot_id'];
        } elseif (isset($_REQUEST['ot_code'])) {
            $otsome = $_REQUEST['ot_code'];
        } elseif (isset($_REQUEST['ot'])) {
            $otsome = $_REQUEST['ot'];
        } elseif (is_numeric($this->otURLPos) && $S->url_fragments[$this->otURLPos]) {
            $otsome = $S->url_fragments[$this->otURLPos];
        }

        if (isset($otsome) && false !== ($otId = $S->otSomeToId($otsome))
            && is_object(($oh = \Verba\_oh($otId)))) {
            $bp['ot_id'] = $otId;
            $bp['ot_code'] = $oh->getCode();
        } else {
            $bp['ot_id'] = null;
            $bp['ot_code'] = null;
        }

        //Определение key
        if (isset($bp['key']) && !empty($bp['key']) && !\Verba\Data\Boolean::isStrBool($bp['key'])) {
            $bp['key'] = $bp['key'];
        } elseif (is_object($oh)) {
            $bp['key'] = $oh->getBaseKey();
        } else {
            $bp['key'] = false;
        }

        //iid
        $iid = $this->extractID($bp);
        if ($iid) {
            $bp['iid'] = $iid;
        } else {
            $bp['iid'] = null;
        }

        //parent ot
        if (array_key_exists('pot', $bp)) {
            $pot = $bp['pot'];
        } elseif (isset($_REQUEST['pot'])) {
            $pot = $_REQUEST['pot'];
        }

        if ($pot) {
            if (!is_array($pot)) {
                $pot = $S->otSomeToId($pot);
                if ($pot) {
                    if (array_key_exists('piid', $bp)) {
                        $piid = $bp['piid'];
                    } elseif (isset($_REQUEST['piid'])) {
                        $piid = $_REQUEST['piid'];
                    }
                    if ($piid && !\Verba\Data\Boolean::isStrBool($_REQUEST['piid'])) {
                        $bp['pot'] = array($pot => array($piid));
                    }
                }
            } else {
                $bp['pot'] = $pot;
            }
        }

        return $bp;
    }

    function extractID($BParams)
    {

        //Если форсированно передано в БазовыхПараметрах
        if (is_array($BParams) && array_key_exists('iid', $BParams)) {
            return $BParams['iid'];
        }

        //если передано через iid в пришедших параметрах.
        if (isset($_REQUEST['iid']) && !empty($_REQUEST['iid'])) {
            $iid = $_REQUEST['iid'];
        } elseif (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
            $iid = $_REQUEST['id'];
            //если указана позиция в УРЛ-е
        }

        return isset($iid)
        && (is_numeric($iid) && ($iid = intval($iid)) || (is_string($iid) && !\Verba\Data\Boolean::isStrBool($iid)))
        && $iid
            ? $iid
            : false;
    }

    function extractAEFActionsFromURL($bp = array())
    {
        switch ($bp['action']) {
            case 'new':
                $Faction = 'newnow';
            case 'edit':
                $Faction = 'editnow';
            case 'createform':
                $Faction = 'create';
            case 'updateform':
                $Faction = 'update';
        }

        return array($bp['action'], $Faction);
    }

    function initSSP()
    {
        if (!array_key_exists('mod', $_SESSION) || !is_array($_SESSION['mod'])) {
            $_SESSION['mod'] = array();
        }
        if (!array_key_exists($this->getCode(), $_SESSION['mod'])
            || !is_array($_SESSION['mod'][$this->getCode()])) {
            $_SESSION['mod'][$this->getCode()] = array();
        }

        $this->_ssp = &$_SESSION['mod'][$this->getCode()];
        return $this->_ssp;
    }

    function removeSSP()
    {
        if (isset($_SESSION['mod'][$this->getCode()])) {
            unset($_SESSION['mod'][$this->getCode()]);
            return true;
        }
        return null;
    }

    function getFromSession($key)
    {
        if ($this->_ssp === null) {
            $this->initSSP();
        }

        return array_key_exists($key, $this->_ssp)
            ? $this->_ssp[$key]
            : null;
    }

    function saveToSession($key, $value = null)
    {
        if ($this->_ssp === null) {
            $this->initSSP();
        }

        $this->_ssp[$key] = $value;
    }

    function removeFromSession($key)
    {
        if ($this->_ssp === null) {
            $this->initSSP();
        }

        if (array_key_exists($key, $this->_ssp)) {
            unset($this->_ssp[$key]);
            return true;
        }

        return null;
    }

}
