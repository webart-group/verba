<?php

namespace Verba\Act;

class AddEditHandler extends Action
{

    protected $action;
    protected $attributes = array();
    protected $exists_values;

    protected $_existsItem;
    /**
     * @var int
     */
    protected $delegatedOwnerId;
    /**
     * @var bool|\U
     */
    protected $_U;
    /**
     * @var \Verba\Model
     */
    public $oh;

    protected $existsValuesLoaded = false;

    static function extractAEFActionsFromURL($action)
    {
        $forwardAction = null;

        switch ($action) {
            case 'new':
                $forwardAction = 'newnow';
                break;
            case 'edit':
                $forwardAction = 'editnow';
                break;
            case 'createform':
                $forwardAction = 'create';
                break;
            case 'updateform':
                $forwardAction = 'update';
                break;
        }

        return array($action, $forwardAction);
    }

    /**
     * Проверка доступа пользователя для запрошенного типа операции (добавление/изменение).
     *
     * @return true|false
     */
    function validateAccess()
    {

        $oh = \Verba\_oh($this->ot_id);
        $data = false;
        $reqRight = array('u', 'c');

        if ($this->getAction() === 'edit') {
            if (empty($this->iid) || (!$this->isExistsValuesLoaded() && !$this->loadExistsValues())) {
                $this->log()->error('Unable to make object\'s exists values');
                return false;
            }
            $reqRight = 'u';
            $data = $this->getExistsValues();
            $oh->substAttrIdsToCodes($data, false, false);
        } elseif ($this->getAction() === 'new') {
            $reqRight = 'c';
            $data = '~';
        }

        if (!$this->getUser()->chrItem($this->getKeyId(), $reqRight, $data)) {
            $this->log()->error("Access denied for action '" . $this->getAction() . "'");
            return false;
        }
        return true;
    }

    function getUser()
    {
        if ($this->_U === null) {
            $this->_U = is_int($this->delegatedOwnerId)
                ? new \Verba\Model\User($this->delegatedOwnerId)
                : \Verba\User();
        }
        return $this->_U;
    }

    /**
     * @param int $userId
     */
    function setDelegatedOwnerId($userId)
    {
        $this->delegatedOwnerId = (int)$userId;
    }

    function isExistsValuesLoaded()
    {
        return is_array($this->exists_values) && count($this->exists_values);
    }

    static public function make_action_sign($action = false, $iid = false)
    {
        $action = strtolower($action);
        $r = false;
        switch ($action) {
            case 'create':
            case 'createform':
            case 'addnew':
            case 'additem':
            case 'new':
            case 'newnow':
            case 'clone':
            case 'importnow':
                $r = 'new';
                break;

            case 'updateform':
            case 'update':
            case 'edit':
            case 'edititem':
            case 'editnow':
                $r = 'edit';
                break;

            case 'cuform':
            case '':
                $r = !empty($iid) ? 'edit' : 'new';
                break;

            default:
                $r = false;
        }


        return $r;
    }

    static public function make_action_sign2($action = false, $iid = false)
    {

        switch (self::make_action_sign($action, $iid)) {
            case 'new':
                $r = 'create';
                break;
            case 'edit':
                $r = 'update';
                break;
            default:
                $r = false;
        }

        return $r;
    }

    function setOTID($ot_id)
    {
        if (!is_object($this->oh = \Verba\_oh($ot_id)))
            throw new \Exception('Bad OT ID');

        $this->ot_id = $this->oh->getID();
        return $this->ot_id;
    }

    function getOTID()
    {
        return $this->ot_id;
    }

    function setIid($val)
    {
        if ((is_numeric($val) || is_string($val)) && !empty($val)) {
            $this->iid = $val;
        }
    }

    function getIID()
    {
        return $this->iid;
    }

    function loadExistsValues()
    {

        if (empty($this->iid)) return false;

        if (!($exists = $this->oh->getData($this->iid, 1, true, false, true))
            || !$this->setExistsValues($exists)) {
            $this->log()->error("Can't take object's exists values. '" . $this->oh->getCode() . "' #" . $this->iid, true);
            return false;
        }

        return true;
    }

    function addExistsValues($row)
    {

        if (!is_array($row)) {
            return false;
        }

        if (!is_array($this->exists_values)) {
            $this->exists_values = [];
        }

        foreach ($row as $c_attr_code => $c_attr_value) {

            $c_attr_id = !is_object($A = $this->oh->A($c_attr_code))
                ? false
                : $A->getID();

            if (is_numeric($c_attr_id)) {
                if ($A->get_lcd() && is_array(\Verba\Lang::getUsedLC())) {
                    $this->exists_values[$c_attr_code] = array();
                    foreach (\Verba\Lang::getUsedLC() as $c_lc_code) {
                        $this->exists_values[$c_attr_code][$c_lc_code] = isset($row[$c_attr_code . '_' . $c_lc_code])
                        ? $row[$c_attr_code . '_' . $c_lc_code]
                        : null;
                    }
                } else {
                    $this->exists_values[$c_attr_code] = $c_attr_value;
                }
            } else {
                $this->exists_values[$c_attr_code] = $c_attr_value;
            }
        }
        return true;
    }

    function setExistsValues($data)
    {
        $this->exists_values = [];
        $this->addExistsValues($data);
        $this->_existsItem = $this->oh->initItem($data);
        return $this->exists_values;
    }

    function getExistsValues()
    {
        return $this->exists_values;
    }

    function getExistsValue($attr)
    {
        if (!is_array($this->exists_values)) {
            return null;
        }
        if (is_object($a = $this->oh->A($attr)) && isset($this->exists_values[$a->getCode()])) {
            return $this->exists_values[$a->getCode()];
        } elseif (array_key_exists($attr, $this->exists_values)) {
            return $this->exists_values[$attr];
        }
        return null;
    }

    function getExistsItem()
    {
        return $this->_existsItem;
    }
}
