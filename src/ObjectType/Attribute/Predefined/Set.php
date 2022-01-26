<?php
namespace Verba\ObjectType\Attribute\Predefined;


class Set extends \Verba\Base
{

    public $ot_id;
    public $pd_set = array(
        'id' => null,
        'title' => null,
    );
    public $vault = array(
        'root' => '',
        'object' => '',
    );

    public $default_value = false;

    public $values = null;
    protected $_rawValues = null;
    public $collection;

    function __construct($cfg, $collection)
    {
        $this->collection = $collection;

        $this->ot_id = (int)$cfg['ot_id'];

        $this->pd_set['id'] = (int)$cfg['pd_set'];
        $this->pd_set['title'] = (string)$cfg['pd_set_title'];

        $this->default_value = isset($cfg['default_value']) && !empty($cfg['default_value'])
            ? $cfg['default_value']
            : null;

        $this->vault['root'] = \Verba\ObjectType\DataVault::convertRoot($cfg['root']);
        $this->vault['object'] = (string)$cfg['object'];
    }

    function getValueById($id, $lang = false)
    {
        $this->getValues();
        $lang = is_string($lang) && \Verba\Lang::isLCValid($lang)
            ? $lang
            : SYS_LOCALE;

        if (!array_key_exists($id, $this->values)
            || !array_key_exists($lang, $this->values[$id])) {
            return null;
        }

        return $this->values[$id][$lang];
    }

    function getValues()
    {
        if ($this->_rawValues === null) {
            $this->_rawValues = array();
            $this->loadValues();
        }
        return $this->values;
    }

    function getValuesForLang($lang = false)
    {

        $this->getValues();

        if (!is_array($this->values) || !count($this->values)) {
            return array();
        }

        if (!is_string($lang) || $lang == SYS_LOCALE || !\Verba\Lang::isLCValid($lang)) {
            return $this->values;
        }

        $r = array();

        foreach ($this->_rawValues as $valueId => $valueLangs) {
            $r[$valueId] = is_array($valueLangs) && array_key_exists($lang, $valueLangs)
                ? $valueLangs[$lang]
                : (is_string($valueLangs) ? $valueLangs : '');
        }

        return $r;
    }

    protected function loadValues()
    {

        $_pred = \Verba\_oh('predefined');
        $predPAC = $_pred->getPAC();

        if (!$this->vault['root'] || !$this->vault['object']) {
            return array('0' => array(SYS_LOCALE => 'Bad vault'));
        }

        $query = "SELECT 
`pred`.*
FROM `" . $this->vault['root'] . "`.`" . $this->vault['object'] . "` as `pred`,
`" . SYS_DATABASE . "`.`pd_links` as `sets`
WHERE `sets`.`ch_iid` = `pred`.`pred_id`
&& `sets`.`p_iid` = '" . $this->pd_set['id'] . "'
ORDER BY sets.priority DESC, `pred`.`pred_id`";

        $sqlr = $this->DB()->query($query);
        if (!$sqlr || !$sqlr->getNumRows()) {
            return null;
        }

        while ($row = $sqlr->fetchRow()) {
            $row = $_pred->substrLcdAttrAsArrayInData($row);
            $this->_rawValues[$row[$predPAC]] = $row;
            $this->values[$row[$predPAC]] = &$this->_rawValues[$row[$predPAC]]['value'][SYS_LOCALE];
        }

        return $this->_rawValues;
    }

    function getDefaultValue()
    {
        return $this->default_value;
    }

    function filterValues($filters, $lang = false)
    {

        if (!is_array($filters) || !count($filters)) {
            return false;
        }
        $this->getValues();
        if (!is_array($this->_rawValues) || !count($this->_rawValues)) {
            return false;
        }
        $U = \Verba\User();


        $lang = !is_string($lang) || !\Verba\Lang::isLCValid($lang) ? SYS_LOCALE : $lang;

        // все возможные фильтры применяются здесь

        // Фильтрация по id - если указан id как массив или один id
        if (array_key_exists('id', $filters)
            && (is_array($filters['id']) || is_string($filters['id']) || is_numeric($filters['id']))
        ) {
            $filter_condition = $filters['id'];
            if (!is_array($filter_condition)) {
                $includeId = array($filter_condition);
            } else {
                $includeId = $filter_condition;
            }
            $intersected = array_intersect_key($this->_rawValues, array_flip($includeId));

            if (!count($intersected)) {
                return array();
            }
            $r = array();
            foreach ($intersected as $catchetPdvId => $catchetPdv) {
                $r[$catchetPdvId] = $catchetPdv['value'][$lang];
            }
        }

        // Фильтрация по праву
        if (array_key_exists('right', $filters)) {
            $filter_condition = $filters['right'];
            if (!isset($r)) {
                $r = is_array($this->values) ? $this->values : array();
            }
            foreach ($r as $predId => $value) {
                if ($this->_rawValues[$predId]['key_id'] > 0
                    && !$U->chr($this->_rawValues[$predId]['key_id'], $filter_condition)) {
                    unset($r[$predId]);
                }
            }
        }

        return isset($r) ? $r : array();
    }
}