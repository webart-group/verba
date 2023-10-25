<?php
namespace Verba;

class Selection extends Base
{
    public $slID;
    public $key_id;
    public $ot_id;
    public $url_var = '';
    public $request = array();
    protected $cache_used = false;
    public $search_q = '';
    public $count = false;
    public $count_q = '';
    public $count_v = false;
    protected $total_rows_in_dv = 0;
    public $page = 1;
    protected $total_pages = 1;
    public $ronp;
    public $ronp_num = false;
    public $c_founded_rows = 0;
    public $start_row = false;
    public $last_row = '';
    public $selected = array();
    protected $defaultOrder = array();
    protected $defaultOrderApplied = false;
    protected $_existsOrder = null;
    protected $isOrderRefreshed = false;
    protected $compiledParts = false;
    private $_ssp = array(); // session save point;
    protected $calcRows = true;
    public $nav_out = '';
    public $seq = false;

    public $QM = false;

    /**
     * @var array Array of active selections
     */
    protected static $_instances = [];

    function __construct($ot_id, $slID = false, $key_id = false)
    {
        if (!$slID) {
            for ($i = 0;
                 $i < 3;
                 $i++, $slID = false
            ) {
                $slID = \Verba\Hive::make_random_string(6, 6, 'l');
                if (!array_key_exists($slID, self::$_instances)) {
                    break;
                }
            }

        } else {
            $slID = (string)$slID;
        }

        if (array_key_exists($slID, self::$_instances)) {
            throw new \Exception('Selection with same id ' . var_export($slID, true) . ' exists');
        }

        if (!$this->setId($slID)) {
            throw new \Exception('Bad Selection id - ' . var_export($slID, true));
        }
        /**
         * @todo Заменить глобальную init_selection
         * Надо переделать так что бы по Selection::get($slId)
         * отдавалась существующая или создавалась новая, свежая или из кеша, выборка
         *
         *
         */
        if(!self::register($this)) {
            throw new \Exception('Unable to register Selection [' . var_export($slID, true).']');
        }

        $this->set_ot_id($ot_id);
        $this->set_key_id(!$key_id ? \Verba\_oh($ot_id)->getBaseKey() : $key_id);

        $this->url_var = $this->slID . '_';
        if (isset($_REQUEST[$this->url_var])) {
            $this->setRequest($_REQUEST[$this->url_var]);
        }

        $this->QM = new \Verba\QueryMaker($this->ot_id, $this->key_id);

        $this->getSSP();
    }

    /**
     * @param bool $val
     * @return $this
     */
    function setCalcRows(bool $val)
    {
        $this->calcRows = $val;
        return $this;
    }

    function setId($var) {
        if(!$this->validateId($var)) {
            return false;
        }

        $this->slID = $var;
        return $this->slID;
    }

    function getId() {
        return $this->slID;
    }

    static function register($Selection)
    {
        if(!$Selection instanceof self){
            return false;
        }

        $slId = $Selection->getId();

        if(!$slId || array_key_exists($slId, self::$_instances)){
            return false;
        }

        self::$_instances[$slId] = $Selection;

        return true;
    }

    /**
     * @param $id string Selection id
     *
     * @return \Selection|null
     */
    static function get($id)
    {
        return array_key_exists((string)$id, self::$_instances)
            ? self::$_instances[$id]
            : null;
    }

    static function validateId($val)
    {
        return is_string($val) && preg_match('/\w+/',$val);
    }

    function __sleep()
    {
        $a = get_object_vars_public($this);
        unset(
            $a['QM'], $a['cache_used'], $a['search_q']
        );
        $a['_existsOrder'] = '';
        return array_keys($a);
    }

    function __wakeup()
    {
        if(!self::register($this)) {
            throw new \Exception('Unable to restore Selection - registration failed. id ['.var_export($this->getId(), true).']');
        }

        $this->getSSP();

        if (isset($_REQUEST[$this->url_var])) {
            $this->setRequest($_REQUEST[$this->url_var]);
        }

        $this->QM = new \Verba\QueryMaker($this->ot_id, $this->key_id);
    }

    function getSSP()
    {
        if (!is_array($this->_ssp)) {
            if (!isset($_SESSION['selections'][$this->slID])
                || !is_array($_SESSION['selections'][$this->slID])
                || !array_key_exists('time', $_SESSION['selections'][$this->slID])
                || !array_key_exists('data', $_SESSION['selections'][$this->slID])) {
                $_SESSION['selections'][$this->slID] = array(
                    'time' => 0,
                    'data' => null,
                );
            }
        }
        $this->_ssp = &$_SESSION['selections'][$this->slID];
    }

    function save2session()
    {
        $this->_ssp['time'] = time();
        $this->_ssp['data'] = serialize($this);
    }

    function cleanUpOldSessionCache()
    {

        if (count($_SESSION['selections']) < 20) {
            return true;
        }

        foreach ($_SESSION['selections'] as $k => $sldata) {
            if (time() - $sldata['time'] > 3600) {
                unset($_SESSION['selections'][$k]);
            }
        }
    }

    function QM()
    {
        return $this->QM;
    }

