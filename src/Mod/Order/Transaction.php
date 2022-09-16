<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 26.08.19
 * Time: 16:24
 */

namespace Verba\Mod\Order;


class Transaction extends \Verba\Base
{
    protected $id;
    protected $_props;
    protected $tlog;

    function __construct($data)
    {
        if (!is_array($data) || !isset($data['id'])) {
            return false;
        }
        $this->id = $data['id'];
        $this->_props = $data;
    }

    function __get($prop)
    {
        $prop = (string)$prop;
        return is_array($this->_props) && array_key_exists($prop, $this->_props)
            ? $this->_props[$prop]
            : null;
    }

    function setTLog($logItem)
    {
        if (is_array($logItem) && !count($logItem)) {
            $this->tlog = $logItem;
            return;
        }
        if (!is_array($logItem) || !isset($logItem['id'])) {
            return false;
        }
        $this->tlog[$logItem['id']] = $logItem;
    }

    function loadTLog()
    {
        $q = "SELECT * FROM `" . SYS_DATABASE . ".`" . $this->mCfg['notifyLogTable'] . "` WHERE rqId = '" . $this->id . "' ORDER BY `rqId` DESC, `created` DESC";
        $sqlr = $this->DB()->query($q);
        if (!$sqlr) {
            $this->tlog = false;
        }
        if (!$sqlr->getNumRows()) {
            $this->tlog = array();
        }

        while ($row = $sqlr->fetchRow()) {
            $this->tlog[$row['id']] = $row;
        }
        return $this->tlog;
    }

    function getTLog()
    {
        if ($this->tlog === null) {
            $this->loadTLog();
        }
        return $this->tlog;
    }

    function getTranDataAsIni()
    {
        if (!is_array($this->_props)) {
            return '';
        }
        $r = array();
        foreach ($this->_props as $k => $v) {
            $r[] = $k . ' = ' . $v;
        }
        $r = implode("\n", $r);

        return $r;
    }
}