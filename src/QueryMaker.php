<?php
namespace Verba;

use Verba\DBDriver\mysql\Result;

class QueryMaker extends Base
{
    protected $ot_id;
    private $key_id;
    /**
     * @var Model
     */
    protected $oh;
    protected $primDb;
    protected $primTable;
    protected $vaults_aliases;
    protected $aliases_vaults;
    private $union = false;
    private $count = false;
    private $obl_added = false;
    private $force_sys_fields = true;
    private $select_as_is = false;
    private $attributes = array();
    private $select_prop = array();
    private $all_lc = false;
    private $lc;

    private $f_selectPdv = true;
    private $f_multipleAttrExists = false;

    private $select = array();
    protected $table_aliases = array();
    private $from = array();
    private $join = array();

    private $complex_join = array();
    public $where = array();
    private $limit = array();
    private $order = array();
    private $group_by = array();

    private $conditions = array();
    private $conditionsAliases = array();
    protected static $aliases_range = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'aa', 'ba', 'ca', 'da', 'ea', 'fa', 'ga', 'ha', 'ia', 'ja', 'ka', 'la', 'ma', 'na', 'oa', 'pa', 'qa', 'ra', 'sa', 'ta', 'ua', 'va', 'wa', 'xa', 'ya', 'za', 'ab', 'bb', 'cb', 'db', 'eb', 'fb', 'gb', 'hb', 'ib', 'jb', 'kb', 'lb', 'mb');

    public $compiledSelect = false;
    public $compiledSelectProp = false;
    public $compiledFrom = false;
    public $compiledWhere = false;
    public $compiledJoin = false;
    public $compiledCJoin = false;
    public $compiledJoinParts = array();
    public $compiledLimit = false;
    public $compiledOrder = false;
    public $compiledGroupBy = false;
    protected $compiledParts = false;
    public $query;
    public $st;
    public $orderSubst = array();

    function __construct($oh, $key_id = false, $attr_list = true, $allLangs = false, $selectPdv = null, $primaryVault = false)
    {

        $this->oh = \Verba\_oh($oh);

        $this->ot_id = $this->oh->getID();
        $this->key_id = !is_numeric($key_id) ? $this->oh->getBaseKey() : $key_id;
        $this->setAllLangs($allLangs);
        if ($selectPdv !== null) {
            $this->setSelectPdv($selectPdv);
        }
        $dv = $this->oh->getVault();
        if (!is_object($dv)) {
            throw new Exception("QueryMaker: Can't get vaults for OT[" . $this->oh->getCode() . "] ot_id[" . $this->oh->getId() . "]");
        }
        $this->primDb = $dv->getRoot();
        $this->primTable = $dv->getObject();

        $this->normalizeVault($primaryVault);

        $this->addAttr($attr_list);
    }

    function set_ot($ot_id)
    {
        if (($ot_id = intval($ot_id)) && $ot_id > 0) {
            $this->ot_id = $ot_id;
            return true;
        } else {
            $this->log()->stopScript("Query generation error: OT id incorrect." . __METHOD__ . " (" . __LINE__ . ")] \$ot_id=[" . $ot_id . "]");
        }
        return null;
    }

    function get_ot_id()
    {
        return $this->ot_id;
    }

    function setSelectPdv($val)
    {
        $this->f_selectPdv = (bool)$val;
    }

    function getSelectPdv()
    {
        return $this->f_selectPdv;
    }

    function setAllLangs($val)
    {
        if (is_string($val) &&  \Verba\Lang::isLCValid($val)) {
            $this->all_lc = false;
            $this->lc = $val;
        } else {
            $this->all_lc = (bool)$val;
            $this->lc = SYS_LOCALE;
        }

        return $this->lc;
    }

    function setForceSysFields($bool)
    {
        $this->force_sys_fields = is_bool($bool) ? $bool : true;
    }

    function ForceSysFields()
    {
        return $this->force_sys_fields;
    }

    function addSysField()
    {
        if (!$this->count && !$this->obl_added && $this->ForceSysFields()) {
            // Добавление обязательных полей
            $this->addSelect('ot_id');
            $this->addSelect('key_id');
            $this->addSelect($this->oh->getPAC());
            // Установка переключателя
            $this->obl_added = true;
        }
        return $this->obl_added;
    }

    function setSelectAsIs($bool)
    {
        $this->select_as_is = is_bool($bool) ? $bool : false;
    }

    function addSelectProp($prop)
    {
        if (!is_array($prop) && !settype($prop, 'array'))
            return false;

        foreach ($prop as $prop_value)
            $this->select_prop[$prop_value] = $prop_value;
        return true;
    }

    function compiledSelectProp()
    {
        $this->compiledSelectProp = "";

        if (count($this->select_prop) == 0)
            return false;

        foreach ($this->select_prop as $prop)
            $this->compiledSelectProp .= $prop . " ";

        return null;
    }

    function getDb()
    {
        return reset($this->from) ? $this->from[key($this->from)]['db'] : false;
    }

    function getAlias()
    {
        return reset($this->from) ? key($this->from) : false;
    }

    function getTable()
    {
        return reset($this->from) ? $this->from[key($this->from)]['table'] : false;
    }

    function getUri()
    {
        return reset($this->from) ? $this->from[key($this->from)]['uri'] : false;
    }

    function addWhereIids($iids, $table = false, $INStatement = true, $negation = false)
    {
        $where_by_ids = $this->makeWhereIids($iids, $table, $INStatement, $negation);
        if (!empty($where_by_ids)) {
            return $this->addWhere($where_by_ids);
        }
        return false;
    }

    public function makeWhereIids($iids, $table = false, $INStatement = true, $negation = false)
    {
        $output = '';
        $negation = (bool)$negation;

        if (!settype($iids, 'array')) {
            return false;
        }
        $table = !is_string($table) ? $this->getAlias() : $table;

        $numIdField = '`' . \Verba\esc($this->oh->getPAC()) . '`';
        $strIdField = $this->oh->getStringPAC();
        if (is_string($strIdField)) {
            $strIdField = '`' . \Verba\esc($strIdField) . '`';
        }

        if (is_string($table) && !empty($table)) {
            $numIdField = '`' . \Verba\esc($table) . '`.' . $numIdField;
            if (is_string($strIdField)) {
                $strIdField = '`' . \Verba\esc($table) . '`.' . $strIdField;
            }
        }
        $not = !($negation)
            ? ''
            : ($INStatement ? 'NOT' : '!');
        // field IN(value0, value1, value2) case
        if ($INStatement) {
            $numPart = '';
            $strPart = '';
            foreach ($iids as $iid) {
                if (is_numeric($iid)) {
                    $numPart .= ", '" . $iid . "'";
                } elseif (is_string($iid)) {
                    $strPart .= ", '" . \Verba\esc($iid) . "'";
                }
            }

            if (!empty($numPart)) {
                $output = ' ' . $numIdField . ' ' . $not . ' IN (' . mb_substr($numPart, 1) . ') ';
            }
            if (!empty($strPart)) {
                if (empty($output)) { //numpart exists
                    $output = ' ' . $strIdField . ' ' . $not . ' IN (' . mb_substr($strPart, 1) . ') ';
                } else {
                    $output = '( '
                        . $output // num part
                        . ' ' . ($negation ? '&&' : '||') // connect btw num and str parts
                        . ' ' . $strIdField . ' ' . $not . ' IN (' . mb_substr($strPart, 1) . ') )'; // str part
                }
            }
            // ( field = value || field = value2 || ...) case
        } else {
            foreach ($iids as $iid) {
                $output .= '|| ' . (is_numeric($iid) ? $numIdField : $strIdField) . ' ' . $not . "= '" . \Verba\esc($iid) . "'";
            }
            $output = !empty($output) ? ' (' . mb_substr($output, 3) . ') ' : false;
        }

        return !empty($output) ? $output : false;
    }

    function addFrom($table, $db = false, $alias = false)
    {
        list($alias, $table, $db) = $this->createAlias($table, $db, $alias);

        if (!array_key_exists($alias, $this->from)) {
            $this->from[$alias] = array('db' => $db, 'table' => $table, 'uri' => '`' . $db . '`.`' . $table . '`');
        }

        return array($table, $db, $alias);
    }

    function compileFrom()
    {
        if (!is_array($this->from)) {
            throw new Exception("Empty 'from'");
        }
        $this->compiledFrom = array();
        foreach ($this->from as $alias => $vault) {
            $this->compiledFrom[$alias] = $vault['uri'] . ' ' . $alias;
        }
    }

    /**
     * @param mixed $table
     * @param mixed $db
     * @param QueryMaker $alias
     * @return array($alias, $table, $db)
     */
    function createAlias($table = false, $db = false, $alias = false)
    {
        if (!$db || !is_string($db)) $db = $this->primDb;
        if (!$table || !is_string($table)) {
            if (is_string($alias) && isset($this->aliases_vaults[$alias])) {
                $table = $this->aliases_vaults[$alias]['t'];
            } else {
                $table = $this->primTable;
            }
        }

        $dbtable = $db . '.' . $table;
        if (!isset($this->vaults_aliases[$dbtable])) {
            $this->vaults_aliases[$dbtable] = array();
        }
        if (!is_string($alias)) {
            if (isset($this->vaults_aliases[$dbtable][0])) {
                return array($this->vaults_aliases[$dbtable][0], $table, $db);
            }
            $alias = self::$aliases_range[count($this->vaults_aliases)];
            $this->vaults_aliases[$dbtable][] = $alias;
            $this->aliases_vaults[$alias] = array('d' => $db, 't' => $table);
            return array($alias, $table, $db);
        }

        if (false === array_search($alias, $this->vaults_aliases[$dbtable])) {
            $this->vaults_aliases[$dbtable][] = $alias;
            $this->aliases_vaults[$alias] = array('d' => $db, 't' => $table);
        }
        return array($alias, $table, $db);
    }

    function addAttr($attr_list)
    {
        $oh = $this->oh;
        $ot_id = $oh->getID();

        if (!is_array($attr_list = $oh->getAttrs($attr_list, false, array('not_selectable'), array('s')))) {
            return false;
        }

        if (!array_key_exists($ot_id, $this->attributes)) {
            $this->attributes[$ot_id] = array();
        }

        $needToAdd = array_diff_key($attr_list, $this->attributes[$ot_id]);

        foreach ($needToAdd as $key => $v) {
            $this->attributes[$ot_id][$key] = $v;
            $this->attr2field($ot_id, $v);
        }

        return $attr_list;
    }

    private function attr2field($ot_id, $attr_code)
    {

        $oh = \Verba\_oh($ot_id);
        $A = $oh->A($attr_code);
        $attr_id = $A->getID();

        list($alias, $table, $db) = $this->createAlias();
        $this->addSelect($attr_code, array($table, $db, $alias));
        if ($this->f_selectPdv) {
            // If its predefined attribute
            if ($oh->in_behavior('predefined', $attr_id)
                && is_object($PdSet = $A->PdSet($oh))) {
                $pdset_vault = $PdSet->vault;
                list($jalias, $jtable, $jdb) = $this->createAlias($pdset_vault['object'], $pdset_vault['root'], 'pred_' . $attr_code);
                // add join for multiples data type
                if ($A->data_type == 'multiple') {

                    if (!isset($this->complex_join['multiple'])) {
                        list($am) = $this->addMultipleJoin();
                        $this->f_multipleAttrExists = true;
                    } else {
                        list($am) = $this->createAlias('attr_multiples');
                    }
                    $this->addCJoin(array(array('a' => $jalias)),
                        array(
                            array('p' => array('a' => $jalias, 'f' => 'pred_id'),
                                's' => array('a' => $am, 'f' => 'var_id')
                            ),
                            array('p' => array('a' => $am, 'f' => 'attr_id'),
                                's' => $attr_id
                            ),
                        ), true
                    );
                    $this->addSelect("GROUP_CONCAT(DISTINCT `" . $jalias . "`.`pred_id`,':',`" . $jalias . "`.`value_" . SYS_LOCALE . "` SEPARATOR '#')", false, $attr_code . "__value", true);
                    // add join for single-value predefined
                } else {
                    $this->addJoin($jtable, $jdb, $jalias, 'pred_id', $alias, $attr_code, true);
                    $this->addSelectPastFrom('value_' . SYS_LOCALE, array($jtable, $jdb, $jalias), $attr_code . '__value');
                }
            }

            // Подстановка foreign_id
            if ($oh->in_behavior('foreign_id', $attr_id)) {
                $ath = $A->getHandlers('present');
                if (is_array($ath)) {
                    foreach ($ath as $prop) {
                        if (!is_array($prop['params']) || !isset($prop['params']['ot_id']) || !isset($prop['params']['field2display'])) {
                            continue;
                        }

                        if (!\Verba\isOt($prop['params']['ot_id'])) {
                            $this->log()->error('Bad ot for attr handler. $ath [' . var_export($ath, true) . ']');
                            continue;
                        }

                        $_foh = \Verba\_oh($prop['params']['ot_id']);
                        $fA = $_foh->A($prop['params']['field2display']);

                        $f_dv = $_foh->getVault();
                        list($falias, $ftable, $fdb) = $this->createAlias($f_dv->getObject(), $f_dv->getRoot(), '_fid_' . $attr_code);
                        //if(!$this->isJoined($alias, $attr_code, $ftable, $_foh->getPAC())) {
                        $this->addJoin($ftable, $f_dv->getRoot(), $falias, $_foh->getPAC(), $alias, $attr_code, true);
                        //}
                        if ($fA->isLcd()) {
                            if ($this->all_lc) {
                                foreach (Lang::getUsedLC() as $lc) {
                                    $this->addSelectPastFrom($fA->getCode() . '_' . $lc, array($ftable, $fdb, $falias), $attr_code . '__value_' . $lc, null, $_foh);
                                }
                            } else {
                                $this->addSelectPastFrom($fA->getCode() . '_' . $this->lc, array($ftable, $fdb, $falias), $attr_code . '__value', null, $_foh);
                            }
                        } else {
                            $this->addSelectPastFrom($fA->getCode(), array($ftable, $fdb, $falias), $attr_code . '__value', null, $_foh);
                        }
                    }
                }
            }
        }

        return true;
    }

    function addMultipleJoin()
    {
        list($a) = $this->createAlias();
        list($am, $t, $db) = $this->createAlias('attr_multiples', SYS_DATABASE, '_am');
        $this->addCJoin(array(array('a' => $am, 't' => $t, 'db' => $db)),
            array(
                array('p' => array('a' => $am, 'f' => 'ot_id'),
                    's' => $this->oh->getID()),
                array('p' => array('a' => $am, 'f' => 'iid'),
                    's' => array('a' => $a, 'f' => $this->oh->getPAC())),
            ), true, 'multiple');
        return array($am, $t, $db);
    }

    function addSelect($field, $vault = false, $falias = false, $asIs = false, $oh = null, $addToFrom = true)
    {
        if (!is_string($field) || $field == '')
            return false;

        if ($oh === false) {
            $is_lcd = false;
        } else {
            $oh = is_object($oh) && $oh instanceof \Verba\Model
                ? $oh
                : $this->oh;

            $is_lcd = is_object($A = $oh->A($field)) ? $A->isLcd() : false;
        }

        $db = $table = $talias = false;
        if (is_string($vault)) {

            if (array_key_exists($vault, $this->aliases_vaults)) {
                $talias = $vault;
                $table = false;
            } else {
                $table = $vault;
            }
        } elseif (is_array($vault) && count($vault)) {
            if (isset($vault[0])) $table = $vault[0];
            if (isset($vault[1])) $db = $vault[1];
            if (isset($vault[2])) $talias = $vault[2];
        } elseif ($vault === null) {
            list($talias, $table, $db) = $this->createAlias();
        }

        if (!$talias || !$table) {
            list($talias, $table, $db) = $this->createAlias($table, false, $talias);
        }

        if ($addToFrom) {
            list($table, $db, $talias) = $this->addFrom($table, $db, $talias);
        } elseif (!is_string($talias)) {
            $talias = $table;
        }

        $q_field = $is_lcd
            ? $field . '_' . $this->lc
            : $field;

        if (!is_string($falias) || empty($falias))
            $falias = $field;

        if (!$talias || !$falias) {
            return false;
        }

        if (!isset($this->select[$talias][$falias]) || !is_array($this->select[$talias][$falias])) {
            $this->select[$talias][$falias] = array('f' => $q_field, 'asIs' => $asIs, 'lcd' => $is_lcd);
        }

        if ($is_lcd && $this->all_lc && is_array(Lang::getUsedLC())) {
            foreach (Lang::getUsedLC() as $lc) {
                $this->select[$talias][$falias . '_' . $lc] = array('f' => $field . '_' . $lc, 'asIs' => false, 'lcd' => false);
            }
        }

        return true;
    }

    function addSelectPastFrom($field, $vault = null, $falias = null, $asIs = null, $oh = false)
    {
        $this->addSelect($field, $vault, $falias, $asIs, $oh, false);
    }

    function compileSelect()
    {

        $this->compiledSelectProp();
        $this->compiledSelect = '';

        if ($this->count) {
            $this->compiledSelect = ' COUNT(*) as `count_rows` ';
            return $this->compiledSelect;
        }

        if (!is_array($this->select) || count($this->select) < 1) {
            throw new Exception('Query generation error: fields list is empty.');
        }

        foreach ($this->select as $table => $fields) {
            $table_part = '`' . $table . '`.';

            if (!is_array($fields) || !count($fields)) {
                continue;
            }

            foreach ($fields as $alias => $field_data) {
                $alias_p = $alias == $field_data['f'] ? '' : " as `$alias`";
                if ($this->select_as_is == true || (isset($field_data['asIs']) && $field_data['asIs'] === true)) {
                    $this->compiledSelect .= ", \n" . $field_data['f'] . $alias_p;
                } else {
                    $this->compiledSelect .= ", \n" . $table_part . '`' . $field_data['f'] . '`' . $alias_p;
                }
            }

        }
        $this->compiledSelect = substr($this->compiledSelect, 1);
        return $this->compiledSelect;
    }

    function removeSelect($field, $talias = false, $alias = false)
    {
        if (!is_string($field) || empty($field))
            return false;

        if (!is_string($talias) || empty($talias))
            $talias = $this->getAlias();

        if (!is_string($alias) || empty($alias))
            $alias = $field;

        if (isset($this->select[$talias][$alias])) {
            unset($this->select[$talias][$alias]);
        }

        return true;
    }

    function addHaving($having, $connector = "&&")
    {
        $this->having[] = array($having, $connector);
    }

    function compileHaving()
    {
        $this->compiledHaving = '';
        if (is_array($this->having) && count($this->having)) {
            $full_having_str = '';

            foreach ($this->having as $key => $c_having) {
                $full_having_str .= is_string($c_having[1]) && !empty($c_having[1]) ? ' ' . $c_having[1] . ' ' : ' || ';
                $full_having_str .= ' ' . $c_having[0] . ' ';
            }

            $this->compiledHaving = substr($full_having_str, 3);
        }
        return true;
    }

    function addLimit($n, $start = false)
    {
        if (is_numeric($n) || is_numeric($start)) {

            $this->limit = array('start' => is_numeric($start) && $start > 0 ? $start : '0',
                'n' => is_numeric($n) && $n > 0 ? $n : ''
            );
            return true;
        }
        return false;
    }

    function compileLimit()
    {
        $this->compiledLimit = '';
        if (is_array($this->limit) && !$this->count) {
            if (is_numeric($this->limit['start']) && $this->limit['start'] > 0) {
                $this->compiledLimit .= ' ' . $this->limit['start'];
            }

            if (is_numeric($this->limit['n']) && $this->limit['n'] > 0) {
                $this->compiledLimit .= !empty($this->compiledLimit) ? ', ' . $this->limit['n'] : ' ' . $this->limit['n'];
            }
        }
    }

    function getLimit()
    {
        return $this->limit;
    }

    function addJoin($jTable, $jDB, $jTAlias = false, $jField, $srcTable, $srcField, $count_free = false, $operator = '=', $jType = 'LEFT')
    {

        list($jTAlias, $jTable, $jDB) = $this->createAlias($jTable, $jDB, $jTAlias);
        $index = $srcTable . '.' . $srcField . '.' . $jTAlias . '.' . $jField;
        $this->join[$jType][$index] = array('jTable' => $jTable,
            'jDB' => $jDB,
            'jTAlias' => $jTAlias,
            'jField' => $jField,
            'srcTable' => $srcTable,
            'srcField' => $srcField,
            'operator' => $operator,
            'count_free' => $count_free
        );
    }