    function setRequest($val)
    {
        $this->request = $val;
    }

    function getRequest()
    {
        return $this->request;
    }

    function setCacheUsed($bool)
    {
        $this->cache_used = is_bool($bool) ? $bool : false;
    }

    function getCacheUsed()
    {
        return $this->cache_used;
    }

    function formated()
    {
        return is_string($this->slID) && !empty($this->slID) && is_object($this->QM);
    }

    function refresh_sets()
    {
        $this->refreshSelected();
        $this->set_ronp_num($this->make_ronp_num());
        $this->set_page($this->make_page());
        $this->set_start_row($this->calc_start_row());

        $this->QM->addLimit($this->ronp_num, $this->start_row);

        $this->refreshOrder();
    }

    /**
     * @param string $query
     * @return $this
     */
    function setSearchQuery(string $query)
    {
        $this->search_q = $query;
        return $this;
    }

    function refresh_querys()
    {
        if(!is_string($this->search_q) || empty($this->search_q)){
            if($this->calcRows){
                $this->QM->addSelectProp('SQL_CALC_FOUND_ROWS');
            }
            $this->QM->makeQuery();
            $this->search_q = $this->QM->getQuery();
        }
        $this->count = true;

        if (!$this->formated())
            return false;

        $this->cleanUpOldSessionCache();
        return true;
    }

    function exec_query()
    {
        $sqlr = $this->DB()->query($this->search_q);
        if (!$sqlr) {
            return false;
        }
        $SQL_CALC_FOUND_ROWS = 0;
        if ($this->calcRows) {
            $SQL_CALC_FOUND_ROWS = $this->DB()->query('SELECT FOUND_ROWS()')->getFirstValue();
        }
        $this->set_c_founded_rows($sqlr->getNumRows());
        $this->set_last_row($this->get_last_row());
        $this->set_count_v($SQL_CALC_FOUND_ROWS);
        $this->set_total_pages($this->calc_total_pages());

        return $sqlr;
    }

    function getUrlVar()
    {
        return $this->url_var;
    }

    function set_c_founded_rows($val)
    {
        $this->c_founded_rows = is_numeric($val) && $val > 0 ? $val : 0;
    }

    function getOtId()
    {
        return $this->ot_id;
    }

    function set_ot_id($ot_id)
    {
        if (is_numeric($ot_id) && $ot_id > 0)
            $this->ot_id = $ot_id;
        else
            $this->ot_id = -1;
    }

    function getKeyId()
    {
        return $this->key_id;
    }

    function set_key_id($key_id)
    {
        if (is_numeric($key_id) && $key_id > 0)
            $this->key_id = $key_id;
        else
            $this->key_id = false;
    }

    function get_count_v()
    {
        return $this->count_v;
    }

    function set_count_v($count_v)
    {
        $this->count_v = (int)$count_v;
    }

    function calc_total_pages()
    {
        if (is_int($this->count_v)
            && $this->count_v > 0
            && is_int($this->ronp_num)
            && $this->ronp_num > 0) {
            $total_pages = ceil($this->count_v / $this->ronp_num);
        } else {
            $total_pages = 1;
        }
        return $total_pages;
    }

    function set_total_pages($val)
    {
        $this->total_pages = (int)$val;
    }

    function getTotalPages()
    {
        return $this->total_pages;
    }

    function make_page()
    {
        IF (is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0) {
            $page = (int)$_REQUEST['page'];
        } ELSE {
            $page = 1;
        }

        return $page;
    }

    function set_page($page)
    {
        $this->page = $page;
    }

    function getPage()
    {
        return $this->page;
    }

    function getRonp()
    {
        return $this->ronp;
    }

    function setRonp($val)
    {
        if ($val = intval($val))
            $this->ronp = $val;
    }

    function make_ronp_num()
    {

        if (is_numeric($this->ronp)) {
            $ronp_num = $this->ronp;
        } else {
            $ronp_num = $this->default['ronp'];
        }

        return $ronp_num;
    }

    function set_ronp_num($val)
    {
        $this->ronp_num = (int)$val;
    }

    function get_ronp_num()
    {
        return $this->ronp_num;
    }

    function calc_start_row()
    {

        IF (is_numeric($this->ronp_num) && is_numeric($this->page)) {
            $start_row = ($this->page - 1) * $this->ronp_num;
        } ELSE
            $start_row = 0;

        return $start_row;
    }

    function get_start_row()
    {
        return $this->start_row;
    }

    function set_start_row($val)
    {
        $this->start_row = (int)$val;
    }

    function get_last_row()
    {
        return is_numeric($this->start_row) && is_numeric($this->c_founded_rows)
            ? $this->start_row + $this->c_founded_rows
            : 0;
    }

    function set_last_row($val)
    {
        $this->last_row = is_numeric($val) && $val > 0 ? $val : 0;
    }

    function already_looked($ot_id, $id)
    {

        return is_array($_SESSION["looked_objects"][$ot_id]) && array_key_exists($id, $_SESSION["looked_objects"][$ot_id]) ? true : false;
    }

