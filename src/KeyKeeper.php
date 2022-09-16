<?php

namespace Verba;

class KeyKeeper extends Base
{

    public $keys = array();
    public $key_binds = array();
    public $inheritable = array();
    private $ssp = 'KeyKeeper';

    function __construct()
    {
        if (isset($_SESSION[$this->ssp])) {
            $this->loadSession();
        }
    }

    function __destruct()
    {
        $this->saveSession();
    }

    function loadSession()
    {
        if (!is_array($_SESSION[$this->ssp])) {
            return false;
        }

        if (array_key_exists('keys', $_SESSION[$this->ssp])) {
            $this->keys = unserialize($_SESSION[$this->ssp]['keys']);
        }

        if (array_key_exists('key_binds', $_SESSION[$this->ssp])) {
            $this->key_binds = $_SESSION[$this->ssp]['key_binds'];
        }

        if (array_key_exists('inheritable', $_SESSION[$this->ssp])) {
            $this->inheritable = $_SESSION[$this->ssp]['inheritable'];
        }
    }

    function saveSession()
    {
        $_SESSION[$this->ssp]['keys'] = serialize($this->keys);
        $_SESSION[$this->ssp]['key_binds'] = $this->key_binds;
        $_SESSION[$this->ssp]['inheritable'] = $this->inheritable;
    }

    /*function load_all_keys(){
      $oRes = $this->loadKeys(true);
      if(!is_object($oRes)){
        return false;
      }
      while($row = $oRes->fetchRow()){
        $this->setKey($row);
      }
    }*/

    function isKeyLoaded($key)
    {
        return isset($this->keys[$key]) ? true : false;
    }

    function loadKeys($keys)
    {

        if ($keys === true) {
            $where = '';
        } else {

            $where_keys = $this->DB()->makeWhereStatement($keys, 'key_id');
            if (!is_string($where_keys))
                return false;
            else
                $where = 'WHERE ' . $where_keys;
        }

        $query = "SELECT `key_id`, `description`, `inherit_id`, `key_id_code`
    FROM `" . SYS_DATABASE . "`.`_keys`"
            . $where;

        return $this->DB()->query($query);
    }

    function buildKeys($need2load, $auto_load_inherit = true)
    {
        if (!\Verba\reductionToArray($need2load)) {
            $this->log()->error('keys loading fault' . __METHOD__ . ' (' . __LINE__ . '): unexpected $keys:[' . var_export($need2load, true) . ']');
            return false;
        }

        $needByInherit = $auto_load_inherit == true ? array() : false;

        $need2load = array_diff($need2load, array_keys($this->keys));

        if (!is_array($need2load) || !count($need2load))
            return true;

        $oRes = $this->loadKeys($need2load);
        if (!is_object($oRes) || $oRes->getNumRows() < 1) {
            return false;
        }
        while ($row = $oRes->fetchRow()) {
            $this->setKey($row);
            if ($row['inherit_id'] > 0 && $auto_load_inherit == true && !$this->isKeyLoaded($row['inherit_id'])) {
                $needByInherit[] = $row['inherit_id'];
            }
            unset($row);
        }
        if ($auto_load_inherit == true && count($needByInherit) > 0) {
            $newInherit = $this->buildKeys($needByInherit, $auto_load_inherit);
            if (is_array($newInherit)) {
                $needByInherit = array_merge($needByInherit, $newInherit);
            }
        }
        return $needByInherit;
    }

    function setKey($key_data)
    {
        $this->keys[$key_data['key_id']] = new KeyKeeper\Key($key_data);
        $this->key_binds[$key_data['key_id_code']] = $key_data['key_id'];
        if ($key_data['inherit_id'] > 0) {
            $this->inheritable[$key_data['key_id']] = $key_data['inherit_id'];
        }
    }

    function code2id($code)
    {

        return array_key_exists($code, $this->key_binds) ? $this->key_binds[$code] : false;
    }

    static function key_assign_base_object($ot_id)
    {
        $oh = \Verba\_oh($ot_id);
        if (!$oh) {
            return false;
        }
        return (int)$oh->getBaseKey();
    }
}