//  function isJoined($srcTable, $srcField, $jTAlias, $jField, $jType = 'LEFT'){
//    $jType = strtoupper($jType);
//    $index = $srcTable.'.'.$srcField.'.'.$jTAlias.'.'.$jField;
//    // ищем в симпл-джойне
//    $joinExists =  array_key_exists($jType, $this->join)
//      && array_key_exists($index, $this->join[$jType])
//      && is_array($this->join[$jType][$index])
//      && count($this->join[$jType][$index]);
//    if($joinExists){
//      return $joinExists;
//    }
//    // ищем в комплекс-джойне
//    if(is_array($this->complex_join) && count($this->complex_join)){
//      foreach($this->complex_join as $ji => $join){
//        if($join['jt']['table'] == '')
//      }
//    }
//    return $joinExists;
//  }
    function compileJoin()
    {
        $this->compiledJoin = '';

        if (!count($this->join)) {
            return;
        }
        foreach ($this->join as $jType => $cTypeJoins) {
            foreach ($cTypeJoins as $c_join) {
                if ($this->count == true && $c_join['count_free'] == true) {
                    continue;
                }
                $this->compiledJoin .= "\n" . $jType . ' JOIN ';
                $this->compiledJoin .= is_string($c_join['jDB']) && !empty($c_join['jDB']) ? '`' . $c_join['jDB'] . '`.' : '';
                $this->compiledJoin .= $c_join['jTAlias'] == $c_join['jTable'] ? '`' . $c_join['jTable'] . '`' : '`' . $c_join['jTable'] . '` as `' . $c_join['jTAlias'] . '`';
                $this->compiledJoin .= ' ON ' . ((is_string($c_join['srcTable']) && !empty($c_join['srcTable'])) ? '`' . $c_join['srcTable'] . '`' : $this->getUri()) . '.`' . $c_join['srcField'] . '` ' . $c_join['operator'] . ' `' . $c_join['jTAlias'] . '`.`' . $c_join['jField'] . '`';
            }
        }
    }

    /**
     *
     * @param array $Tables array('a'=>alias, 't'=>table 'd'=> database)
     * @param array $Conditions array(array(
     *     'p' => array('t'=> [table|alias], 'f' => [field]), // left statement
     *     ['o' => compare operand,
     *     'g'=> condition glue,
     *     's' => [...] // right (secondary) statement
     *     'asIs' => 0 | 1  - as is]
     *    ),
     * @param bool $count_free
     * @param string|bool $alias
     * @param string $jType
     * @param string $sign
     *
     */
    function addCJoin($Tables, $Conditions, $count_free = false, $alias = null, $jType = 'LEFT', $sign = null)
    {

        $complex_table = '';
        $jt = array();
        foreach ($Tables as $table) {
            list($jTAlias, $jTable, $jDb) = $this->createAlias($table['t'], $table['d'], $table['a']);
            $complex_table .= ', `' . $jDb . '`.`' . $jTable . '` as `' . $jTAlias . '`';

            $jt['alias'] = $jTAlias;
            $jt['table'] = $jTable;
            $jt['db'] = $jDb;
        }
        $complex_table = substr($complex_table, 1);

        $complex_on = '';

        foreach ($Conditions as $c_data) {

            list($falias, $ftable) = $this->createAlias(
                isset($c_data['p']['t']) ? $c_data['p']['t'] : false,
                isset($c_data['p']['d']) ? $c_data['p']['d'] : false,
                isset($c_data['p']['a']) ? $c_data['p']['a'] : false
            );

            $fStatement = $falias . '.`' . $c_data['p']['f'] . '`';

            if (is_array($c_data['s'])) {
                list($salias, $stable) = $this->createAlias(
                    isset($c_data['s']['t']) ? $c_data['s']['t'] : false,
                    isset($c_data['s']['d']) ? $c_data['s']['d'] : false,
                    isset($c_data['s']['a']) ? $c_data['s']['a'] : false);

                $sStatement = $salias . '.`' . $c_data['s']['f'] . '`';
            } elseif (is_string($c_data['s']) || is_numeric($c_data['s'])) {
                if (isset($c_data['asis']) && $c_data['asis'] == true) {
                    $sStatement = $c_data['s'];
                } else {
                    $sStatement = '\'' . $this->DB()->escape_string($c_data['s']) . '\'';
                }
            }

            $glue = !isset($c_data['g']) ? '&&' : (string)$c_data['g'];
            $operator = !isset($c_data['o']) ? '=' : (string)$c_data['o'];

            $complex_on .= ' ' . $glue . ' ' . $fStatement . ' ' . $operator . ' ' . $sStatement;
        }
        $complex_on = substr($complex_on, 3);
        $d = array(
            'tables' => $complex_table,
            'conditions' => $complex_on,
            'count_free' => $count_free,
            'type' => $jType,
            'sign' => isset($sign) && !empty($sign) ? strtolower($sign) : '',
            'jtable' => $jt,
        );
        if (is_string($alias)) {
            $this->complex_join[$alias] = $d;
        } else {
            $this->complex_join[] = $d;
        }
    }

    function compileCJoinEntry($c_join)
    {
        return
            "\n" . $c_join['type'] . ' JOIN ' . $c_join['tables']
            . "\r\n ON " . $c_join['conditions'];
    }

    function compileCJoin()
    {

        $this->compiledCJoin = '';

        if (!is_array($this->complex_join) || !count($this->complex_join) > 0) {
            return false;
        }
        foreach ($this->complex_join as $c_join) {
            if ($this->count == true && $c_join['count_free'] == true) {
                continue;
            }
            $type = strtolower($c_join['type']);
            $str = $this->compileCJoinEntry($c_join);
            $this->compiledCJoin .= $str;
        }
    }

    function compileJoinBySign($sign)
    {

        if (!is_array($this->complex_join) || !count($this->complex_join) > 0) {
            return false;
        }

        $sign = strtolower($sign);
        $str = '';
        foreach ($this->complex_join as $c_join) {
            if ($c_join['sign'] != $sign) {
                continue;
            }
            $str .= $this->compileCJoinEntry($c_join);
        }
        return $str;
    }

    function removeCJoin($alias)
    {
        if (isset($this->complex_join[$alias])) {
            unset($this->complex_join[$alias]);
            return true;
        }
        return false;
    }

    /**
     * Добавление условий выборки
     *
     * @param string $value Значение поля. В режиме asis должно содержать полный синтаксис where подусловия например myfield = 'condition'
     * @param string|false $alias Задает алиас условия по которому в будущем можно к нему обратиться. Передав false - включает режим asis
     * @param string|false $field Название поля. В режиме asis задаст будет использован как алиас условия. Если передан false - полем будет $alias
     * @param string|false $vault Ваулт в формате.
     * @param string $operator Оператор сравнения.
     * @param string $connector Условие соединения с другими частями where.
     */
    function addWhere($value, $alias = false, $field = false, $vault = false, $operator = '=', $connector = "&&")
    {
        $args = func_get_args();
        list($alias, $data) = call_user_func_array(array($this, 'createWhereData'), $args);
        $this->where[$alias] = $data;
    }

    function createWhereData($value, $alias = false, $field = false, $vault = false, $operator = '=', $connector = "&&")
    {

        $data = array(
            'v' => (string)$value,
            'conn' => $connector,
            'cmp' => $operator,
            'f' => $field,
            'vault' => $vault,
            'asIs' => false
        );

        if (!is_string($alias)) {
            $data['asIs'] = true;
            if (is_string($field)) {
                $data['f'] = false;
                $alias = $field;
            } else {
                $alias = count($this->where);
            }
        } else {
            $field = is_string($field) ? $field : $alias;
            $data['f'] = $field;
        }
        return array($alias, $data);
    }

    function removeWhere($alias)
    {
        if (!is_string($alias) || !array_key_exists($alias, $this->where)) {
            return false;
        }
        unset($this->where[$alias]);
    }

    function getWhere($alias = false)
    {
        return is_string($alias) && isset($this->where[$alias])
            ? $this->where[$alias]
            : (!$alias
                ? $this->where
                : null);
    }

    /**
     * Returns interface to add group of where conditions inserted into ()
     *
     * @return \Verba\QueryMaker\WhereGroup
     */
    function addWhereGroup($alias = false, $conn = '&&')
    {
        if (!is_string($alias)) {
            $alias = '_g' . count($this->where);
        }
        $WG = new \Verba\QueryMaker\WhereGroup($this, $alias, $conn);
        $this->where[$alias] = $WG;
        return $WG;
    }

    function compileWhere()
    {
        $r = '';
        if (!count($this->where)) {
            return $r;
        }

        foreach ($this->where as $key => $c_where) {
            if (is_array($c_where)) {
                $r .= $this->compileWhereItem($c_where);
            } elseif (is_object($c_where)) {
                $r .= $c_where->compile();
            }
        }
        $r = substr($r, 3);

        return $r;
    }

    function compileWhereItem($where)
    {
        $wval = '';
        $table = $db = $talias = false;
        if (is_string($where['f']) && !empty($where['f'])) { // если указано поле

            if ($where['asIs']) {

                $talias = $where['vault'];

            } else {

                if (is_string($where['vault'])) {
                    $where['vault'] = array(2 => $where['vault']);
                }
                if (is_array($where['vault']) && count($where['vault'])) {
                    if (isset($where['vault'][0])) $table = $where['vault'][0];
                    if (isset($where['vault'][1])) $db = $where['vault'][1];
                    if (isset($where['vault'][2])) $talias = $where['vault'][2];
                }
                list($talias) = $this->createAlias($table, $db, $talias);

            }

            if (is_string($talias) && !empty($talias)) {
                $wval = '`' . $talias . '`.';
            }

            $wval .= '`' . $where['f'] . '` ' . $where['cmp'];
            if (is_string($where['v'])) {
                $wval .= ' \'' . $this->DB()->escape_string($where['v']) . '\'';
            }
        } else {
            $wval = $where['v'];
        }
        return ' ' . $where['conn'] . ' ' . $wval;
    }

    function getCompiledWhere()
    {
        return $this->compiledWhere;
    }

    function prepareOrderData($fields, $alias = false, $vault = false)
    {
        $vault = $this->normalizeVault($vault);
        $r = array();
        foreach ($fields as $field => $data) {
            $priority = 0;
            if (is_string($data)) {
                $direction = $data;
            } elseif (is_array($data)) {
                $direction = array_key_exists('direction', $data) ? $data['direction'] : null;
                if (array_key_exists('priority', $data)) {
                    $priority = intval($data['priority']);
                }
            } else {
                continue;
            }
            list($field, $vaultByField) = $this->fieldToOrderName($field, $vault);
            $key = is_string($alias) ? $alias : $this->buildOrderAlias($field, $vaultByField);
            $r[$key] = array(
                'type' => $direction == 'a' || $direction == 'd' ? $direction : 'a',
                'priority' => isset($priority) && is_int($priority) ? $priority : 0,
                'field' => $field,
                'vault' => $vaultByField,
            );
        }
        return $r;
    }

    function addOrder($fields, $alias = false, $vault = false)
    {
        // as-is
        if (is_string($fields) && !empty($fields)) {
            $this->order['_s' . rand()] = $fields;
        }
        if (!is_array($fields) || !count($fields)) {
            return array();
        }

        $prepared = $this->prepareOrderData($fields, $alias, $vault);
        foreach ($prepared as $k => $d) {
            $this->order[$k] = $d;
        }
        return $prepared;
    }

    function addPreparedOrder($preparedData, $alias = false)
    {
        if (!is_array($preparedData)
            || !array_key_exists('type', $preparedData)
            || !array_key_exists('field', $preparedData)
            || !array_key_exists('vault', $preparedData)
            || !array_key_exists('priority', $preparedData)
        ) {
            return false;
        }
        $alias = is_string($alias) ? $alias : $this->buildOrderAlias($preparedData['field'], $preparedData['vault']);
        $this->order[$alias] = $preparedData;
        return true;
    }

    function addStandartOrder()
    {
        if (!$this->oh) {
            return false;
        }
        if ($this->oh->isA('priority')) {
            $order['priority'] = 'd';
        }
        $order[$this->oh->getPAC()] = 'd';

        return $this->addOrder($order);
    }

    function removeOrderByFields($fields, $vault = false)
    {
        if (!is_array($fields) || !count($fields)) {
            return false;
        }
        $vault = $this->normalizeVault($vault);

        foreach ($fields as $field) {
            list($field, $vaultByField) = $this->fieldToOrderName($field, $vault);
            $key = $this->buildOrderAlias($field, $vaultByField);
            if (isset($this->order[$key])) {
                unset($this->order[$key]);
            }
        }
    }

    function removeOrder($alias)
    {
        if (!array_key_exists($alias, $this->order)) return false;
        unset($this->order[$alias]);
    }

    function fieldInOrder($field, $vault = false)
    {
        $vault = $this->normalizeVault($vault);
        list($field, $vault) = $this->fieldToOrderName($field, $vault);
        $key = $this->buildOrderAlias($field, $vault);
        return array_key_exists($key, $this->order);
    }

    function compileOrder()
    {
        $this->compiledOrder = '';
        if (!is_array($this->order) || !count($this->order)) {
            return false;
        }
        $orderedOrder = $this->order;
        usort($orderedOrder, 'self::sortOrderByPriority');
        foreach ($orderedOrder as $field_path => $data) {
            if (is_string($data)) {
                $this->compiledOrder .= ', ' . $data;
                continue;
            }
            if (array_key_exists('asis', $data) && $data['asis']) {
                $this->compiledOrder .= ', ' . $data;
                continue;
            }

            $direction = $data['type'] == 'a' ? 'ASC' : 'DESC';
            $vaultAlias = is_array($data['vault']) ? '`' . $data['vault'][2] . '`.' : '';
            $this->compiledOrder .= ', ' . $vaultAlias . '`' . $this->DB()->escape_string($data['field']) . '` ' . $direction;
        }
        $this->compiledOrder = mb_substr($this->compiledOrder, 1);
        return true;
    }

    private function fieldToOrderName($field, $vault)
    {
        if (array_key_exists($field, $this->orderSubst)) {
            $field = $this->orderSubst[$field][0];
            $vault = $this->orderSubst[$field][1];
        }

        $A = $this->oh->A($field);

        if ($A && $A->get_lcd()) {
            $field = $A->getCode() . '_' . SYS_LOCALE;
        } elseif ($A && $A->isPredefined()) {
            $field = $A->getCode() . '__value';
            $vault = false;
        } elseif ($A) {
            $field = $A->getCode();
        }
        return array($field, $vault);
    }

    function getFieldOrderType($field, $vault = false)
    {
        $vault = $this->normalizeVault($vault);
        list($field, $vault) = $this->fieldToOrderName($field, $vault);
        $key = $this->buildOrderAlias($field, $vault);
        return isset($this->order[$key]) ? $this->order[$key]['type'] : false;
    }

    function getOrder($alias = false)
    {
        return is_string($alias) ? (isset($this->order[$alias]) ? $this->order[$alias] : null) : $this->order;
    }

    function buildOrderAlias($field, $vault)
    {
        if (!is_string($field) || empty($field)) {
            return false;
        }

        return (is_array($vault) && array_key_exists(2, $vault)
                ? $vault[2] . '.'
                : '') . ((string)$field);
    }

    /**
     * @param $val
     * @return array|bool
     *
     * @desc $val array
     * [
     *   alias => [fieldName, vault],
     *   ...
     *  ]
     */
    function setOrderSubst($val)
    {
        if (!is_array($val)) {
            return false;
        }

        foreach ($val as $alias => $subst) {

            $vault = null;
            $field = null;

            if(is_string($subst)){
                $field = $subst;
                $vault = $this->createAlias();
            }elseif(is_array($subst)){
                if(isset($subst[0])){
                    $field = $subst[0];
                }
                if(isset($subst[1])){
                    $vault = $this->normalizeVault($subst[1]);
                }
            }

            if(!$field || !$vault){
                $this->log()->error('Unable to get vault or field for order substs');
                continue;
            }

            $this->orderSubst[$alias] = [$field, $vault];

            if ($A = $this->oh->A($alias)) {
                $this->orderSubst[$A->getCode()] =
                $this->orderSubst[$A->getId()] = &$this->orderSubst[$alias];
            }
        }

        return $this->orderSubst;
    }

    static function sortOrderByPriority($a, $b)
    {
        if ($a['priority'] == $b['priority']) {
            return 0;
        }
        return ($a['priority'] < $b['priority']) ? 1 : -1;
    }

    function addGroupBy($group, $vault = false)
    {
        if (!\Verba\reductionToArray($group)) {
            return false;
        }
        $table = $db = $alias = false;
        if (is_string($vault)) {
            $table = $vault;
        } elseif (is_array($vault)) {
            if (isset($vault[0])) $table = $vault[0];
            if (isset($vault[1])) $db = $vault[1];
            if (isset($vault[2])) $alias = $vault[2];
        }
        list($alias) = $this->createAlias($table, $db, $alias);
        foreach ($group as $field) {
            if (is_string($field) && !empty($field)) {
                $this->group_by[] = array('f' => $field, 'alias' => $alias);
            }
        }
        return true;
    }

    function compileGroupBy()
    {
        $this->compiledGroupBy = '';
        if (!is_array($this->group_by) || !count($this->group_by)) {
            if ($this->f_multipleAttrExists) {
                $this->addGroupBy($this->oh->getPAC());
            } else {
                return false;
            }
        }
        foreach ($this->group_by as $cgroup) {
            $this->compiledGroupBy .= ", `" . $cgroup['alias'] . "`.`" . $cgroup['f'] . "` ";
        }
        $this->compiledGroupBy = mb_substr($this->compiledGroupBy, 1);
        return true;
    }

    function setQuery()
    {
        $this->query = $this->compileQuery();
    }

    function makeQuery()
    {
        $this->query = $this->compileQuery();
    }

    protected function compileQuery()
    {

        //Подготовка. Добавление служебных полей
        if (!$this->obl_added && !$this->count) {
            $this->addSysField();
        }
        //Компиляция частей
        $this->compileParts();

        // Соединение частей в полный запрос

        return $this->union ? $this->glue_union_query() : $this->glue_plane_query();
    }

    function getQuery()
    {
        if (null === $this->query) {
            $this->makeQuery();
        }
        return $this->query;
    }

    function query()
    {
        return $this->query;
    }

    function isCompiled()
    {
        return $this->compiledParts;
    }

    function compileParts()
    {

        // Компилим Conditions
        $this->compileConditions();

        // Компилим SELECT
        $this->compileSelect();

        // Компилим FROM
        if (!$this->compiledFrom) {
            $this->compileFrom();
        }

        // Компилим JOIN

        $this->compileJoin();
        $this->compileCJoin();


        // Подстановка условия WHERE
        $this->compiledWhere = $this->compileWhere();

        // Подстановка условия HAVING
        $this->compileHaving();


        // Limit
        $this->compileLimit();

        // ORDER
        $this->compileOrder();

        // GROUP BY
        $this->compileGroupBy();


        $this->compiledParts = true;
    }

    function glue_plane_query()
    {

        $query = 'SELECT ' . $this->compiledSelectProp . $this->compiledSelect;

        $query .= "\nFROM " . implode(',', $this->compiledFrom);

        if (is_string($this->compiledJoin) && !empty($this->compiledJoin) /*&& !$this->count*/)
            $query .= $this->compiledJoin;

        if (is_string($this->compiledCJoin) && !empty($this->compiledCJoin) /*&& !$this->count*/)
            $query .= $this->compiledCJoin;

        if (is_string($this->compiledWhere) && !empty($this->compiledWhere))
            $query .= "\nWHERE " . $this->compiledWhere;

        if (!$this->count) {
            if (is_string($this->compiledGroupBy) && !empty($this->compiledGroupBy))
                $query .= "\nGROUP BY " . $this->compiledGroupBy;
        }

        if (is_string($this->compiledHaving) && !empty($this->compiledHaving))
            $query .= "\nHAVING " . $this->compiledHaving;

        if (!$this->count) {
            if (is_string($this->compiledOrder) && !empty($this->compiledOrder))
                $query .= "\nORDER BY" . $this->compiledOrder;

            if (is_string($this->compiledLimit) && !empty($this->compiledLimit))
                $query .= "\nLIMIT " . $this->compiledLimit;
        }

        //dbg($query);
        return $query;
    }

    function glue_union_query()
    {
        foreach ($this->compiledFrom as $alias => $full_table_string) {
            $separated_query[] = "SELECT " . $this->compiledSelect . " FROM " . $full_table_string;
        }
        $query = implode($separated_query, "\n\nUNION\n\n");
        return $query;
    }

    function setUnion($var)
    {
        $this->union = (bool)$var;
    }

    function setCount($var)
    {
        $this->count = (bool)$var;
    }

    function reset()
    {
        //reseting..

        //Where
        $this->compiledWhere = '';
        $this->where = array();

        //Limit
        $this->limit = array();
        $this->compiledLimit = '';

        //Having
        $this->having = array();
        $this->compiledHaving = '';
        //GroupBy
        $this->compiledGroupBy = '';
        $this->group_by = array();

        // join
        $this->compiledJoin = '';
        $this->join = array();
        // CJoin
        $this->compiledCJoin = '';
        $this->complex_join = array();
        //Order
        $this->compiledOrder = '';
        $this->order = array();

        $this->compiledParts = false;

        $this->query = false;

    }

    function normalizeVault($vault)
    {
        if ($vault === null) {
            return null;
        } elseif (is_string($vault)) {
            $vault = [$vault];
        } elseif (is_array($vault)) {
            if (!isset($vault[0])) $vault[0] = false;
            if (!isset($vault[1])) $vault[1] = false;
            if (!isset($vault[2])) $vault[2] = false;
        } else {
            $vault = array(false, false, false);
        }
        list($vault[2], $vault[0], $vault[1]) = $this->createAlias($vault[0], $vault[1], $vault[2]);
        return $vault;
    }

    /**
     * @return Result
     */
    function run()
    {
        $sqlr = $this->DB()->query($this->getQuery());
        if (array_key_exists('SQL_CALC_FOUND_ROWS', $this->select_prop)) {
            $sqlr->SQL_CALC_FOUND_ROWS = $this->DB()->query('SELECT FOUND_ROWS()')->getFirstValue();
        }
        return $sqlr;
    }

    function compileConditions()
    {
        if (!is_array($this->conditions) || empty($this->conditions)) {
            return;
        }
        foreach ($this->conditions as $CondType => $conditions) {
            if (!is_array($conditions) || empty($conditions)) {
                continue;
            }
            foreach ($conditions as $c_condition) {
                if ($c_condition->compile($this) === false) {
                    $this->log()->error(__METHOD__ . ' error while compiling condition type [' . var_export($CondType, true) . ']');
                }
            }
        }
    }

    function addCondition($type, $alias = false)
    {
        // $type - (string) ByLinkedOT | ...

        if (!isset($this->conditions[$type]) || !is_array($this->conditions[$type]))
            $this->conditions[$type] = array();
        if ($alias) {
            $alias = (string)$alias;
        } else {
            $alias = 'qc_rnd_alias_' . rand(0, 20000);
        }
        $className = '\Verba\QueryMaker\Condition\\' . ucfirst($type);
        $this->conditions[$type][$alias] = new $className($alias);
        if (is_object($this->conditions[$type][$alias])) {
            $this->conditionsAliases[$alias] = $this->conditions[$type][$alias];
            return $this->conditions[$type][$alias];
        } else {
            false;
        }
    }

    function isConditionExists($alias)
    {
        return array_key_exists((string)$alias, $this->conditionsAliases);
    }

    function removeCondition($alias)
    {
        if (!$alias || !array_key_exists($alias, $this->conditionsAliases)) {
            return false;
        }
        $t = $this->conditionsAliases[$alias]->type;
        unset($this->conditionsAliases[$alias], $this->conditionsAliases[$t][$alias]);
        return true;
    }

    /**
     * Возвращает интерфейс по добавлению условий выборки по связанному ОТ
     *
     * @return \Verba\QueryMaker\Condition\Linked
     *
     * @see \Verba\QueryMaker\Condition\Linked
     * @see \Verba\QueryMaker\Condition\Linked::setLinkedOT()
     * @see \Verba\QueryMaker\Condition\Linked::setLinkedIIDs()
     */
    function addConditionByLinkedOT($ot_id = false, $iids = false, $alias = false)
    {
        $obj = $this->addCondition('Linked', $alias);
        if (is_object($obj)) {
            if ($ot_id) $obj->setLinkedOT($ot_id);
            if ($iids) $obj->setLinkedIIDs($iids);
        }
        return $obj;
    }

    /**
     * @param bool $ot_id
     * @param bool $iids
     * @param bool $alias
     * @return \Verba\QueryMaker\Condition\LinkedRight
     */
    function addConditionByLinkedOTRight($ot_id = false, $iids = false, $alias = false)
    {
        $obj = $this->addCondition('LinkedRight', $alias);
        if (is_object($obj)) {
            if ($ot_id) $obj->setLinkedOT($ot_id);
            if ($iids) $obj->setLinkedIIDs($iids);
        }
        return $obj;
    }

    /**
     * @param \Verba\Model $oh
     * @param mixed $iid
     */
    static function getIdFieldNameByIdValue($oh, $iid)
    {
        $iid = (string)$iid;
        if (!$oh instanceof \Verba\Model || empty($iid)) {
            return false;
        }

        if (false == ($strPAC = $oh->getStringPAC())) {
            return $oh->getPAC();
        }
        return preg_match("/\D/", $iid, $buf) ? $strPAC : $oh->getPAC();

    }
}