    function add2looked_objects($ot_id, $iid)
    {
        IF (is_numeric($ot_id) && is_numeric($iid)) {
            IF (!is_array($_SESSION["projects"][$this->prj_id]["looked_objects"][$ot_id]))
                $_SESSION["projects"][$this->prj_id]["looked_objects"][$ot_id] = array();

            $_SESSION["projects"][$this->prj_id]["looked_objects"][$ot_id][$iid] = $iid;
        }

    }

    function valid_query()
    {
        return $this->QM->isCompiled();
    }

    function get_seq()
    {

        IF (is_numeric($_REQUEST["seq"])) {
            $seq = $_REQUEST["seq"];
        } ELSEIF (is_numeric($this->seq) && $this->seq > 0) {
            $seq = $this->seq;
        } ELSE {
            $seq = false;
        }
        return $seq;
    }

    function set_seq($seq)
    {
        IF (is_numeric($seq) && $seq > 0) {
            IF (is_numeric($this->count_v) && $seq > $this->count_v) {
                $seq = $this->count_v;
            } ELSEIF (!is_numeric($this->count_v)) {
                $seq = 1;
            }

            $this->seq = $seq;
            return true;

        } ELSE {
            return false;
        }
    }

    function refreshOrder()
    {

        if ($this->isOrderRefreshed) {
            goto orderfilled;
        }

        $this->isOrderRefreshed = true;

        if (is_array($this->_existsOrder) && count($this->_existsOrder)) {
            foreach ($this->_existsOrder as $alias => $orderData) {
                $this->QM->addPreparedOrder($orderData, $alias);
            }
        }

        if (isset($this->request['o']) && is_array($this->request['o'])) {
            foreach ($this->request['o'] as $field => $flag) {
                if ($flag == '!') {
                    $this->QM->removeOrderByFields(array($field));
                    continue;
                }
                $this->QM->addOrder(array($field => $flag));
            }
        }

        $orderFilled = is_array($this->QM->getOrder()) && count($this->QM->getOrder());

        if (!$orderFilled && $this->_existsOrder === null && count($this->defaultOrder)) {
            $this->defaultOrderApplied = true;
            foreach ($this->defaultOrder as $k => $dOrder) {
                $this->QM->addPreparedOrder($dOrder);
            }
        }
        orderfilled:
        $this->_existsOrder = $this->QM->getOrder();
        return $this->_existsOrder;
    }

    function isDefaultOrderApplied()
    {
        return $this->defaultOrderApplied;
    }

    function addDefaultOrder($attrs, $vault = false)
    {
        $prepared = $this->QM->prepareOrderData($attrs, false, $vault);
        foreach ($prepared as $k => $v) {
            $this->defaultOrder[$k] = $v;
        }
    }

    function refreshSelected()
    {
        if (is_array($_POST['selected'][$this->ot_id]) && count($_POST['selected'][$this->ot_id]) > 0) {
            $this->add_to_selected($_POST['selected']);
        }
        if (is_array($_POST['unselected'][$this->ot_id]) && count($_POST['unselected'][$this->ot_id]) > 0) {
            $this->remove_from_selected($_POST['unselected']);
        }
    }

    function add_to_selected($array_to_add)
    {
        $r = array();
        \Verba\reductionToArray($array_to_add);
        if (!is_array($array_to_add)) {
            return $r;
        }
        foreach ($array_to_add as $ot => $items) {
            if (!is_array($items)) {
                continue;
            }
            if (!isset($this->selected[$ot])) {
                $this->selected[$ot] = array();
            }
            $r[$ot] = array();
            foreach ($items as $iid) {
                $this->selected[$ot][$iid] =
                $r[$ot][$iid] = $iid;
            }
        }
        return $r;
    }

    function remove_from_selected($array_to_remove)
    {
        $r = array();
        \Verba\reductionToArray($array_to_remove);
        if (!is_array($array_to_remove)) {
            return $r;
        }
        foreach ($array_to_remove as $ot => $iids) {
            if (!isset($this->selected[$ot])
                || !is_array($iids)) {
                $r[$ot] = $iids;
                continue;
            }
            foreach ($iids as $iid) {
                if (!isset($this->selected[$ot][$iid])) {
                    $r[$ot][$iid] = $iid;
                    continue;
                }
                unset($this->selected[$ot][$iid]);
                $r[$ot][$iid] = $iid;
            }
            if (!count($this->selected[$ot])) {
                unset($this->selected[$ot]);
            }
        }
        return $r;
    }

    function isSelected($iid, $ot = false)
    {
        if (!$ot) {
            $ot = $this->ot_id;
        }

        return isset($this->selected[$ot][$iid]);
    }

    function getSelected($ot = null)
    {
        if ($ot) {
            return isset($this->selected[$ot]) ? $this->selected[$ot] : array();
        }

        return $this->selected;
    }

    function getSelectedCount($ot = null)
    {
        if ($ot) {
            return isset($this->selected[$ot]) ? count($this->selected[$ot]) : null;
        }
        $r = 0;
        foreach ($this->selected as $ot => $iids) {
            $r += count($iids);
        }
        return $r;
    }
}
