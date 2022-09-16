<?php

namespace Verba\Act\MakeList;

class Filter extends \Verba\Configurable
{

    /**
     * @var \Verba\Act\MakeList\Filter\Controller
     */
    protected $C;
    protected $disabled;
    protected $hidden;
    protected $value;
    /**
     * @var \Verba\Act\MakeList
     */
    protected $list;
    public $templates = array(
        'content' => 'list/default/filters/item_content.tpl',
    );
    public $captionLangKey = false;
    public $caption;
    public $captionInside = true;
    /**
     * @var array возможность задать доп свойства конфига html-элемента фильтра
     */
    protected $ecfg = array();

    public $ftype = ''; // filter-type, значение используется для генерации css-класса list-filter-type-<$ftype>
    public $felement = '\Verba\Html\Text'; // filter-элемент

    public $name; // name required to generate form data key

    public $classes = array();

    /**
     * @var string attrCode
     */
    protected $attr;
    /**
     * @var bool|\Verba\ObjectType\Attribute
     */
    protected $A;
    /**
     * @var \Verba\Model
     */
    protected $oh;
    /**
     * @var \Verba\FastTemplate
     */
    protected $tpl;
    protected $_alias;
    /**
     * @var Filter\WorkingData
     */
    public $WD;
    /**
     * @var \Verba\Html\Element
     */
    public $E;

    /**
     * @param \Verba\Act\MakeList $list
     * @param mixed $cfg
     * @return \Verba\Act\MakeList\Filter
     */
    function __construct($C, $cfg = null)
    {

        $this->C = $C;

        $this->list = $this->C->getList();
        $this->oh = $this->list->getOh();

        if (array_key_exists('attr', $cfg)) {
            $this->attr = $cfg['attr'];
        }
        if (array_key_exists('name', $cfg)) {
            $this->name = $cfg['name'];
        }


        if ($this->attr && $this->oh->isA($this->attr)) {
            $this->A = $this->oh->A($this->attr);
            if ($this->name === null) {
                $this->name = $this->A->getCode();
            }
        } elseif ($this->name && $this->oh->isA($this->name)) {
            $this->A = $this->oh->A($this->name);
            $this->attr = $this->A->getCode();
        }

        if (is_string($this->name) && !empty($this->name)) {
            $this->_alias = $this->name;
        } elseif (is_object($this->A) && $this->A instanceof \Verba\ObjectType\Attribute) {
            $this->_alias = $this->A->getCode();
        } else {
            $this->_alias = 'unnamed';
        }

        $flt_wrap_extra_class = 'list-filter-' . $this->getAlias();
        if (!isset($cfg['ecfg']['wrap']['classes']) || is_string($cfg['ecfg']['wrap']['classes'])) {

            $cfg['ecfg']['wrap']['classes'] .= ' ' . $flt_wrap_extra_class;

        } else {
            if (is_array($cfg['ecfg']['wrap']['classes'])) {
                $cfg['ecfg']['wrap']['classes'][] = $flt_wrap_extra_class;
            } else {
                $cfg['ecfg']['wrap']['classes'] = [$flt_wrap_extra_class];
            }
        }

        $this->applyConfigDirect($cfg);


        $this->getAlias();

        if (!array_key_exists('name', $this->ecfg) || !$this->ecfg['name']) {
            $this->ecfg['name'] = $this->makeName();
        }

        $this->tpl = \Verba\Hive::initTpl();

        $fltBaseClass = 'flt-' . $this->getAlias();
        if (!isset($this->ecfg['classes'])) {
            $this->ecfg['classes'] = $fltBaseClass;
        } else {
            if (!isset($this->ecfg['classes']) || !is_array($this->ecfg['classes'])) {
                if (!empty($this->ecfg['classes'])) {
                    $this->ecfg['classes'] = array($this->ecfg['classes']);
                } else {
                    $this->ecfg['classes'] = array();
                }
            }
            $this->ecfg['classes'][] = $fltBaseClass;
        }
        $this->WD = $this->C->getWD();

        $this->initE();
        $this->init();
    }

    function init()
    {

    }

    function initE()
    {
        $this->E = new $this->felement($this->ecfg, false, $this->attr, $this->C);
    }

    function prepare()
    {
        if (is_object($this->E)) {
            $this->E->fire('prepare');
        }
    }

    function build()
    {

    }

    function getE()
    {
        return $this->E;
    }

    function setHidden($val)
    {
        $this->hidden = (bool)$val;
    }

    function getHidden()
    {
        return $this->hidden;
    }

    function setDisabled($val)
    {
        $this->disabled = (bool)$val;
    }

    function getDisabled()
    {
        return $this->disabled;
    }

    function getNameBase()
    {
        return $this->list->getID() . '[flt]';
    }

    function getAlias()
    {
        return $this->_alias;
    }

    function applyValue()
    {
        $fSqlAlias = $this->makeWhereAlias();
        $this->list->QM()->removeWhere($fSqlAlias);
        if (isset($this->value)) {
            $this->list->QM()->addWhere($this->value, $fSqlAlias, $this->name);
        }
    }

    function makeName()
    {
        return $this->getNameBase() . '[' . $this->getAlias() . ']';
    }

    function getName()
    {
        if ($this->name === null) {
            $this->name = $this->makeName();
        }
        return $this->name;
    }

    function getIdBase()
    {
        return $this->list->getID() . '_flt_';
    }

    function makeId()
    {
        return $this->getIdBase() . $this->getAlias();
    }

    function getId()
    {
        if ($this->id === null) {
            $this->id = $this->makeId();
        }
        return $this->id;
    }

    function getCaption()
    {
        if ($this->caption === null) {
            $this->caption = (string)$this->makeCaption();
        }
        return $this->caption;
    }

    function setCaption($val)
    {
        $this->caption = (string)$val;
    }

    function makeCaption()
    {

        $caption = '';

        if (is_string($this->captionLangKey) && !empty($this->captionLangKey)) {
            $caption = \Verba\Lang::get($this->captionLangKey);
        } elseif ($this->A) {
            $caption = $this->A->getTitle();
        }

        return $caption;
    }

    function makeWhereAlias()
    {
        return 'flt_' . $this->getAlias();
    }

    function extractValue()
    {
        $value = $this->C->getFilterValue($this->getAlias());
        if (!isset($value)
            && $this->globalStoreName
            && isset($_SESSION['listGlobalFilters'][$this->globalStoreName])) {
            $value = $_SESSION['listGlobalFilters'][$this->globalStoreName];
        }
        $this->setValue($value);
        // If GlobalStoreName is definded save value to session
        if ($this->globalStoreName) {
            $_SESSION['listGlobalFilters'][$this->globalStoreName] = $this->value;
        }
    }

    function setValue($val)
    {
        if (is_numeric($val)) {
            $this->value = intval($val);
        }
        if (!$val) {
            return false;
        }
        $this->value = (string)$val;
    }

    function handleValue()
    {

    }

    function getValue()
    {
        return $this->value;
    }

    function setCaptionInside($val)
    {
        $this->captionInside = (bool)$val;
    }

    function setEcfg($val)
    {
        if (!is_array($val) || !count($val)) {
            return false;
        }
        $this->ecfg = array_replace_recursive($this->ecfg, $val);
        return $this->ecfg;
    }

    function getEcfg()
    {
        return $this->ecfg;
    }
}
