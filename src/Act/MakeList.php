<?php

namespace Verba\Act;

use Verba\Configurable;
use function Verba\_oh;
use function Verba\User;

/**
 * Class MakeList
 *
 * Генерация списка
 *
 * @package Act
 */

class MakeList extends Action
{
    /**
     * Events
     * ->fire(<event>);
     *
     * cfgOrderBefore
     * cfgOrderAfter
     *
     */
    public $OT = false;

    public $row;
    /**
     * @var \Verba\Model\Item
     */
    public $rowItem;
    public $rowClass;
    public $rowCfg;
    public $rowAttrs;
    public $rowExtended = array();
    public $fieldResult;
    public $fieldCode;
    public $fieldClass;
    public $fieldCfg;

    // headers to parse
    public $headersToParse = array();
    // headers row
    public $headersCfg;
    // header cell
    public $headerCfg;
    public $headerFieldCfg;
    public $headerCode;
    public $headerText;

    protected $fieldTemplates = array();

    protected $defaultFieldCfg = array();

    protected $_rowsArray = null;
    protected $_currentRowNumber = 0;

    protected $slId;
    protected $nowrap = false;
    protected $parentsConditions = array();
    public $attrs_to_handle;
    public $attrs_to_blank;
    /**
     * @var \Selection
     */
    public $Selection;
    public $title = '';

    protected $strictOt = false;
    public $listId = false;
    protected $currentPos = 0;
    public $wrapId = false;
    public $rootOt = false;
    public $rootRuleAlias = false;

    protected $_prepared = array(
        'fields' => array(),
        'row' => array(
            'handler' => false,
        ),
    );

    public $fieldsToShow = array();
    public $fieldsToHide = array();
    protected $fieldsToParse = array();
    public $virtualFields = array();

    public $control_block_parsed = false;
    private $zerro_result_tpl;

    /**
     * @var \Verba\Act\MakeList\Filter\Controller
     */
    protected $Filters;

    // параметры для хнедлеров
    protected $_handlers_path_base = '';
    protected $_handlers_class_prefix = '\Verba\Act\MakeList\Handler\Field';

    protected $rq = array();
    protected $config = array();
    static $_config_default;
    protected $jsCfg = array(
        'id' => null,
        'otId' => null,
        'keyId' => null,
        'slId' => null,
        'page' => 1,
        'url' => false,
        'selectedOther' => false,
        'workers' => array(),
    );
    /**
     * @var \DBDriver\Result
     */
    public $sqlr = null;
    protected $client_templates = array();
    protected $_cachedHtml = array();
    protected $_wrapClasses = array();

    /**
     * @var array Где ключ - id ключа доступа, а значение массив вида array('key' => keyId, 'ownerId' => $ownerId)
     * На основе ключа создастся именованное WHERE `ownerField` = 'ownerId'
     *
     */
    protected $validOwner = array();

    public const CSS_PRIORITY = 700;

    function __call($mth, $args)
    {

        $action = strtolower(substr($mth, 0, 2));
        $propertie = lcfirst(substr($mth, 3));

        if (is_string($propertie) && property_exists($this, $propertie)) {
            switch ($action) {
                case 'set'  :
                    $this->$propertie = isset($args[0]) ? (bool)$args[0] : null;
                    return $this->$propertie;
            }
        }

        throw new \Exception('Call undefined method - ' . __CLASS__ . '::' . $mth . '()');
    }

    function __construct($cfg)
    {
        $this->initConfigurator(SYS_CONFIGS_DIR.'/list', 'list', 'config');
        $this->config = self::$_config_default;

        $this->_workersJsScriptsUrlBase = SYS_JS_URL . '/engine/act/makelist/workers';
        $this->_workersJsScriptsDirBase = SYS_JS_DIR . '/engine/act/makelist/workers';

        # apply cfg
        $this->initAndParseCfg($cfg);

        $this->tpl = \Verba\Hive::initTpl();

        if (!$this->ot_id) {
            throw new \Exception('OType is empty');
        }

        $this->oh = _oh($this->ot_id);
        $this->OT = $this->oh->getOT();

        if (!$this->keyId) {
            $this->setKeyId($this->oh->getBaseKey());
        }
        $editUrl = $this->gC('url edit');
        if (!$editUrl) {
            $this->setDefaultEditUrl();
        }

        if (!$this->listId) {
            if (isset($_REQUEST['slID'])) {
                $listId = $_REQUEST['slID'];
            } else {
                $listId = $this->makeListId();
            }
            $this->setListId($listId);
        }
        $this->restoreFromSession();
        if (isset($_REQUEST[$this->listId]) && is_array($_REQUEST[$this->listId])) {
            $this->rq = $_REQUEST[$this->listId];
        }

        $this->Selection = \Verba\init_selection($this->ot_id, $this->keyId, $this->listId);
        $this->slId = $this->Selection->getID();

        if (!$this->wrapId) {
            $this->setWrapId($this->makeWrapId());
        }

        if (!is_object($this->Selection) || !$this->Selection->formated()) {
            throw new \Exception('Selection intialization failed' . __METHOD__ . " (" . __LINE__ . ")]");
        }

        $this->Filters = new MakeList\Filter\Controller($this);
    }

    function getNowrap()
    {
        return (bool)$this->nowrap;
    }

    static public function make_action_sign($action = false, $iid = false)
    {
        $action = strtolower($action);
        switch ($action) {
            case 'list':
            case '':
                $r = 'list';
                break;
            default:
                $r = false;
        }

        return $r;
    }

    function getRequest($key = false)
    {
        return is_string($key) ? (isset($this->rq[$key]) ? $this->rq[$key] : null) : $this->rq;
    }

    function rq($key = false)
    {
        return $this->getRequest($key);
    }

    function save2session()
    {
        $this->Selection->save2session();
        $cfg = $this->gC();
        $_SESSION['list'][$this->listId] = array(
            'cfg' => array(
                'options' => array('state' => $cfg['options']['state'])
            ),
            'data' => array(//'extendedData' => $this->extendedData
            )
        );
    }

    function restoreFromSession()
    {
        if (!isset($_SESSION['list'][$this->listId])) {
            return false;
        }
        if (!isset($_SESSION['list'][$this->listId]['data'])
            || !is_array($_SESSION['list'][$this->listId]['data'])) {
            return false;
        }
        if (array_key_exists('extendedData', $_SESSION['list'][$this->listId]['data'])
            && is_array($_SESSION['list'][$this->listId]['data']['extendedData'])
            && count($_SESSION['list'][$this->listId]['data']['extendedData'])
        ) {
            $this->addExtData($_SESSION['list'][$this->listId]['data']['extendedData']);
        }
    }

    function applySessionCfg()
    {
        if (!isset($_SESSION['list'][$this->slId])) {
            return false;
        }
        $this->applyConfigDirect($_SESSION['list'][$this->slId]['cfg']);
    }

    function getOtId()
    {
        return $this->ot_id;
    }

    function isFeatAvaible($key)
    {
        return $this->isFeat($key);
    }

    protected function isFeat($key, $format = null)
    {

        if (!array_key_exists($key, $this->config['feats'])) {
            return null;
        }

        if (is_numeric($format)) {
            settype($format, 'integer');
        }

        if (!is_bool($format)
            && !is_int($format)
            && $format !== null) {
            $format = null;
        }

        if (is_int($format)) {
            return (bool)($this->config['feats'][$key] & $format);
        }

        return (bool)$this->config['feats'][$key];
    }

    function isFeatAvaibleTop($key)
    {
        return $this->isFeat($key, 1);
    }

    function isFeatAvaibleBottom($key)
    {
        return $this->isFeat($key, 2);
    }

    function refreshFeats()
    {
        if ($this->Selection->count_v < 5) {
            $this->config['feats']['ronpSelector'] = 0;
            $this->config['feats']['currentRange'] = 0;
        }

        if ($this->config['feats']['options'] & 2
            && $this->Selection->count_v < (int)$this->gC('options bottom max_rows_condition')) {
            $this->config['feats']['options'] = $this->config['feats']['options'] ^ 2;
        }
    }

    function isAddNew()
    {
        return (bool)$this->config['feats']['addnew'];
    }

    function isRowControls()
    {
        return (bool)$this->config['feats']['rowControls'];
    }

    function isRowControlsCheckbox()
    {
        return (bool)$this->config['feats']['rowControlsCheckbox'];
    }

    function isSelectAll()
    {
        return (bool)$this->config['feats']['selectAll'];
    }

    function isHeaders()
    {
        return (bool)$this->config['feats']['headers'];
    }

    function isJumpToPage()
    {
        return (bool)$this->config['feats']['jumpToPage'];
    }

    function isRowNumbering()
    {
        return (bool)$this->config['feats']['rowNumbering'];
    }

    function isListTitle()
    {
        return (bool)$this->config['feats']['listTitle'];
    }

    function isEditable()
    {
        return (bool)$this->config['feats']['editable'];
    }

    function defaultTpls()
    {
        $cfg = $this->gC();
        $this->tpl->clear_tpl(array('searchResult_tbl', 'searchResult_body', 'list_wrap'));
        $this->tpl->define(array(
            'searchResult_body' => $cfg['body'],
            'searchResult_tbl' => $cfg['table']['tpl'],
            'list_wrap' => $cfg['wrap'],
            'client_templates' => 'list/default/client_templates.tpl'
        ));

        $this->addCSS(array('list list-fields list-filters'), self::CSS_PRIORITY);


        if (!empty($cfg['layout'])) {
            $this->_wrapClasses[] = 'layout-' . $cfg['layout'];
            $this->addCSS(array('list-layout-' . $cfg['layout']), self::CSS_PRIORITY);
        }

        if (!empty($cfg['class'])) {
            $this->_wrapClasses[] = $cfg['class'];
        };

        $this->tpl->assign(array(
            'EXTENDED_HIDDEN_ELEMENTS' => '',
            'SEARCH_FOWARD_URL' => $this->getForwardUrl(),
            'LIST_ID' => $this->listId,
            'LIST_WRAP_ID' => $this->wrapId,
            'SLID' => $this->Selection->getID(),
            'OBJECT_OT' => $this->ot_id,
            'LIST_CONFIGS_STR' => str_replace('/', '|', implode(' ', $this->_confAppliedNames)),
            'CLIENT_TEMPLATES' => '',
        ));

        if ($this->isRowControls()) {
            $this->tpl->define(array(
                'row_control_block' => $cfg['control_block']['tpl'],
                'edit_element' => $cfg['control_block']['edit_element'],
                'select_element' => $cfg['control_block']['select_element'],
            ));
        }

        $this->addHidden('slID', $this->Selection->getID());
        $this->addHidden(session_name(), session_id());
        $this->addScripts(
            ['list-tools', 'engine/act/makelist'],
            ['workers', 'engine/act/makelist'],
            200
        );
    }

    function setReloadMethod($val)
    {
        if (!is_string($val) || !strtolower($val) || !($val = trim($val)) || empty($val)
            || !in_array($val, array('ajax', 'page'))) {
            return false;
        }
        $this->sC($val, 'reloadMethod');
        return true;
    }

    function makeWrapId()
    {
        return 'list-' . $this->listId;
    }

    function setWrapId($val)
    {
        $this->wrapId = is_string($val) && !empty($val)
            ? preg_replace("/[^\w\-]/", '_', $val)
            : false;
        return $this->wrapId;
    }

    function getWrapId()
    {
        return $this->wrapId;
    }

    function makeListId()
    {
        return \Verba\Hive::make_random_string(6, 6, 'l');
    }

    function setListId($val)
    {
        $this->listId = is_string($val) && !empty($val)
            ? preg_replace("/[^\w]/", '_', $val)
            : false;
        $this->sC($this->listId, 'listId');
        return;
    }

    function getListId()
    {
        return $this->listId;
    }

    function getID()
    {
        return $this->slId;
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

    function makeForwardUrl($url)
    {

        $url = new \Verba\Url($url);
        $get_params = $url->stringToParams($_SERVER['QUERY_STRING']);
        $url->setParams($get_params);
        // Add slID sign to forward URL
        $url->setParams(array('slID' => $this->Selection->getID()));

        $need2remove = array();
        // Delete Order sign from exist URL if present
        if (is_array($this->Selection->getted_vars_ref['o'])) {
            foreach ($this->Selection->getted_vars_ref['o'] as $okey => $ovalue) {
                $need2remove[] = $this->Selection->url_var . '[o][' . $okey . ']';
            }
        }

        // Delete Ronp sign from exist URL if present
        if (isset($this->Selection->getted_vars_ref['ronp'])) {
            $need2remove[] = $this->Selection->url_var . '[ronp]';
        }

        $url->removeParams($need2remove);

        return $url->get();
    }

    // Edit URL
    function setDefaultEditUrl()
    {
        $this->setEditUrl('/' . $this->oh->getCode() . '/edit', 'url edit');
    }

    function setEditUrl($url)
    {
        if (!is_string($url) || empty($url)) {
            return false;
        }
        $this->sC(rtrim($url, '/'), 'url edit');
        return true;
    }

    function getEditUrl()
    {
        return $this->gC('url edit');
    }

    function setEditIdOver($method)
    {
        if (!is_string($method) || empty($method)) {
            return false;
        }
        $this->editIdOver = strtolower($method);
        return true;
    }

    function getEditIdOver()
    {
        return $this->editIdOver;
    }

    // Delete Action Path
    function setDeleteUrl($val)
    {
        if (!is_string($val) || empty($val)) {
            return false;
        }
        $this->sC($val, 'url delete');
    }

    function getDeleteUrl()
    {
        return $this->gC('url delete');
    }

    function makeDeleteUrl()
    {
        return !is_string($this->gC('url delete'))
            ? $this->getForwardURL()
            : $this->gC('url delete');
    }

    function setNewUrl($val)
    {
        if (!is_string($val) || empty($val)) {
            return false;
        }
        $this->sC($val, 'url new');
    }

    function getNewUrl()
    {
        return $this->gC('url new');
    }

    function makeAddNewUrl()
    {
        $url = !is_string($url = $this->getNewUrl())
            ? $this->getForwardURL()
            : $url;

        if (count($this->parents)) {
            list($pot, $piid) = $this->getFirstParent();
            $url = \Verba\var2url($url, array('pot' => $pot, 'piid' => $piid));
        }
        return $url;
    }

    //Fields to view (select)
    function addAttr($attrs_list, $type = 'n')
    {
        // type = char{1} n - normal
        //                h - hidden
        //                v - virtual
        switch ($type) {
            case 'v':
                $a = &$this->virtualFields;
                break;
            case 'h':
                $a = &$this->fieldsToHide;
                break;
            case 'n':
            default:
                $a = &$this->fieldsToShow;
                break;
        }

        if ($type != 'v') {
            $attrs_list = $this->QM()->addAttr($attrs_list);
        }

        if (!\Verba\reductionToArray($attrs_list)) {
            return false;
        }

        foreach ($attrs_list as $key => $value) {
            $a[$key] = $value;
        }

        return;
    }

    function addHiddenAttr($attrs_list)
    {
        return $this->addAttr($attrs_list, 'h');
    }

    function addVirtualAttr($attrs_list)
    {
        return $this->addAttr($attrs_list, 'v');
    }

    function addDefaultOrder($attrs, $vault = false)
    {
        if ($this->Selection->isDefaultOrderApplied()) {
            return null;
        }
        $this->Selection->addDefaultOrder($attrs, $vault);
    }

    function handleDefaultOrderFromConfig()
    {
        $cfg = $this->gC('order default');
        if (!is_array($cfg) || !count($cfg) || $this->Selection->isDefaultOrderApplied()) {
            return;
        }
        if (gettype(key($cfg)) == 'string') {
            $cfg = array(array($cfg, false));
        }
        foreach ($cfg as $orderAndVault) {
            if (!isset($orderAndVault[0])) {
                continue;
            }
            $this->addDefaultOrder($orderAndVault[0], isset($orderAndVault[1]) ? $orderAndVault[1] : null);
        }
    }

    /**
     * @return object \Selection
     */
    function Selection()
    {
        return $this->Selection;
    }

    function getCurrentPos()
    {
        return $this->currentPos;
    }

    /**
     * Возвращает ссылку на экземпляр класса QueryMaker для текущего списка
     * @return \Verba\QueryMaker
     * @see \Verba\QueryMaker
     */
    function QM()
    {
        return $this->Selection->QM();
    }

    function setFields($fields)
    {
        if (!is_array($fields)) {
            $fields = array();
        } else {
            $fields = Configurable::substNumIdxAsStringValues($fields);
        }

        foreach ($fields as $f_code => $f_cfg) {
            if ($f_cfg === false) {
                if (isset($this->config['fields'][$f_code])) {
                    unset($this->config['fields'][$f_code]);
                }
                continue;
            }

            if (isset($this->config['fields'][$f_code])) {
                $this->config['fields'][$f_code] = array_replace_recursive(
                    $this->config['fields'][$f_code], $f_cfg
                );
            } else {
                $this->config['fields'][$f_code] = $f_cfg;
            }
        }
    }

    function addAttrsFromConfig()
    {
        $c = $this->gC('fields');
        if (!is_array($c)) {
            $c = array();
        } else {
            $c = Configurable::substNumIdxAsStringValues($c);
        }
        if ($this->gC('only_config_fields') == false) {
            $attrs = $this->oh->getAttrs(true);
            $attrs = Configurable::substNumIdxAsStringValues($attrs);
            $attrs = array_replace_recursive($attrs, $c);
        } else {
            $attrs = $c;
        }

        $virtual =
        $hidden =
        $toRemove =
        $normal = array();
        foreach ($attrs as $key => $field_conf) {
            if (is_numeric($key) && is_string($field_conf)) {
                $key = $field_conf;
                $field_conf = array();
            }
            if ($field_conf === false) {
                $toRemove[$key] = $key;
                continue;
            }

            if (array_key_exists($key, $toRemove)) {
                unset($toRemove[$key]);
            }

            if (!is_array($field_conf)) {
                $field_conf = array('type' => 'normal');
            }
            if (!array_key_exists('type', $field_conf)) {
                $field_conf['type'] = 'normal';
            }
            switch ($field_conf['type']) {
                case 'virtual':
                    $virtual[$key] = $key;
                    break;
                case 'hidden':
                    $hidden[$key] = $key;
                    break;
                case 'normal':
                default:
                    $normal[$key] = $key;
                    break;
            }
        }

        if (count($toRemove)) {
            foreach ($toRemove as $rkey) {
                unset($virtual[$rkey], $hidden[$key], $normal[$key]);
            }
        }

        if (count($virtual)) {
            $this->addVirtualAttr($virtual);
        }
        if (count($hidden)) {
            $this->addHiddenAttr($hidden);
        }
        if (count($normal)) {
            $this->addAttr($normal);
        }
    }

    function parseRowControls($row)
    {
        $cfg = $this->gC('control_block');
        $iid = $row[$this->oh->getPAC()];

        $class = 'list-item-controls';
        if (is_string($cfg['class']) && !empty($cfg['class'])) {
            $class .= ' ' . $cfg['class'];
        }
        $this->tpl->assign(array(
            'ADMFUNC_ENTRY_ED' => '',
            'SELECT_CHECKBOX' => '',
            'CELL_CLASS_ATTR' => ' class="' . $class . '"',
        ));

        $U = User();

        $editUrlPrefix = $this->gC('url edit');
        //Edit Element
        if ($this->isEditable()
            && $U->chrItem($row['key_id'], 'u', $row)
            && is_string($editUrlPrefix) && !empty($editUrlPrefix)) {
            if ($this->editIdOver == 'get') {
                $editurl = \Verba\var2url($editUrlPrefix, 'iid=' . $iid);
            } else {
                $editurl = $editUrlPrefix . '/' . $iid;
            }
            $this->tpl->assign('ADMFUNC_ENTRY_EDIT_URL', $editurl);
            if ($cfg['edit_element']) {
                $this->tpl->parse('ADMFUNC_ENTRY_ED', 'edit_element');
            }
        }

        if ($this->isRowControlsCheckbox()) {

            $this->tpl->assign(array(
                'CHECKBOX_SELECTED' => $this->Selection->isSelected($iid, $row['ot_id']) ? 'checked' : '',
            ));
            $this->tpl->parse('SELECT_CHECKBOX', 'select_element');
        }

        $this->control_block_parsed = true;
        return $this->tpl->parse(false, 'row_control_block');
    }

    function rowControlsAsJson($row)
    {
        $html_class = 'list-item-controls' . (
            is_string($this->_c['control_block']['class']) && !empty($this->_c['control_block']['class'])
            ? ' ' . $this->_c['control_block']['class']
            : '');

        $r = [
            'rights' => [
                null,
                null,
                (int)($this->isEditable() && User()->chrItem($row['key_id'], 'u', $row)),
                (int)User()->chrItem($row['key_id'], 'd', $row)
            ],
            'sequence_pos' => $this->currentPos,
            'selected' => $this->Selection->isSelected($row[$this->oh->getPAC()], $row['ot_id']),
            'html' => [
                'class' => $html_class
            ]
        ];

        return $r;
    }

    function setRowsOnPage($val)
    {
        $this->sC($val, 'navout', 'ronp', 'default');
    }

    function order_link_HTML($attr_code, $displayName)
    {
        $A = $this->oh->A($attr_code);
        if ($A) {
            $attrOrderCode = $A->getID();

            $sortAs = $A->getDataType() == 'string' ? 'string' : 'number';

        } else {

            $attrOrderCode = $attr_code;
            $sortAs = 'number';

        }
        $hdrs = &$this->{$this->_confPropName}['headers'];
        $attrUrlOrderName = $this->Selection->getUrlVar() . '[o][' . $attrOrderCode . ']';
        $url = $this->getForwardUrl();
        $class = 'list-button-order';

        if ($this->QM()->fieldInOrder($attr_code)) {

            if (!empty($hdrs['ordered_class'])) {
                $class .= ' ' . $hdrs['ordered_class'];
            }

            $flag2name = '!';
            $flag2arrow = $this->QM()->getFieldOrderType($attr_code) == 'a' ? 'd' : 'a';
            $img_title = 'change: ';
            switch ($flag2arrow) {
                case 'a' :
                    $img_suffix = 'des';
                    break;
                case 'd'  :
                default    :
                    $img_suffix = 'asc';
                    break;
            }
            if ($sortAs == 'string') {
                $switcherClassSign = 'string';
                $img_title .= $img_suffix == 'asc' ? 'Z-&gt; A' : 'A -&gt; Z';
            } else {
                $switcherClassSign = 'number';
                $img_title .= $img_suffix == 'asc' ? '10 -&gt; 0' : '0 -&gt; 10';
            }
            $order2arrow = '<a class="list-button-order-switcher ' . $switcherClassSign . '" href="' . \Verba\var2url($url, array($attrUrlOrderName => $flag2arrow)) . '"><img src="/images/acp/order_' . $img_suffix . '.gif" border="0" alt="' . $img_title . '"></a>';
            $href_title = \Verba\Lang::get('list order on');
        } else {
            $flag2name = 'a';
            $order2arrow = '';
            $href_title = \Verba\Lang::get('list order off');
        }

        $field_href = '<a href="' . \Verba\var2url($url, array($attrUrlOrderName => $flag2name)) . '" class="' . $class . '" title="' . $href_title . '"><i>' . $displayName . '</i></a>';
        if (!empty($order2arrow)) {
            $field_href = '<div class="orderable-wrap">'.$field_href.'&nbsp;' . $order2arrow.'</div>';
        }

        return $field_href;
    }

    function generateHeaderText($code)
    {
        $name = '--';
        $hdrs = &$this->{$this->_confPropName}['headers'];
        if (isset($hdrs['fields'][$code]['title'])) {
            $name = is_array($hdrs['fields'][$code]['title']) && isset($hdrs['fields'][$code]['title'][SYS_LOCALE])
                ? $hdrs['fields'][$code]['title'][SYS_LOCALE]
                : $hdrs['fields'][$code]['title'];
        } elseif ($A = $this->oh->A($code)) {
            $name = $A->display();
        }

        return $name;
    }

    // Title
    function setTitleValue($title)
    {
        $this->sC((string)$title, 'title value');
    }

    function parseListTitle()
    {
        $this->tpl->clear_tpl('list_title');
        $cfg = $this->gC('title');
        $this->tpl->define('list_title', $cfg['tpl']);
        if (!$cfg['value'] && !(bool)$cfg['leaveEmptyTag']) {
            return '';
        }
        $this->tpl->assign(array('LIST_TITLE' => $cfg['value'],
            'LT_CLASS_ATTR' => is_string($cfg['class']) && !empty($cfg['class']) ? ' class="' . $cfg['class'] . '"' : '',
        ));
        return $this->tpl->parse(false, 'list_title');
    }

    function handleSelectionSets()
    {
        // ronp - rows on page
        if (is_array($slCfg = $this->Selection->getRequest()) && isset($slCfg['ronp'])) {
            $this->Selection->setRonp($slCfg['ronp']);
        } elseif (!is_numeric($this->Selection->getRonp())) {
            $this->Selection->setRonp($this->gC('navout ronp default'));
        }
        //nowrap
        if (isset($this->rq['nowrap'])) {
            $this->nowrap = (bool)$this->rq['nowrap'];
        }
    }

    // RootOt, RuleAlias
    function setRootOt($val)
    {
        $this->rootOt = _oh($val)->getID();
    }

    function getRootOt()
    {
        return $this->rootOt;
    }

    function setRootRuleAlias($val)
    {
        $this->rootRuleAlias = (string)$val;
    }

    function getRootRuleAlias()
    {
        return $this->rootRuleAlias;
    }

    function getNumRows()
    {
        return $this->Selection->count_v;
    }

    function getRows()
    {
        if ($this->_rowsArray === null) {
            $this->_rowsArray = array();
            if ($this->sqlr && $this->sqlr->getNumRows()) {
                while ($row = $this->sqlr->fetchRow()) {
                    $this->_rowsArray[$row[$this->oh->getPAC()]] = $row;
                }
            }
        }

        return $this->_rowsArray;
    }

    protected function extractHandlerFromCfg($handlerCfg, $type = false, $extra_cfg = false)
    {
        $attr_code = $extra_cfg['attr_code'];

        if (is_string($handlerCfg)) {

            list($className, $cfg) = \Verba\Hive::stringToHandlerParts($handlerCfg);
            if (!$className) {
                return false;
            }
            goto HANDLER_KNOWN;

        } elseif (!is_array($handlerCfg) || !count($handlerCfg)
            || !isset($handlerCfg[0]) || !is_string($handlerCfg[0])) {
            return false;
        }

        $cfg = isset($handlerCfg[1]) && is_array($handlerCfg[1])
            ? $handlerCfg[1]
            : array();

        // possible mod
        $mod = isset($handlerCfg[2])
            ? $handlerCfg[2]
            : false;

        $className = $handlerCfg[0];


        HANDLER_KNOWN:
        if (!is_array($cfg)) {
            $cfg = array();
        }
        if (is_array($extra_cfg)) {
            $cfg = array_replace_recursive($cfg, $extra_cfg);
        }

        if (!$mod && strpos($className, '\\') === false) {
            $className = '\Verba\Act\MakeList\Handler\\' . ucfirst($type) . '\\' . $className;
        }

        // если класс сушествует
        if (class_exists($className, true))
        {
            switch ($type) {
                case 'row':
                    $handler = new $className($this, $cfg);
                    break;
                case 'field':
                default:
                    $handler = new $className($this->oh, $attr_code, $cfg, $this);
            }

            if (!$handler instanceof \Verba\Act\MakeList\Handler\HandlerInterface) {
                $this->log()->error('List Handler ' . $type . ' wrong class ' . var_export($className, true), false);
                return false;
            }

        } else {
            $this->log()->error('Unknown List ' . $type . ' handler ' . var_export($className, true), false);
            return false;
        }

        return $handler;
    }

    function extractFieldHandlerFromCfg($handlerCfg, $cfg = false)
    {
        return $this->extractHandlerFromCfg($handlerCfg, 'field', $cfg);
    }

    function extractRowHandlerFromCfg($handlerCfg)
    {
        return $this->extractHandlerFromCfg($handlerCfg, 'row');
    }

    function setStrictOt($val)
    {
        $this->strictOt = (bool)$val;
    }

    // Workers
    function getDefaultWorkerPath($workerClassName = null)
    {
        $path = __CLASS__ . '\\Worker';
        return is_string($workerClassName)
            ? $path . '\\' . $workerClassName
            : $path;
    }

    # !!!!!! #
    # Action #
    # !!!!!! #
    function generateList($parseHtml = true)
    {
        $this->fire('beforeStart');
        $this->prepareAndRunQueries();

        return $parseHtml ? $this->parseHtml() : true;
    }

    function generateListJson()
    {
        $this->fire('beforeStart');
        $this->prepareAndRunQueries();

        return $this->asJson();
    }

    function prepareAndRunQueries()
    {
        if (!$this->validateAccess()) {
            $err = 'Access to List is denied';
            $this->log()->error($err);
            throw new \Exception($err);
        }
        // добвляем условия по владельцу если доступ к списку получен только по праву "для владельцев"
        $this->addValidOwnerWhereCondition();


        //
        $this->applySessionCfg();

        if (is_array($this->_c['order']['subst']) && count($this->_c['order']['subst'])) {
            $this->QM()->setOrderSubst($this->_c['order']['subst']);
        }
        // handle defaultOrder from Config
        $this->fire('cfgOrderBefore');
        $this->handleDefaultOrderFromConfig();
        $this->fire('cfgOrderAfter');

        $this->addAttrsFromConfig();
        $this->handleSelectionSets();

        $this->Filters->addFiltersFromCfg();
        $this->Filters->apply();

        // Refresh Selection sets
        $this->Selection->refresh_sets();
        $this->currentPos = $this->Selection->start_row + 1;

        // parents condition

        if (count($this->parents)) {
            $parentQMCondAliasBase = 'listParent';
            foreach ($this->parents as $cpot => $cpiids) {
                $_cpoh = _oh($cpot);
                $parentQMCondAlias = $parentQMCondAliasBase . '_' . $_cpoh->getCode();
                if ($this->QM()->isConditionExists($parentQMCondAlias)) {
                    continue;
                }

                $parentCond = $this->QM()->addConditionByLinkedOTRight($_cpoh->getID(), $cpiids, $parentQMCondAlias);
                $parentCond->setDescendantsPrimary(true);

                // !!! Необходимо переделать под множ. родителей
                if ($this->parentsRelation) {
                    $parentCond->setRelation($this->parentsRelation);
                }

                if ($this->rootOt) {
                    $_rootOh = _oh($this->rootOt);
                    if ($_rootOh->getID() == $_cpoh->getID()) {
                        $parentCond->setRootOt($this->rootOt);
                        if ($this->rootRuleAlias) {
                            $parentCond->setRuleAlias($this->rootRuleAlias);
                        }
                    }
                }
            }
        }

        // Strict OT if set to true - add ot condition to where
        if ($this->strictOt) {
            $this->QM()->addWhere($this->oh->getID(), 'strict_ot_id', 'ot_id');
        } else {
            $this->QM()->removeWhere('strict_ot_id');
        }

        // Make main query

        $this->fire('beforeQuery');
        $this->Selection->refresh_querys();
        $this->sqlr = $this->Selection->exec_query();
        $this->fire('queryExecuted');
        $this->getRows();
        $this->sqlr->free();
        $this->fire('sqlrConvertedToArray');

        return $this->_rowsArray;
    }

    function asJson()
    {
        $this->fire('beforeParse');
        $this->setForwardUrl(
            $this->makeForwardUrl(
                !is_string($this->getForwardUrl())
                    ? $_SERVER['REQUEST_URI']
                    : $this->getForwardUrl()
            )
        );

        $r = [
            'items' => null,
        ];

        //Filters
        $this->fire('beforeFilters');
        $r['filters'] = $this->Filters->asJson();
        $this->fire('afterFilters');

        //Feats
        $this->refreshFeats();
        $r['feats'] = $this->config['feats'];

        if ($this->_rowsArray && $this->Selection->c_founded_rows > 0) {
            $this->prepareToParse();
            $r['items'] = $this->parseRowsAsJson();
        }

        // Options blocks
        $r['panels'] = $this->getOptionsPannelsAsJson();

        // Pager Panels
        $r['selection'] = $this->getSelectionParams();

        // Headlines (table view - column headers, free view - order fields row)
        $r['headers'] = $this->isHeaders() ? $this->getHeadersAsJson() : null;

        //List Title
        $r['title'] = $this->isListTitle() ? $this->parseListTitle() : '';

        //External CSS
//        if ($cfg['css']) {
//            $this->addCSS($cfg['css']);
//        }
//        //External Scripts
//        if ($cfg['scripts']) {
//            $this->addScripts($cfg['scripts']);
//        }

        // Add Client List Workers and initialization code
        //$this->addWorkersToJs();

        //парсинг предидущего JavaScript-кода
        //$this->prepareAndParseJsBefore();

        //парсинг последующего JavaScript-кода
        //$this->prepareAndParseJsAfter();

        //$this->mergeHtmlIncludesWithTiedBlock();

        // парсинг хидденов
        //$this->parseToHiddens();

        // клиентские шаблоны
        //$this->parseClientTemplates();

        //Save state to session
        $this->save2session();

//        $this->tpl->parse('LIST_WRAP_CONTENT', 'searchResult_body');
//        if ($this->nowrap) {
//            return $this->tpl->getVar('LIST_WRAP_CONTENT');
//        }

//        $this->tpl->assign(array(
//            'LIST_WRAP_CLASS' => count($this->_wrapClasses) ? implode(' ', $this->_wrapClasses) : '',
//        ));

        return $r;
    }

    function parseHtml()
    {
        $this->fire('beforeParse');
        $cfg = $this->gC();

        $this->setForwardUrl(
            $this->makeForwardUrl(
                !is_string($this->getForwardUrl())
                    ? $_SERVER['REQUEST_URI']
                    : $this->getForwardUrl()
            )
        );

        $this->defaultTpls();

        //Filters
        $this->fire('beforeFilters');
        $this->tpl->assign('LIST_FILTERS', $this->Filters->parse());
        $this->fire('afterFilters');

        $this->refreshFeats();

        if (!$this->_rowsArray
            || $this->Selection->c_founded_rows < 1) {
            $this->tpl->assign('ROWS', $this->zerro_result());
        } else {
            $this->prepareToParse();
            $this->tpl->assign('ROWS', $this->parseRows());
        }

        // Options blocks
        $this->parseOptionsPannels();

        // Pager Panels
        $this->parsePagerPanels();

        // Headlines (table view - column headers, free view - order fields row)
        $this->tpl->assign('SEARCH_RESULT_HEADERS', $this->isHeaders() ? $this->parseHeaders() : '');

        $this->tpl->parse('LIST_AND_HEADERS', 'searchResult_tbl');

        //List Title
        $this->tpl->assign('SR_TITLE', $this->isListTitle() ? $this->parseListTitle() : '');

        //External CSS
        if ($cfg['css']) {
            $this->addCSS($cfg['css']);
        }
        //External Scripts
        if ($cfg['scripts']) {
            $this->addScripts($cfg['scripts']);
        }

        // Add Client List Workers and initialization code
        $this->addWorkersToJs();

        //парсинг предидущего JavaScript-кода
        $this->prepareAndParseJsBefore();

        //парсинг последующего JavaScript-кода
        $this->prepareAndParseJsAfter();

        $this->mergeHtmlIncludesWithTiedBlock();

        // парсинг хидденов
        $this->parseToHiddens();

        // клиентские шаблоны
        $this->parseClientTemplates();

        //Save state to session
        $this->save2session();

        $this->tpl->parse('LIST_WRAP_CONTENT', 'searchResult_body');
        if ($this->nowrap) {
            return $this->tpl->getVar('LIST_WRAP_CONTENT');
        }

        $this->tpl->assign(array(
            'LIST_WRAP_CLASS' => count($this->_wrapClasses) ? implode(' ', $this->_wrapClasses) : '',
        ));

        return $this->tpl->parse(false, 'list_wrap');

    }

    //  function addCss(){
    //    $args = func_get_args();
    //    call_user_func_array('parent::addCss', $args);
    //  }

    function prepareToParse()
    {
        $cfg = $this->gC();

        $this->_prepared['row'] = $cfg['row'];
        $this->_prepared['row']['handler'] = false;
        if (is_array($cfg['row']['handler']) && count($cfg['row']['handler'])) {
            $this->_prepared['row']['handler'] = $this->extractRowHandlerFromCfg($cfg['row']['handler']);
        }

        $toDefine = array();

        $this->defaultFieldCfg = $cfg['field_default'];

        $this->defaultFieldCfg['tpl_hash'] = false;
        $this->defaultFieldCfg['content_tpl_hash'] = false;

        if (is_string($this->defaultFieldCfg['tpl']) && strlen($this->defaultFieldCfg['tpl'])) {
            $this->defaultFieldCfg['tpl_hash'] = 'field_tpl_' . md5($this->defaultFieldCfg['tpl']);
            $toDefine[$this->defaultFieldCfg['tpl_hash']] = $this->defaultFieldCfg['tpl'];
        }

        if (is_string($this->defaultFieldCfg['content_tpl']) && strlen($this->defaultFieldCfg['content_tpl'])) {
            $this->defaultFieldCfg['content_tpl_hash'] = 'field_content_tpl_' . md5($this->defaultFieldCfg['content_tpl']);
            $toDefine[$this->defaultFieldCfg['content_tpl_hash']] = $this->defaultFieldCfg['content_tpl'];
        }

        $this->fieldsToParse = array_merge($this->fieldsToShow, $this->virtualFields);
        $orderedFields = [];

        foreach ($this->fieldsToParse as $attr_code) {

            $this->_prepared['fields'][$attr_code] = is_array($cfg['fields'][$attr_code])
                ? array_replace_recursive($this->defaultFieldCfg, $cfg['fields'][$attr_code])
                : $this->defaultFieldCfg;

            //data-type
            if (empty($this->_prepared['fields'][$attr_code]['data-type'])) {
                $this->_prepared['fields'][$attr_code]['data-type'] = $this->oh->isA($attr_code)
                    ? $this->oh->A($attr_code)->data_type
                    : 'string';
            }

            // field's custom template
            if (isset($cfg['fields'][$attr_code]['tpl'])
                && is_string($cfg['fields'][$attr_code]['tpl']) && strlen(['tpl'])) {
                $this->_prepared['fields'][$attr_code]['tpl_hash'] = 'field_tpl_' . md5($cfg['fields'][$attr_code]['tpl']);
                $toDefine[$this->_prepared['fields'][$attr_code]['tpl_hash']] = $cfg['fields'][$attr_code]['tpl'];
            }

            if (is_string($cfg['fields'][$attr_code]['content_tpl']) && strlen($cfg['fields'][$attr_code]['content_tpl'])) {
                $this->_prepared['fields'][$attr_code]['content_tpl_hash'] = 'field_content_tpl_' . md5($cfg['fields'][$attr_code]['content_tpl']);
                $toDefine[$this->_prepared['fields'][$attr_code]['content_tpl_hash']] = $cfg['fields'][$attr_code]['content_tpl'];
            }

            //Field handlers
            $this->_prepared['fields'][$attr_code]['handlers'] = array();
            // generating value by default present handlers
            if ($this->oh->isA($attr_code) && !$cfg['fields'][$attr_code]['preventDefaultHandlers']) {
                $A = $this->oh->A($attr_code);
                $attr_code = $A->getCode();

                $aths = $A->getHandlers('present');
                if (!is_array($aths) || !count($aths)) {
                    $aths = array(0 => array('_autohandler' => true, 'ah_name' => $A->getHandlerByType()));
                }

                foreach ($aths as $set_id => $set_data) {
                    list($className, $handler_cfg) = $this->genClassAndCfgForFieldHandler($attr_code, $set_data);
                    if (!$className) {
                        continue;
                    }

//                    if (!is_a($className, '\Act\MakeList\Handler', true)){
//                        $Handler = MakeList\Handler\Adapter::create($className, $this, $handler_cfg);
//                    }else {
//                        $Handler = new $className($this, $handler_cfg);
//                    }

                    $Handler = new $className($this->oh, $A, $handler_cfg, $this);

                    $this->_prepared['fields'][$attr_code]['handlers'][] = $Handler;
                }
            }

            // custom field handler
            if (!empty($cfg['fields'][$attr_code]['handler'])) {
                $this->_prepared['fields'][$attr_code]['handlers'][]
                    = $this->extractFieldHandlerFromCfg($cfg['fields'][$attr_code]['handler'], array('attr_code' => $attr_code));
            }

            $orderedFields[$attr_code] = [
                'code' => $attr_code,
                'priority' => isset($this->_prepared['fields'][$attr_code]['priority'])
                    ? (int)$this->_prepared['fields'][$attr_code]['priority']
                    : 0
            ];
        }

        usort($orderedFields, '\Verba\sortByPriorityAsArrayDesc');

        $this->fieldsToParse = [];
        foreach($orderedFields as $fidx => $attr){
            $this->fieldsToParse[] = $attr['code'];
        }

        if (count($toDefine)) {
            $this->tpl->define($toDefine);
        }
    }

    ## Rows
    function parseRows()
    {
        global $S;
        $tpl = \Verba\Hive::initTpl();

        $cfg = $this->gC();

        $this->rowCfg = $this->_prepared['row'];

        if ($this->isRowControls()) {
            $this->tpl->define(array(
                'row_control_block' => $cfg['control_block']['tpl'],
                'entry_element' => $cfg['control_block']['entry_element'],
                'select_element' => $cfg['control_block']['select_element'],
                'numbering' => $this->rowCfg['num']
            ));
        }

        $this->tpl->define(array(
            'row' => $this->rowCfg['tpl']
        ));

        $all_rows = '';

        foreach ($this->_rowsArray as $this->row) {

            if (!$this->row['ot_id']) {
                $this->log()->error('Invalid list entry - bad OT');
                continue;
            }
            $_oh = _oh($this->row['ot_id']);
            if (!$this->row[$_oh->getPAC()]) {
                $this->log()->error('Invalid list entry ID, ot[' . $_oh->getCode() . ']');
                continue;
            }
            $this->rowItem = $_oh->initItem($this->row);

            $this->rowAttrs = array();

            $this->rowClass = array();

            $this->rowExtended = array();

            $this->fire('rowBefore');

            if (!is_numeric($this->row['key_id']) || !$S->U()->chrItem($this->row['key_id'], 's', $this->row)) {
                continue;
            }

            $this->_currentRowNumber++;

            $iid = $this->row[$this->oh->getPAC()];

            $tpl->clear_vars(array('ARM_ADMFUNC_ENTRY_ED', 'SELECT_CHECKBOX', 'CONTROL_BLOCK'));

            $this->rowClass[] = 'list-item list-itemid-' . $this->row['ot_id'] . '-' . $iid . ' list-item-page-pos-' . $this->_currentRowNumber . ' list-item-pos-' . $this->currentPos;


            if (!empty($this->rowCfg['class'])) {
                $this->rowClass[] = $this->rowCfg['class'];
            }

            $this->tpl->assign(array(
                'ENTRY_ID' => $this->row[$this->oh->getPAC()],
                'OBJECT_IID' => $this->row[$this->oh->getPAC()],
                'OBJECT_KEY_ID' => $this->row['key_id'],
                'ENTRY_SEQUENCE_POS' => $this->currentPos,
                'ROW_FIELDS' => '',
            ));

            if ($this->isRowNumbering()) {
                $this->tpl->assign(array(
                    'ENTRY_ANCHOR' => $this->oh->getID() . "." . $this->row[$this->oh->getPAC()]
                ));
                $this->tpl->parse('NUMBERING_CELL', 'numbering');
            } else {
                $this->tpl->assign('NUMBERING_CELL', '');
            }

            if (is_object($this->rowCfg['handler'])) {
                $this->rowCfg['handler']->run();
            }

            if (!$this->rowCfg['handler']
                || !$this->rowCfg['preventDefaultFieldParseIfHandler']
            ) {
                $this->tpl->assign(array(
                    'ROW_FIELDS' => $this->parseRowFields(),
                ));
            }
            $row_attrs_str = '';
            if (count($this->rowAttrs)) {
                foreach ($this->rowAttrs as $tag_attr_name => $tag_attr_value) {
                    $row_attrs_str .= ' ' . $tag_attr_name . '="' . htmlspecialchars($tag_attr_value) . '"';
                }
            }
            $this->tpl->assign(array(
                'ROW_CLASS_ATTR' => ' class="' . implode(' ', $this->rowClass) . '"',
                'ROW_ATTRS' => $row_attrs_str,
                'CONTROL_BLOCK' => $this->isRowControls() ? $this->parseRowControls($this->row) : '',
            ));

            $all_rows .= $this->tpl->parse(false, 'row');
            $this->currentPos++;

            $this->fire('rowAfter');
        }

        return $all_rows;
    }

    function parseRowsAsJson()
    {
        global $S;

        $this->rowCfg = $this->_prepared['row'];

        $this->tpl->define(array(
            'row' => $this->rowCfg['tpl']
        ));

        $all_rows = [];

        foreach ($this->_rowsArray as $this->row) {
            $this->rowAttrs = [];
            $this->rowClass = [];
            $this->rowExtended = [];

            if (!$this->row['ot_id']) {
                $this->log()->error('Invalid list entry - bad OT');
                continue;
            }
            $_oh = _oh($this->row['ot_id']);
            if (!$this->row[$_oh->getPAC()]) {
                $this->log()->error('Invalid list entry ID, ot[' . $_oh->getCode() . ']');
                continue;
            }
            $this->rowItem = $_oh->initItem($this->row);

            $this->fire('rowBefore');

            if (!is_numeric($this->row['key_id']) || !$S->U()->chrItem($this->row['key_id'], 's', $this->row)) {
                continue;
            }

            $this->_currentRowNumber++;

            $iid = $this->row[$this->oh->getPAC()];

            $this->rowClass[] = 'list-item list-itemid-' . $this->row['ot_id'] . '-' . $iid . ' list-item-page-pos-' . $this->_currentRowNumber . ' list-item-pos-' . $this->currentPos;

            if (!empty($this->rowCfg['class'])) {
                $this->rowClass[] = $this->rowCfg['class'];
            }

            if (!$this->rowCfg['handler'] || !$this->rowCfg['preventDefaultFieldParseIfHandler']) {
                $fields = $this->parseRowFieldsAsJson();
            }
            $html = [
                'class' => implode(' ', $this->rowClass),
                'attrs' => $this->rowAttrs
            ];

            $all_rows[] = [
                'fields' => $fields,
                'controls' => $this->rowControlsAsJson($this->row),
                'html' => $html,
            ];

            $this->currentPos++;

            $this->fire('rowAfter');
        }

        return $all_rows;
    }


    ## Fields

    function parseRowFields()
    {
        if (!is_array($this->fieldsToParse) || count($this->fieldsToParse) < 1 || !is_array($this->row)) {
            return '';
        }

        $fields_content = '';

        foreach ($this->fieldsToParse as $attr_code) {
            $this->fieldResult = false;
            $this->fieldCode = $attr_code;

            $this->fieldCfg = $this->_prepared['fields'][$attr_code];

            $this->fieldClass = 'list-field lf-datatype-' . $this->fieldCfg['data-type'] . ' lf-' . $attr_code;

            if (is_string($this->fieldCfg['class'])) {
                $this->fieldClass .= $this->fieldCfg['class_merge'] && !empty($this->defaultFieldCfg['class'])
                    ? $this->defaultFieldCfg['class'] . ' ' . $this->fieldCfg['class']
                    : $this->fieldCfg['class'];
            } elseif (isset($this->defaultFieldCfg['class']) && is_string($this->defaultFieldCfg['class'])) {
                $this->fieldClass .= $this->defaultFieldCfg['class'];
            }

            $this->fire('fieldBefore');

            $this->fieldResult = $this->row[$attr_code];

            // generating value by assigned handlers
            if (is_array($this->fieldCfg['handlers']) && count($this->fieldCfg['handlers'])) {
                foreach ($this->fieldCfg['handlers'] as $ch) {
                    if (!is_object($ch)) {
                        $this->log()->error('Bad field ' . $attr_code . '(' . $this->oh()->getCode() . ') handler ' . var_export($ch, true));
                        continue;
                    }
                    $this->fieldResult = $ch->run();
                }
            }

            $classAttr = is_string($this->fieldClass) && !empty($this->fieldClass)
                ? ' class="' . $this->fieldClass . '"'
                : '';
            if (isset($this->fieldCfg['attr'])
                && is_array($this->fieldCfg['attr'])
                && count($this->fieldCfg['attr'])) {
                foreach ($this->fieldCfg['attr'] as $e_attr_name => $e_attr_val) {
                    $e_attr_str = '';
                    $e_attr_str .= ' ' . $e_attr_name . '="' . $e_attr_val . '"';
                }
            } else {
                $e_attr_str = '';
            }

            $ATTR_CODE = strtoupper($attr_code);

            // if custom cell-content template setted
            if ($this->fieldCfg['content_tpl_hash']) {
                $this->fieldResult = $this->tpl->parse(false, $this->fieldCfg['content_tpl_hash']);
            }

            $this->tpl->asg('CELL_CLASS_ATTR', $classAttr);
            $this->tpl->asg('ITEM_' . $ATTR_CODE . '_CLASS_ATTR', $classAttr);

            $this->tpl->asg('CELL_ATTRS', $e_attr_str);
            $this->tpl->asg('ITEM_' . $ATTR_CODE . '_CELL_ATTRS', $e_attr_str);

            $this->tpl->asg('CELL_CONTENT', $this->fieldResult);
            $this->tpl->asg('ITEM_' . $ATTR_CODE, $this->fieldResult);;

            // cell template is setted;
            if ($this->fieldCfg['tpl_hash']) {
                $fields_content .= $this->tpl->parse(false, $this->fieldCfg['tpl_hash']);
            } else {
                $fields_content .= $this->fieldResult;
            }
        }

        return $fields_content;
    }

    function parseRowFieldsAsJson()
    {
        $r = [];
        if (!is_array($this->fieldsToParse) || count($this->fieldsToParse) < 1 || !is_array($this->row)) {
            return $r;
        }

        foreach ($this->fieldsToParse as $attr_code) {
            $this->fieldResult = false;
            $this->fieldCode = $attr_code;

            $this->fieldCfg = $this->_prepared['fields'][$attr_code];

            $this->fieldClass = 'list-field lf-datatype-' . $this->fieldCfg['data-type'] . ' lf-' . $attr_code;

            if (is_string($this->fieldCfg['class'])) {
                $this->fieldClass .= $this->fieldCfg['class_merge'] && !empty($this->defaultFieldCfg['class'])
                    ? $this->defaultFieldCfg['class'] . ' ' . $this->fieldCfg['class']
                    : $this->fieldCfg['class'];
            } elseif (!empty($this->defaultFieldCfg['class'])) {
                $this->fieldClass .= $this->defaultFieldCfg['class'];
            }

            $this->fire('fieldBefore');

            $this->fieldResult = $this->row[$attr_code];

            // generating value by assigned handlers
            if (is_array($this->fieldCfg['handlers']) && count($this->fieldCfg['handlers'])) {
                foreach ($this->fieldCfg['handlers'] as $ch) {
                    if (!is_object($ch)) {
                        $this->log()->error('Bad field ' . $attr_code . '(' . $this->oh()->getCode() . ') handler ' . var_export($ch, true));
                        continue;
                    }
                    $this->fieldResult = $ch->run();
                }
            }

            $html = [
                'class' => $this->fieldClass,
                'attrs' => null
            ];

            if (!empty($this->fieldCfg['attr'])) {
                $html['attrs'] = $this->fieldCfg['attr'];
            }

            $r[$this->fieldCode] =
                [
                    'content' => $this->fieldResult,
                    'html' => $html
                ];
        }

        return $r;
    }

    ## Headers
    function getHeadersAsJson()
    {
        $r = [];
        if ($this->Selection->c_founded_rows < 1) {
            return $r;
        }

        $this->headersCfg = $this->gC('headers');

        $this->fire('headersRowBefore');

        if (is_array($this->headersCfg['fields'])
            && count($this->headersCfg['fields'])
            && $this->headersCfg['onlyConfigHeaders']) {

            $this->headersToParse = array_keys($this->headersCfg['fields']);

        } elseif (is_array($this->fieldsToParse) && !empty($this->fieldsToParse)) {
            $this->headersToParse = $this->fieldsToParse;
        } else {
            $this->headersToParse = [];
        }

        if (is_array($this->headersToParse) && is_array($this->fieldsToHide)) {
            $this->headersToParse = array_diff($this->headersToParse, $this->fieldsToHide);
        }

        if (!is_array($this->headersToParse) || !count($this->headersToParse)) {
            return $r;
        }

        $hdrs_row_class = 'list-headers-items';
        if ($this->headersCfg['row']['class']) {
            $hdrs_row_class = ' ' . (is_array($this->headersCfg['row']['class'])
                    ? implode(' ', $this->headersCfg['row']['class'])
                    : $this->headersCfg['row']['class']);
        }

        $html = [
            'class' => $hdrs_row_class
        ];

        $cell_class_base = 'list-header';

        $order_allowed = $this->headersCfg['order']['allowed'];
        $order_denied = $this->headersCfg['order']['denied'];
        $fields = [];
        // headers fields parse
        foreach ($this->headersToParse as $this->headerCode) {
            $this->headerCfg = $this->headersCfg['cell'];
            $this->headerFieldCfg = array_key_exists($this->headerCode, $this->_prepared['fields'])
                ? $this->_prepared['fields'][$this->headerCode]
                : [];
            $this->fire('headerCellBefore');

            $this->headerText = $this->generateHeaderText($this->headerCode);
            $A = $this->oh->isA($this->headerCode) ? $this->oh->A($this->headerCode) : $this->headerCode;
            if (isset($this->headerFieldCfg['header']['textHandler']) && is_array($this->headerFieldCfg['header']['textHandler'])
                && !empty($this->headerFieldCfg['header']['textHandler'])) {
                foreach ($this->headerFieldCfg['header']['textHandler'] as $thClassName => $thClassCfg) {
                    if (!class_exists($thClassName)) {
                        $this->log()->error('Unexists List Header Text Generator ' . $thClassName . ' for headerCode: ' . $this->headerCode . ', ot: ' . $this->oh()->getID());
                        continue;
                    }
                    /**
                     * @var $Th \Verba\Act\MakeList\Handler\Header
                     */
                    $Th = new $thClassName($this->oh, $A, $thClassCfg, $this);
                    $this->headerText = $Th->run();
                }
            }

            $cell_class = $cell_class_base . ' lh-' . $this->headerCode;
            if (isset($this->headerCfg['class']) && !empty($this->headerCfg['class'])) {
                $cell_class .= ' ' . (is_array($this->headerCfg['class'])
                        ? implode(' ', $this->headerCfg['class'])
                        : $this->headerCfg['class']);
            }

            $fields[] = [
                'code' => $this->headerCode,
                'text' => $this->headerText,
                'html' => [
                    'class' => $cell_class
                ]
            ];

            $this->fire('headerCellAfter');
        }

        $this->headerCfg = $this->headersCfg['cell'];
        $control_cell_class = $cell_class_base . ' ' . (is_array($this->headerCfg['class'])
                ? implode(' ', $this->headerCfg['class'])
                : $this->headerCfg['class']);

        // rows control-block cell
        $controls = [
            'html' => [
                'class' => $control_cell_class
            ]
        ];
        // rows numbering cell
        if ($this->isRowNumbering()) {
            $numbering = [
                'html' => [
                    'class' => $control_cell_class . ' lh-row-nums'
                ]
            ];
        }else{
            $numbering = null;
        }

        $this->fire('headersRowAfter');

        $r = array_merge($r,
            [
                'order_allowed' => $order_allowed,
                'order_denied' => $order_denied,
                'fields' => $fields,
                'html' => $html,
                'controls' => $controls,
                'numbering' => $numbering,
            ]);

        return $r;
    }

    function parseHeaders()
    {
        if ($this->Selection->c_founded_rows < 1) {
            return '';
        }

        $this->headersCfg = $this->gC('headers');

        $this->fire('headersRowBefore');

        if (is_array($this->headersCfg['fields'])
            && count($this->headersCfg['fields'])
            && $this->headersCfg['onlyConfigHeaders']) {

            $this->headersToParse = array_keys($this->headersCfg['fields']);

        } elseif (is_array($this->fieldsToParse) && !empty($this->fieldsToParse)) {

            $this->headersToParse = $this->fieldsToParse;

        } else {

            $this->headersToParse = array();

        }

        if (is_array($this->headersToParse) && is_array($this->fieldsToHide)) {
            $this->headersToParse = array_diff($this->headersToParse, $this->fieldsToHide);
        }

        if (!is_array($this->headersToParse) || !count($this->headersToParse)) {
            return '';
        }

        $this->tpl->define(array(
            'headers' => $this->headersCfg['row']['tpl'],
            'headers_cell' => $this->headersCfg['cell']['tpl'],
        ));

        $hdrs_row_class = 'list-headers-items';
        if ($this->headersCfg['row']['class']) {
            $hdrs_row_class = ' ' . (is_array($this->headersCfg['row']['class'])
                    ? implode(' ', $this->headersCfg['row']['class'])
                    : $this->headersCfg['row']['class']);
        }

        $this->tpl->assign(array(
            'HEADERS_ROWCLASS_ATTR' => ' class="' . $hdrs_row_class . '"',
            'HEADERS_CELLS' => '',

            'HEADERS_NUMROWS_CELL' => '',
            'HEADERS_CONTROL_BLOCK_CELL' => '',
        ));

        $cell_class_base = 'list-header';

        $order_allowed = $this->headersCfg['order']['allowed'];
        $order_denied = $this->headersCfg['order']['denied'];

        // headers fields parse
        foreach ($this->headersToParse as $this->headerCode) {
            $this->headerCfg = $this->headersCfg['cell'];
            $this->headerFieldCfg = array_key_exists($this->headerCode, $this->_prepared['fields'])
                ? $this->_prepared['fields'][$this->headerCode]
                : array();
            $this->fire('headerCellBefore');

            $this->headerText = $this->generateHeaderText($this->headerCode);
            $A = $this->oh->isA($this->headerCode) ? $this->oh->A($this->headerCode) : $this->headerCode;
            if (isset($this->headerFieldCfg['header']['textHandler']) && is_array($this->headerFieldCfg['header']['textHandler'])
                && !empty($this->headerFieldCfg['header']['textHandler'])) {
                foreach ($this->headerFieldCfg['header']['textHandler'] as $thClassName => $thClassCfg) {
                    if (!class_exists($thClassName)) {
                        $this->log()->error('Unexists List Header Text Generator ' . $thClassName . ' for headerCode: ' . $this->headerCode . ', ot: ' . $this->oh()->getID());
                        continue;
                    }
                    /**
                     * @var $Th \Verba\Act\MakeList\Handler\Header
                     */
                    $Th = new $thClassName($this->oh, $A, $thClassCfg, $this);
                    $this->headerText = $Th->run();
                }
            }

            if ((is_array($order_denied) && in_array($this->headerCode, $order_denied))
                || (is_array($order_allowed) && !in_array($this->headerCode, $order_allowed))) {
                $col_name = $this->headerText;
            } else {
                $col_name = $this->order_link_HTML($this->headerCode, $this->headerText);
            }

            $cell_class = $cell_class_base . ' lh-' . $this->headerCode;
            if (isset($this->headerCfg['class']) && !empty($this->headerCfg['class'])) {
                $cell_class .= ' ' . (is_array($this->headerCfg['class'])
                        ? implode(' ', $this->headerCfg['class'])
                        : $this->headerCfg['class']);
            }

            $this->tpl->assign(array(
                'HEADER_CELL_CONTENT' => $col_name,
                'HEADER_CELL_CLASS_ATTR' => ' class="' . $cell_class . '"',
            ));

            $this->tpl->parse('HEADERS_CELLS', 'headers_cell', true);
            $this->fire('headerCellAfter');
        }

        $this->headerCfg = $this->headersCfg['cell'];
        $control_cell_class = $cell_class_base . ' ' . (is_array($this->headerCfg['class'])
                ? implode(' ', $this->headerCfg['class'])
                : $this->headerCfg['class']);

        // rows control-block cell
        if ($this->isRowControls()) {
            if (is_string($this->headersCfg['control'])) {
                $tplName = 'headers_control';
                $this->tpl->define($tplName, $this->headersCfg['control']);
                $content = $this->tpl->parse(false, $tplName);
            } else {
                $content = '';
            }
            $this->tpl->assign(array(
                'HEADER_CELL_CLASS_ATTR' => ' class="' . $control_cell_class . ' lh-row-controls"',
                'HEADER_CELL_CONTENT' => $content,
            ));
            $this->tpl->parse('HEADERS_CONTROL_BLOCK_CELL', 'headers_cell');
        }
        // rows numbering cell
        if ($this->isRowNumbering()) {
            if (is_string($this->headersCfg['num'])) {
                $tplName = 'headers_num';
                $this->tpl->define($tplName, $this->headersCfg['num']);
                $content = $this->tpl->parse(false, $tplName);
            } else {
                $content = '';
            }

            $this->tpl->assign(array(
                'HEADER_CELL_CLASS_ATTR' => ' class="' . $control_cell_class . ' lh-row-nums"',
                'HEADER_CELL_CONTENT' => $content,
            ));
            $this->tpl->parse('HEADERS_NUMROWS_CELL', 'headers_cell');
        }

        $this->fire('headersRowAfter');
        return $this->tpl->parse(false, 'headers');
    }

    function zerro_result()
    {
        $this->tpl->clear_tpl('empty_list');
        $this->tpl->define(array('empty_list' => $this->gC('empty tpl')));

        $this->_wrapClasses[] = 'list-empty';

        $this->tpl->assign(array(
            'LIST_EMPTY_MESSAGE' => \Verba\Lang::get($this->gC('empty \Verba\LangKey'))
        ));

        return $this->tpl->parse(false, 'empty_list');
    }

    function parsePagerPanels()
    {
        $featKey = 'pager';
        $panelCfg = $this->gC('pager_panel');

        $panelCfg = array(
            'tpl' => $panelCfg['tpl'],
            'items' => array('pager', 'currentRange'/*, 'ronpSelector', 'jumpToPage'*/)
        );

        foreach (array('top', 'bottom') as $side) {
            $isFeatMth = 'isFeatAvaible' . ucfirst($side);
            $panelTplVar = 'SR_PANEL_PAGER_' . strtoupper($side);
            if (!$this->$isFeatMth($featKey)) {
                $this->tpl->assign($panelTplVar, '');
                continue;
            }
            $this->tpl->assign($panelTplVar, $this->parsePanel($panelCfg, $side));
        }
    }

    function parseOptionsPannels()
    {
        $featKey = 'options';
        $featCfg = $this->gC('options');

        foreach (array('top', 'bottom') as $side) {
            $isFeatMth = 'isFeatAvaible' . ucfirst($side);
            $panelTplVar = 'SR_PANEL_OPTIONS_' . strtoupper($side);
            if (!$this->$isFeatMth($featKey)) {
                $this->tpl->assign($panelTplVar, '');
                continue;
            }

            $this->tpl->assign($panelTplVar, $this->parsePanel($featCfg[$side], $side));
        }
    }

    function getOptionsPannelsAsJson()
    {
        $featKey = 'options';
        $featCfg = $this->gC('options');

        $r = [];

        foreach (['top', 'bottom'] as $side) {
            $isFeatMth = 'isFeatAvaible' . ucfirst($side);
            if (!$this->$isFeatMth($featKey)) {
                continue;
            }

            $r[$side] = $featCfg[$side]; // ->getPanelAsJson()
        }

        return $r;
    }

    /**
     * $side 'bottom' | 'top' | null
     */
    function parsePanel($panelCfg, $side = null)
    {
        $this->tpl()->clear_tpl(array('panel'));
        $this->tpl->define(array(
            'panel' => $panelCfg['tpl'],
        ));
        if ($side === null) {
            $sideInt = null;
        } else {
            $sideInt = $side == 'bottom' ? 2 : 1;
        }
        $items = \Verba\Configurable::substNumIdxAsStringValues($panelCfg['items']);
        $ei = 0;
        foreach ($items as $itemKey => $itemCfg) {
            $item_tpl_var = 'SR_PI_' . strtoupper($itemKey);
            if (!$this->isFeat($itemKey, $sideInt)) {
                $this->tpl->assign($item_tpl_var, '');
                continue;
            }
            $blockContent = $this->getBlockHtmlByCode($itemKey, $side);
            if (!empty($blockContent)) {
                $ei++;
            }
            $this->tpl->assign($item_tpl_var, $blockContent);
        }

        if (!$ei && !$panelCfg['forcedParse']) {
            return '';
        }

        $this->tpl->assign(array(
            'PLACE_CLASS_SIGN' => is_string($side) && !is_numeric($side) && !empty($side)
                ? $side
                : ''
        ));

        return $this->tpl->parse(false, 'panel');
    }

    /**
     * $side 'bottom' | 'top' | null
     */
//    function getPanelAsJson($panelCfg, $side = null)
//    {
//        if ($side === null) {
//            $sideInt = null;
//        } else {
//            $sideInt = $side == 'bottom' ? 2 : 1;
//        }
//        $itemsFromCfg = Configurable::substNumIdxAsStringValues($panelCfg['items']);
//
//        $r = [];
//        $items = [];
//
//        foreach ($itemsFromCfg as $itemKey => $itemCfg) {
//            if (!$this->isFeat($itemKey, $sideInt)) {
//                continue;
//            }
//            $items[] = [
//                'name' => $itemKey,
//                'cfg' => $itemCfg,
//                'content' => $this->getBlockHtmlByCode($itemKey, $side)
//            ];
//        }
//
//        if (empty($r) && !$panelCfg['forcedParse']) {
//            return $r;
//        }
//
//        return $this->tpl->parse(false, 'panel');
//    }

    function getBlockHtmlByCode($key, $side = false)
    {
        $key = (string)$key;
        $mthd = 'parse' . ucfirst($key);
        if (!isset($this->_cachedHtml[$key])) {
            $this->_cachedHtml[$key] = '';
            $this->_cachedHtml[$key] = (string)$this->$mthd($side);
        }
        return $this->_cachedHtml[$key];
    }

    function getSelectionParams()
    {
        return [
            'first_row' => $this->Selection->start_row + 1,
            'last_row' => $this->Selection->last_row ??  null,
            'found' => $this->Selection->count_v,
            'rows_on_page' => $this->Selection->ronp,
            'pages' => [
                'total' => (int)$this->Selection->getTotalPages(),
                'current' => $this->Selection->getPage(),
            ]
        ];
    }

    function parseCurrentRange()
    {
        $this->tpl->define('searchResult_crows_info', $this->gC('crows_info tpl'));
        $this->tpl->assign(array('SEARCH_LAST_ROW' => isset($this->Selection->last_row) ? $this->Selection->last_row : "",
            'SEARCH_FIRST_ROW' => $this->Selection->start_row + 1,
            'SEARCH_SERCHED_ROWS' => $this->Selection->count_v,
        ));
        return $this->tpl->parse(false, 'searchResult_crows_info');
    }

    function parsePager()
    {

        $total_pages = (int)$this->Selection->getTotalPages();
        $c_page = $this->Selection->getPage();

        if ($total_pages < 2) {
            return '';
        }

        $nav_url = $this->getForwardUrl();

        $cfg = $this->gC('navout');
        $tpl = $this->tpl();
        $tpl->define(array('navout' => $cfg['tpl'],
            'navout_left' => $cfg['left'],
            'navout_right' => $cfg['right'],
            'navout_prev' => $cfg['prev'],
            'navout_next' => $cfg['next'],
            'nav_item_root' => $cfg['item_root'],
            'link_page' => $cfg['link_page_tpl'],
            'c_page' => $cfg['c_page_tpl'],
        ));

        $tpl->assign(array('SEARCH_TOTAL_PAGES' => $total_pages,
            'SEARCHNAVOUT_LEFTPART' => '',
            'SEARCHNAVOUT_RIGHTPART' => '',
            'SEARCHNAVOUT_PREVPAGE' => '',
            'SEARCHNAVOUT_NEXTPAGE' => '',
            'SEARCHNAVOUT_CENTER' => '',
            'SR_NAV_URL' => $nav_url,
        ));
        $make_padej_func = '\Verba\make_padej_' . SYS_LC_DEFAULT;

        $tpl->assign('STRANIC_WORD', $make_padej_func(
                $total_pages,
                \Verba\Lang::get($cfg['item_root']),
                array(\Verba\Lang::get($cfg['item_0']),
                    \Verba\Lang::get($cfg['item_1']),
                    \Verba\Lang::get($cfg['item_2']))
            )
        );
        $left_limit = 1;
        $right_limit = $total_pages + 1;

        $url = new \Verba\Url($nav_url);

        //classes
        $linkclasses = $clinkclasses = 'list-button-page';

        if ($cfg['link_page_class']) {
            $linkclasses .= ' ' . $cfg['link_page_class'];
        }

        if ($cfg['c_page_class']) {
            $clinkclasses .= ' ' . $cfg['c_page_class'];
        }

        $linkpagesignclass = 'list-button-page-v-';

        if ($total_pages > 10) {
            if ($c_page - 1 > 2) {
                $left_limit = $c_page - 2;
            }
            if ($total_pages - $c_page > 2) {
                $right_limit = $c_page + 3;
            }
            // First and Last pages elements
            if ($this->gC('feats firstLastPage')) {
                if ($c_page - 1 >= 3) {
                    $url->setParams(array('page' => 1));
                    $tpl->assign(array(
                        'SR_NAV_FIRST_CLASS' => $linkclasses . ' list-first-page ' . $linkpagesignclass . '1',
                        'SR_NAV_URL_FIRST' => $url->get()
                    ));
                    $tpl->parse('SEARCHNAVOUT_LEFTPART', 'navout_left');
                }

                if ($total_pages - $c_page >= 3) {
                    $url->setParams(array('page' => $total_pages));
                    $tpl->assign(array(
                        'SR_NAV_LAST_CLASS' => $linkclasses . ' list-last-page ' . $linkpagesignclass . $total_pages,
                        'SR_NAV_URL_LAST' => $url->get()
                    ));
                    $tpl->parse('SEARCHNAVOUT_RIGHTPART', 'navout_right');
                }
            }
        }
        // Prev and Next pages elements
        if ($this->gC('feats prevNextPage')) {
            if ($c_page > 1) {
                $url->setParams(array('page' => $c_page - 1));
                $tpl->assign(array(
                    'SR_NAV_URL_PREV' => $url->get(),
                    'SR_NAV_PREV_CLASS' => $linkclasses . ' prev-page ' . $linkpagesignclass . ($c_page - 1),
                ));
                $tpl->parse('SEARCHNAVOUT_PREVPAGE', 'navout_prev');
            }
            if ($c_page < $total_pages) {
                $url->setParams(array('page' => $c_page + 1));
                $tpl->assign(array(
                    'SR_NAV_URL_NEXT' => $url->get(),
                    'SR_NAV_NEXT_CLASS' => $linkclasses . ' prev-page ' . $linkpagesignclass . ($c_page + 1)
                ));
                $tpl->parse('SEARCHNAVOUT_NEXTPAGE', 'navout_next');
            }
        }
        $nav_out_center = '';
        for ($i = $left_limit; $i < $right_limit; $i++) {
            $url->setParams(array('page' => $i));
            $tpl->assign(array(
                'SR_NAV_URL_CPAGE' => $url->get(),
                'SR_I' => $i,
                'SR_LINK_PAGE_CLASS' => ($i != $c_page
                    ? $linkclasses . ' ' . $linkpagesignclass . $i
                    : $clinkclasses . ' selected ' . $linkpagesignclass . $i
                )
            ));
            $nav_out_center .= $i != $c_page ? $tpl->parse(false, 'link_page') : $tpl->parse(false, 'c_page');
        }
        $tpl->assign(array('SEARCHNAVOUT_CENTER' => $nav_out_center));
        return $tpl->parse(false, 'navout');
    }

    function parseRonpSelector()
    {
        $sl = new \Verba\Html\Select();
        $sl->setValues($this->gC('navout ronp values'));
        $sl->setValue($this->Selection->ronp);
        $sl->setClasses('list-button-ronp list-' . $this->listId);
        $this->tpl->define('ronp', 'list/default/ronp.tpl');

        $this->tpl->assign(array(
            'ROWS_ON_PAGE_SELECT' => $sl->build(),
            'RONP_SELECTOR_TITLE' => \Verba\Lang::get('list ronp_selector_title')
        ));

        return $this->tpl->parse(false, 'ronp');
    }

    function parseJumpToPage()
    {

        $this->tpl->define(array('JumpToPage' => 'list/default/navigation/JumpToPage.tpl'));

        $this->tpl->assign('SEARCH_URLFORPAGE', $this->getForwardUrl());

        return $this->tpl->parse(false, 'JumpToPage');
    }

    function parseButtons($side)
    {

        $cfg = $this->gC('buttons ' . $side);

        if ($side == 'bottom' && !is_array($cfg) || !count($cfg)) {
            $cfg = $this->gC('buttons top');
        }

        if (!is_array($cfg) || !count($cfg)) {
            return '';
        }

        $this->tpl->define(array(
            'buttons_tbl' => $cfg['tbl'],
            'button_wrap' => $cfg['button_wrap'],
        ));
        $isFeatMthd = 'isFeatAvaible' . ucfirst($side);
        $U = User();
        $this->tpl->assign('BUTTONS_ROW', '');
        if (is_array($cfg['items'])) {
            $default_class = isset($cfg['default_button_class']) ? (array)$cfg['default_button_class'] : array();
            foreach ($cfg['items'] as $buttonKey => $buttonCfg) {
                if (!$buttonCfg || (isset($buttonCfg['feat']) && !$this->isFeat($buttonCfg['feat']))) {
                    continue;
                }
                if (isset($buttonCfg['action'])) {
                    $actCfg = $buttonCfg['action'];
                    unset($buttonCfg['action']);

                    if (is_string($actCfg)) { // если строка
                        $action = $actCfg;
                    } elseif (is_array($actCfg) // если массив, то интерпретируется как генератор.
                        && array_key_exists(0, $actCfg) && array_key_exists(1, $actCfg)) {
                        $method = $actCfg[1];
                        $args = array($this, $buttonKey, &$buttonCfg);
                        if (array_key_exists('cfg', $actCfg)) {
                            $args[3] = $actCfg['cfg'];
                        }
                        $ct = $actCfg[0] === null ? $this : \Verba\_mod($actCfg[0]);
                        $action = call_user_func_array(array($ct, $method), $args);
                    } else {
                        $action = '';
                    }
                    $this->tpl->assign('BUTTON_ACTION_PATH', $action);
                }

                $this->tpl->clear_tpl('button_template');
                $this->tpl->define(array('button_template' => $buttonCfg['tpl']));

                if (isset($buttonCfg['rights']) && \Verba\reductionToArray($buttonCfg['rights'])) {
                    reset($buttonCfg['rights']);
                    foreach ($buttonCfg['rights'] as $key => $rights) {
                        if (0 === $key) {
                            $key = $this->keyId;
                        }
                        if (!$U->chr($key, $rights)) {
                            continue 2;
                        }
                    }
                }
                if (isset($buttonCfg['class'])) {
                    $custom_class = $buttonCfg['class'];
                    \Verba\reductionToArray($custom_class);
                } else {
                    $custom_class = false;
                }
                $merged_class = $custom_class ? array_merge($default_class, $custom_class) : $default_class;
                $this->tpl->assign('BUTTON_CLASS', is_array($merged_class) ? implode(' ', $merged_class) : '');

                if (isset($buttonCfg['titleLangKey'])) {
                    $title = \Verba\Lang::get($buttonCfg['titleLangKey']);
                }
                if (!isset($title) || !$title) {
                    $title = isset($buttonCfg['titleLangKey']) ? $buttonCfg['titleLangKey'] : 'UnassignedButtonName';
                }
                $this->tpl->assign('BUTTON_TITLE', $title);
                $btn = $this->tpl->parse(false, 'button_template');
                // add workers for button if defined
                if (isset($buttonCfg['workers']) && is_array($buttonCfg['workers']) && count($buttonCfg['workers'])) {
                    $this->setWorkers($buttonCfg['workers']);
                }

                $this->tpl->assign(array(
                    'LIST_BUTTON_' . strtoupper($buttonKey) => $btn,
                    'LIST_BUTTON' => $btn));
                $this->tpl->parse('BUTTONS_ROW', 'button_wrap', true);
            }
        }
        return $this->tpl->parse(false, 'buttons_tbl');
    }

    /**
     * @param array|string $arg1 Массив вида tplKey => tplFilePath или, если передается второй аргумент tplKey
     * @param string|null $arg2 Если передано, воспринимается как путь к шаблону, tplKey которого передается первым аргументом
     * @return bool
     */
    function addClientTemplate($arg1, $arg2 = null)
    {
        if (is_string($arg1) && is_string($arg2)) {
            $arr = array($arg1 => $arg2);
        } elseif (is_array($arg1) && count($arg1)) {
            $arr = $arg1;
        }

        if (!isset($arr)) {
            return false;
        }
        foreach ($arr as $tplKey => $tplFilePath) {
            $_tplKey = 'client_template_' . $tplKey;
            $this->client_templates[$tplKey] = $_tplKey;
            $this->tpl->define($_tplKey, $tplFilePath);
        }
        return true;
    }

    function parseClientTemplates()
    {
        if (!is_array($this->client_templates) || empty($this->client_templates)) {
            return null;
        }
        $this->tpl->clear_vars('CLIENT_TEMPLATES_ITEMS');
        foreach ($this->client_templates as $tplKey => $_tplKey) {
            $tplContent = $this->tpl->getTemplate($_tplKey);
            if (!is_string($tplContent)) {
                $badPath = $this->tpl->getTplPath($_tplKey);
                $this->log()->error('Unable to load client tpl file "' . var_export($_tplKey, true) . '", path: ' . var_export($badPath, true));
                continue;
            }
            $this->tpl->assign('CLIENT_TEMPLATES_ITEMS', '<div data-tpl="' . $tplKey . '">' . $tplContent . '</div>', true);
        }
        $this->tpl->parse('CLIENT_TEMPLATES', 'client_templates');

        return true;
    }

    /**
     * Проверка доступа если задан в кофиге ключ 'access'.
     *
     * @param array $manage_groups Массив групп участие в которых однозначно приводит к true
     * @param mixed $mode режим проверки прав для ключей 'access'. 0 - Или, достаточно положительного решения хоть по одному ключу; 1 - И требуется совпадение прав по всем ключам.
     *
     * @return bool true|false;
     */
    function validateAccess()
    {

        $U = User();

        $mode = $this->gC('access_mode');

        if (is_string($keys = $this->gC('access')) || is_numeric($keys)) {
            $keys = array($keys => 's');
        } elseif (!is_array($keys)) {
            $keys = array($this->oh->getBaseKey() => 's');
        }

        if (count($keys)) {
            $i = 0;
            foreach ($keys as $key => $rights) {
                $y = 0;
                // проверяем в общих правах
                if ($U->chr($key, $rights, 1)) {
                    $y++;

                    // проверяем доступ в правах для владельца
                } elseif ($U->chr($key, $rights, 2)) {
                    // Если по этому ключу доступ только по владельцу - добавляем условия WHERE для этого ключа
                    if (!$y) {
                        $this->addValidOwner($key, $U->getID());
                    }
                    $y++;
                }

                if ($y) {
                    $i++;
                }
            }
        }

        return !isset($i)
        || (
            $mode && $i == count($keys)
            || (!$mode && count($keys) > 0 && $i > 0)
        )
            ? true : false;
    }

    function addValidOwner($keyId, $ownerId)
    {
        $keyId = (int)$keyId;
        $ownerId = (int)$ownerId;
        if (!$keyId || !$ownerId) {
            return false;
        }

        if (!is_array($this->validOwner)) {
            $this->validOwner = array();
        }

        if (!array_key_exists($keyId, $this->validOwner)) {
            $this->validOwner[$keyId] = array(
                'keyId' => false,
                'ownerId' => false,
            );
        }

        $this->validOwner[$keyId]['keyId'] = $keyId;
        $this->validOwner[$keyId]['ownerId'] = $ownerId;

        return true;

    }

    protected function addValidOwnerWhereCondition()
    {
        if (!is_array($this->validOwner) || !count($this->validOwner)) {
            return true;
        }
        $ownerFieldCode = $this->oh()->getOwnerAttributeCode();
        $QM = $this->QM();
        list($a) = $QM->createAlias();

        foreach ($this->validOwner as $key => $keyData) {
            if (!is_integer($keyData['ownerId'])
                || !is_integer($keyData['keyId'])) {
                continue;
            }

            $where_alias = '__list_only_owner_for_key_' . $keyData['keyId'];
            $QM->removeWhere($where_alias);
            $Wgroup = $QM->addWhereGroup($where_alias);
            $Wgroup->addWhere($keyData['ownerId'], $ownerFieldCode, false, $a);
            $Wgroup->addWhere($keyData['keyId'], 'key_id', false, $a);
        }
    }

    function addExtData($name, $data = null)
    {
        if (is_array($name) && $data === null) {
            foreach ($name as $k => $v) {
                $this->addExtData($k, $v);
            }
            return;
        }
        $this->extendedData[$name] = $data;
    }

    function getExtData($name)
    {
        return $this->extendedData[$name];
    }

    //JavaScript
    function parseJSInstance()
    {
        if(!isset($this->_c['js']['instance']) || !$this->_c['js']['instance']) {
            return '';
        }

        $this->jsCfg['id'] = $this->listId;
        $this->jsCfg['wrapId'] = $this->wrapId;
        $this->jsCfg['otId'] = $this->ot_id;
        $this->jsCfg['keyId'] = $this->keyId;
        if (count($this->parents)) {
            //list($pot, $piid) = $this->getFirstParent();
            $this->jsCfg['pot'] = $this->parents;
            //$this->jsCfg['piid'] = $piid;
        }
        $this->jsCfg['page'] = $this->Selection()->getPage();
        $this->jsCfg['slId'] = $this->slId;
        $this->jsCfg['url'] = $this->_c['url'];
        $this->jsCfg['optionsstate'] = $this->_c['options']['state'];
        $this->jsCfg['selected'] = $this->Selection->getSelected();
        $this->jsCfg['selectedOther'] = $this->Selection->getSelectedCount();
        $this->jsCfg['reloadMethod'] = $this->_c['reloadMethod'];
        $this->jsCfg['dialogsModal'] = $this->_c['dialogsModal'];
        $this->jsCfg['itemClickAction'] = is_string($this->_c['itemClickAction']) ? $this->_c['itemClickAction'] : false;

        $this->tpl->define(['__js_instance' => $this->_c['js']['instance']]);
        $this->tpl->assign(array(
            'LIST_JS_INST_CFG' => json_encode($this->jsCfg)
        ));

        return $this->tpl->parse(false, '__js_instance');
    }

    function setHeaders($var){
        $pn = $this->_confPropName;
        $this->{$pn}['headers'] = array_replace_recursive($this->{$pn}['headers'], $var);

        return $this->{$pn}['headers'];
    }
}

MakeList::$_config_default = array(
    'listId' => false,
    'url' => array(
        'forward' => false, // to reload list with new params for example
        'update' => false, // url to send to upadte exists List Item
        'edit' => false, // url to get List Item Edit Form
        'new' => false, // url to get List Item Add Form
        'delete' => false, // url to request List Items delete
        'base' => false,
        'listerBase' => '/lister', // Lister mod
    ),
    'css' => false, // подключаемые css-файлы в формате
    'scripts' => false,
    'access' => false, // массив прав для
    'access_mode' => 0, // режим проверки прав - 0 - хотя бы по одному ключу, 1 - требуется совпадение по всем
    'wrap' => 'list/default/wrap.tpl', // list body wrap
    'body' => 'list/default/body.tpl', // Подложка тела списка, все элементы
    'table' => array(
        'tpl' => 'list/default/table.tpl'
    ), // тело списка - заголовки и данные
    'class' => '',
    'layout' => 'table',
    'pager_panel' => array(
        'tpl' => '/list/default/panels/pager.tpl'//
    ),
    'feats' => array(

        // (маска верх,низ) панель опций (постраничка, результаты, строк на страницу, кнопки)
        'options' => 1,

        //(маска верх,низ) pager-panel
        'pager_panel' => 3,
        // (маска верх,низ) постраничка
        'pager' => 3,

        // (булево) кнопка (возможность) добавления нового элемента
        'addnew' => 0,

        // вывод строки заголовков, true, false
        'headers' => 1,

        // кнопки действий верх,низ
        'buttons' => 3,

        // кнопка (возможность) выбрать все, булево
        'selectAll' => 1,

        // элемент "перейти на страницу", верх,низ
        'jumpToPage' => 0,

        // (булево) элементы следующая и предыдущая страницы в постраничке.
        'prevNextPage' => 0,

        // (булево) элементы последняя и первая страницы в постраничке.
        'firstLastPage' => 1,

        // (маска верх,низ) выбор отображения количества записей на страницу
        'ronpSelector' => 3,

        // (маска верх,низ) элемент текущий диапазон строк
        'currentRange' => 3,

        // (булево) наличие блока с элементами управления (чекбокс, кнп.редактирование)
        // у элемента списка
        'rowControls' => 1,

        // (булево) наличие элемента "выбрать" (чекбок) у элемента списка.
        'rowControlsCheckbox' => 1,

        // (булево) нумерация строк.
        'rowNumbering' => 0,

        // (булево) вывод заголовка списка.
        'listTitle' => 1,

        // (булево) возможность редактировать элемент списка (наличие кнопки)
        'editable' => 0,
    ),
    'row' => array( // Шаблон строки списка
        'tpl' => 'list/default/row.tpl',
        'class' => '',
        'num' => 'list/default/row_numbering.tpl',
        'handler' => array(),
        // отключает генерацию полей методом по умолчанию если указан 'генератор' строки
        // предполагается, что вся работа по генерации значений будет выполнена в обработчике handler
        'preventDefaultFieldParseIfHandler' => true,
    ),
    'empty' => array(
        'tpl' => 'list/default/empty_list.tpl',
        'langKey' => 'list empty message'
    ),
    'filters' => array(
        'captionInside' => true, // bool
        'wrap' => array(
            'tpl' => 'list/default/filters/wrap.tpl',
            'class' => false,
        ),
        'item_wrap' => array(
            'tpl' => 'list/default/filters/item_wrap.tpl',
            'caption_tpl' => 'list/default/filters/item_caption.tpl',
            'class' => false,
        ),
        'buttons' => array(
            'tpl' => 'list/default/filters/panel-buttons.tpl',
            'items' => array(
                'reset' => 'list filters buttons reset',
                'apply' => 'list filters buttons apply'
            )

        ),
        'items' => array(
            //'className' => 'class name prefix'
            // other keys as filter class config
        )
    ),

    'control_block' => array( // Блок управляющих элементов - чекбокс, ссылка на редактирование.
        'tpl' => 'list/default/control/block.tpl',
        'class' => null,
        'edit_element' => 'list/default/control/edit_element.tpl', // передав false - сам элемент парсится не будет, однако edit_url будет сформирован
        'select_element' => 'list/default/control/select_element.tpl',
    ),

    'headers' => array( // Заголовки - если grid - названия колонок, иначе - настройки линейки ссылок сортировки.
        'row' => array(
            'tpl' => '/list/default/headers/row.tpl',
            'class' => '',
        ),
        'cell' => array(
            'tpl' => '/list/default/headers/cell.tpl',
            'class' => '',
        ),
        'control' => false,// false | string as cell content-tpl for `control` header cell '/list/default/headers/control_content.tpl',
        'num' => false,// false | string as cell content-tpl for `num` headers cell
        'e_class' => 'list-button-order',
        'ordered_class' => 'ordered',
        'onlyConfigHeaders' => 0, // если true - сортируемыми будут только те поля что указаны в ключе headers-fields
        'fields' => array( // псевдонимы полей /*'filed_name'  => array('title' => 'alt text for display')*/
        ),

        'order' => array( // разрешенный/запрещенные в сортировке поля.
            'allowed' => false, // array = (f1, f2, f3)
            'denied' => false // array = (f1, f2, f3)
        ),
    ),

    'title' => array(
        'tpl' => 'list/default/title.tpl',
        'class' => 'list-title  wow fadeInLeft',
        'leaveEmptyTag' => false,
    ),
    'only_config_fields' => true,
    'fields' => false,
    'field_default' => array(
        'data-type' => false,
        'class' => '',
        'class_merge' => false,
        // cell template
        'tpl' => 'list/default/cell.tpl',
        // cell content custom template. If empty cell content will be fieldValue
        'content_tpl' => false,
        'handlers' => array(),
        'preventDefaultHandlers' => false,
        'header' => array(
            'textHandler' => null,
        ),
    ),
    'order' => array(
        // порядок парсинга полей, сначала указанные, потом все остальные.
        // формат array(attr1_code[, attr2_code])
        'priority' => false,

        // set default sorting. Format is like list->addOrder() method
        // array('attr_code' => 'd'[,'attr_code1' => 'a'])
        // or, with vault defining
        // array(
        //    array( array('acode' => 'd'[,'acode1' => 'a'])), //vith default vault
        //    array( array('acode' => 'd'[,'acode1' => 'a']), vault), //vith custom vault
        // )
        'default' => false,
        'subst' => null,
    ),
    'navout' => array(//Линейка постраничного вывода
        'tpl' => 'list/default/navigation/navout.tpl',
        'left' => 'list/default/navigation/ending_left.tpl',
        'right' => 'list/default/navigation/ending_right.tpl',
        'prev' => 'list/default/navigation/prev_page.tpl',
        'next' => 'list/default/navigation/next_page.tpl',
        'jtp' => 'list/default/navigation/JumpToPage.tpl',
        'class' => 'smaller',
        'c_page_tpl' => 'list/default/navigation/c_page.tpl',
        'c_page_class' => '',
        'link_page_tpl' => 'list/default/navigation/link_page.tpl',
        'link_page_class' => '',
        'total_pages' => true,
        //  языковые ключи в словарях для слова "страниц|а|ы":
        'item_root' => 'list navigation item_root', // корень - страниц
        'item_0' => 'list navigation item_0',  // окончание 0
        'item_1' => 'list navigation item_1',  // окончание 1 - ы
        'item_2' => 'list navigation item_2',  // окончание 2 - а

        'ronp' => array(
            'upper' => true,
            'default' => 50,
            'values' => array(10 => 10, 15 => 15, 30 => 30, 60 => 60, 120 => 120, 240 => 240),
        ),
    ),

    'crows_info' => array(
        'tpl' => 'list/default/crows_info.tpl'
    ),
    'buttons' => array(  // управляющие кнопки для отмеченных объектов
        'top' => array(
            'tbl' => 'list/default/buttons/tbl.tpl',
            'button_wrap' => 'list/default/buttons/button_wrap.tpl',
            'items' => array(
                'selectall' => array(
                    'tpl' => 'list/default/buttons/selectall.tpl',
                    'class' => 'list-button-selectall',
                    'feat' => 'selectAll',
                ),
                'addnew' => array(
                    'tpl' => 'list/default/buttons/addnew.tpl',
                    'action' => array(null, 'makeAddNewUrl'),
                    'rights' => 'c',
                    'class' => 'list-button-addnew',
                    'feat' => 'addnew',
                    'titleLangKey' => 'list buttons addnew',
                ),
                'delete' => array(
                    'tpl' => 'list/default/buttons/del.tpl',
                    'action' => array(null, 'makeDeleteUrl'),
                    'rights' => 'd',
                    'class' => 'list-button-delete',
                    'workers' => array(
                        'deleteButton' => array(
                            '_script' => array('workers', 'list'),
                            '_className' => 'DeleteButton',
                        ),
                    ),
                ),
            ),
            'default_button_class' => 'btn-option'
        ),
        'bottom' => false, // использовать этот ключ что бы задать отдельные шаблоны кнопок в нижних опциях. Иначе испольуется конфиг кнопок верхнего блока.
    ),
    'options' => array( // шаблоны верхнего и нижнего блоков опций
        'state' => 0, // opened or closed by default: 0 - closed; 1 - opened;
        'top' => array(
            'forcedParse' => false,
            'tpl' => 'list/default/panels/options.tpl',
            'items' => [
                'buttons',
                'ronpSelector'
            ]
        ),
        'bottom' => array(
            'forcedParse' => false,
            'tpl' => 'list/default/panels/options.tpl',
            'items' => [
                'buttons',
                'ronpSelector'
            ],
            'max_rows_condition' => 10, // если в выборке строк меньше этого числа, нижние опции не показываются
        ),
    ),
    'parentsRelation' => false,
    'workers' => array(/*'workerAlias' => array(
      'alias' => 'scalias'
      'script' => arrat('myworker', 'mymod/list'), //скрипт расположения класса воркера
      'name' => 'ListSomeWorkerClassName',        //Название js-класса воркера
      'args' => array("{'url':'myurl'}", "'someStringValue'")    //аргументы в конструктор js-класса, начиная со второго
    )  */
    ),
    'js' => [
        'instance' => 'list/default/instance_js.tpl',
        'wrap_onready' => false,
    ],
    'reloadMethod' => 'ajax',
    'dialogsModal' => false,
    'itemClickAction' => false,
);
/*
'fields'  => array(
      'price'  => array(
          'class'  => 'rsl_price'
      ),
      'title'  => array(
          'preventDefaultHandlers'  => true,
          'handlers'      => array(array('realityhouse', 'title2link')
          // в метод-хендлере первыми двумя атрибутами будут переданы
          // $list, $row
      ),
      'image'  => array(
          'handlers'      => array(array('realityhouse', 'image2link'),
      ),
*/
