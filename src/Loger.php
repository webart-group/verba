<?php

namespace Verba;

class Loger extends Base
{
    private $alias;
    public static $allEventArray = array();
    public static $objArray = array();
    public $evArray = array();
//  public static $evTypes = array(
//    'event' => 1,
//    'warning' => 2,
//    'error' => 4,
//    'secure' => 5,
//    'debug' => 6,
//    'fin' => 7);
    protected static $cleanUpReport = '';
    protected $nologging = false;

    private function __construct($alias)
    {
        $this->alias = $alias;
    }

    public function setNologging($val)
    {
        $this->nologging = (bool)$val;
    }

    public function getNologging()
    {
        return $this->nologging;
    }

    public function event($dspStr, $flagShow = true, $flagWrite = true)
    {
        $this->addItem($dspStr, 'event', $flagShow, $flagWrite);
    }

    public function warning($dspStr, $flagShow = true, $flagWrite = true)
    {
        $this->addItem($dspStr, 'warning', $flagShow, $flagWrite);
    }

    public function debug($dspStr, $flagShow = true, $flagWrite = true)
    {
        if (!\Hive::$debug) {
            return null;
        }
        $this->addItem($dspStr, 'debug', $flagShow, $flagWrite);
    }

    /**
     * alias to debug()
     */
    public function d()
    {
        call_user_func_array(array($this, 'debug'), func_get_args());
    }

    public function flow($flow, $dspStr, $flagShow = true, $flagWrite = true)
    {
        $flow = strtolower(trim($flow));
        $bt = debug_backtrace();
        $btValue = '';
        foreach ($bt as $bte) {
            $className = isset($bte['class']) && !empty($bte['class']) ? $bte['class'] . '::' : '';
            $btValue .= '› ' . $className . $bte['function'] . '() at ' . $bte['file'] . ' line ' . $bte['line'] . "\n";
        }
        $this->addItem($dspStr, $flow, $flagShow, $flagWrite, $btValue);
    }

    public function secure($dspStr, $flagShow = true, $flagWrite = true)
    {
        $this->flow('secure', $dspStr, $flagShow, $flagWrite);
    }

    public function fin($dspStr, $flagShow = true, $flagWrite = true)
    {
        $this->flow('fin', $dspStr, $flagShow, $flagWrite);
    }

    public function error($dspStr, $flagShow = true, $flagWrite = true)
    {
        $bt = debug_backtrace();
        $btValue = '';
        foreach ($bt as $bte) {
            $className = isset($bte['class']) && !empty($bte['class']) ? $bte['class'] . '::' : '';
            $btValue .= '› ' . $className . $bte['function'] . '() at ' . $bte['file'] . ' line ' . $bte['line'] . "\n";
        }

        if (!is_string($dspStr)) {
            if ($dspStr instanceof \Exception) {
                $btValue .= "\nTRACE\n" . $dspStr->getTraceAsString();
                $dspStr = $dspStr->getMessage();
            } elseif ($dspStr instanceof \Error) {
                $btValue .= "\nTRACE\n" . $dspStr->getTraceAsString();
                $dspStr = $dspStr->getMessage();
            }
        }

        $le = error_get_last();
        if ($le) {
            $btValue .= "\n\nLast Error\n" . $le['message'] . ', at ' . $le['file'] . ' (' . $le['line'] . ')';
        }

        $this->addItem($dspStr, 'error', $flagShow, $flagWrite, $btValue);
    }

    private function addItem($displayString, $type = 'event', $flagShow = true, $flagWrite = true, $traceString = '')
    {
        if ($this->nologging) {
            return null;
        }
        \Verba\Loger::$allEventArray[] = $this->evArray[] = array('displayStr' => $displayString, 'flagShow' => $flagShow, 'flagWrite' => $flagWrite, 'trace' => $traceString, 'type' => $type, 'alias' => $this->alias, 'logged' => null);
    }

    public function countMessages($type = false)
    {
        if ($type) {
            $i = 0;
            foreach ($this->evArray as $val)
                if ($val['type'] == $type) $i++;
            return $i;
        } else {
            return count($this->evArray);
        }
    }

    static function countAllMessages($type = false)
    {
        $i = 0;
        foreach (\Verba\Loger::$objArray as $obj)
            $i += $obj->countMessages($type);
        return $i;
    }

    public function getMessages($type = false)
    {
        $arr = array();
        foreach ($this->evArray as $val) {
            if (!$type || $type === $val['type']) {
                $arr[] = $val['displayStr'];
            }
        }
        return $arr;
    }

    public function getMessagesAsStrHtml($type = false)
    {
        return $this->getMessagesAsString($type, "\n<br />");
    }

    public function getMessagesAsStr($type = false)
    {
        return $this->getMessagesAsString($type, "\n");
    }

    public function getMessagesAsString($type = false, $delimiter = ' ')
    {
        $r = '';
        $delimiter = (string)$delimiter;
        foreach ($this->evArray as $val) {
            if ((!$type || $type === $val['type']) && $val['flagShow']) {
                $r .= $val['displayStr'] . $delimiter;
            }
        }
        return $r;
    }

    public function stopScript($dspStr)
    {
        $this->addItem($dspStr, 'error', true);
        echo \Verba\Loger::getAllMessages();
        \Verba\Loger::saveToDB();
        exit;
    }

    /**
     * put your comment there...
     *
     * @param mixed $alias
     * @return \Verba\Loger
     */
    public static function create($alias = 'default')
    {
        if (empty(\Verba\Loger::$objArray[$alias])) {
            $newObj = new \Verba\Loger($alias);
            \Verba\Loger::$objArray[$alias] = $newObj;
            return $newObj;
        } else {
            return \Verba\Loger::$objArray[$alias];
        }
    }

    public static function getAllMessages($alias = false, $type = false, $format = true)
    {
        if ($alias || !empty(\Verba\Loger::$objArray[$alias])) {
            return \Verba\Loger::$objArray[$alias]->getMessagesAsStr($type);
        } else {
            $ret_text = '';
            foreach (\Verba\Loger::$allEventArray as $val) {
                if ($val['flagShow'] && ($type === false || $type === $val['type'])) {
                    if ($format)
                        $ret_text .= strtoupper($val['type']) . ' (' . $val['alias'] . '): ' . $val['displayStr'] . '<br />';
                    else
                        $ret_text .= $val['displayStr'] . '<br />';
                }
            }
            return $ret_text;
        }
    }

    public function getLastError()
    {
        if (!count($this->evArray)) return null;
        return $this->evArray[(count($this->evArray) - 1)]['displayStr'];
    }

    public static function saveToDB()
    {
        global $S;

        self::throwDiceToCleanUp();
        $logCfg = $S->gC('log');
        $r_uri = $S->DB()->escape_string($_SERVER['REQUEST_URI']);
        $u_id = is_object($S->U()) && is_numeric($S->U()->getID()) ? $S->U()->getID() : 0;
        $c_ip = ip2long(\Verba\getClientIP());

        $c = count(\Verba\Loger::$allEventArray);
        if ($c > 0) {
            $i = $t = 0;
            $inserted_values = '';
            foreach (\Verba\Loger::$allEventArray as $ev_idx => $val) {
                $t++;
                $i++;
                if (in_array($val['type'], $logCfg['saveToDb']['disallow']) || (count($logCfg['saveToDb']['allow']) && !in_array($val['type'], $logCfg['saveToDb']['allow']))) {
                    continue;
                }

                if ($val['flagWrite'] && $val['logged'] != true) {
                    $inserted_values .= ",('"
                        . SYS_SCRIPT_KEY . "','"
                        . session_id() . "','"
                        . $r_uri . "','"
                        . $u_id . "','"
                        . $c_ip . "','"
                        . $S->DB()->escape_string($val['displayStr']) . "','"
                        . $S->DB()->escape_string($val['type']) . "','"
                        . $S->DB()->escape_string($val['trace']) . "','"
                        . $S->DB()->escape_string($val['alias']) . "')";

                    \Verba\Loger::$allEventArray[$ev_idx]['logged'] = true;
                }
                if (($t == $c || $i == 100)
                    && !empty($inserted_values)) {
                    $query = "INSERT INTO `" . SYS_DATABASE . "`.`_logx_events` ("
                        . "`script_key`,"
                        . "`session`,"
                        . "`requested_url`,"
                        . "`user_id`,"
                        . "`ip`,"
                        . "`sys_text`,"
                        . "`type`,"
                        . "`trace`,"
                        . "`alias`)"
                        . " VALUES " . substr($inserted_values, 1);

                    if (!($res = $S->DB()->query($query))) {
                        self::create()->error('Unable to save log messages to DB');
                    }
                    $i = 0;
                    $inserted_values = '';
                }
            }
        }

        //access entry
        if (!in_array('event', $logCfg['saveToDb']['disallow'])
            && (!count($logCfg['saveToDb']['allow']) || in_array('event', $logCfg['saveToDb']['allow']))
        ) {
            $referer = isset($_SERVER['HTTP_REFERER'])
                ? (string)(preg_replace("/^\w+:\/\//", '', $_SERVER['HTTP_REFERER']))
                : '';
            $query = "INSERT INTO `" . SYS_DATABASE . "`.`_logx_access` ("
                . "`script_key`,"
                . "`session`,"
                . "`requested_url`,"
                . "`user_id`,"
                . "`ip`,"
                . "`queries`,"
                . "`referer`,"
                . "`gentime`,"
                . "`request`)"
                . " VALUES ('"
                . SYS_SCRIPT_KEY . "','"
                . session_id() . "','"
                . $r_uri . "','"
                . $u_id . "','"
                . $c_ip . "','"
                . $S->DB()->getQueryCount() . "','"
                . $S->DB()->escape_string($referer) . "','"
                . round((microtime(true) - $GLOBALS['__timestart']), 4) . "','"
                . $S->DB()->escape(var_export($_REQUEST, true)) . "\n\ncookies:\n-------\n" . $S->DB()->escape(var_export($_COOKIE, true)) . "')";
            if (!($res = $S->DB()->query($query))) {
                self::create()->error('Unable to log access to DB');
            }
        }
    }

    private static function throwDiceToCleanUp()
    {
        $r = rand(1, 100);
        if ($r > 1) {
            return;
        }
        self::$cleanUpReport = "Trying to clear the log. dice: '" . $r . "'. ";
        try {
            self::$cleanUpReport .= 'Access: ' . self::cleanUp('access');
            self::$cleanUpReport .= '; Events: ' . self::cleanUp('events');
        } catch (Exception $e) {
            $cleanUpReport .= $e->getMessage();
            \Verba\Loger::create('\Loger')->error(self::$cleanUpReport);
            return false;
        }
        \Verba\Loger::create('\Loger')->event(self::$cleanUpReport);
    }

    private static function cleanUp($code)
    {
        global $S;
        if ($code !== 'access' && $code !== 'events') {
            return;
        }
        $table = '_logx';
        $table .= ($code == 'access' ? '_access' : '_events');
        $time = time() - (3600 * 24 * 30);
        $formated = date('Y-m-d H:i:s', $time);
        $q = "SELECT MIN(`date`) as `i`, MAX(`date`) as `a`, COUNT(`date`) as `c` FROM `" . SYS_DATABASE . "`.`" . $table . "`
WHERE
`date` < '" . $formated . "'";
        $res = $S->DB()->query($q);
        if (!$res) {
            throw new Exception('Unable to make SQL query. Error [' . var_export($S->DB()->getLastError(), true) . ']');
        } elseif ($res->getAffectedRows() == 0) {
            return "No entries with date < '" . $formated . "'";
        }
        $row = $res->fetchRow();
        $oldest = $row['i'];
        $newest = $row['a'];
        $count = $row['c'];
        if (!$count) {
            return "No entries with date < '" . $formated . "'";
        }
        $q = "DELETE FROM `" . SYS_DATABASE . "`.`" . $table . "` WHERE `date` < '" . $formated . "'";
        $res = $S->DB()->query($q);
        if (!$res) {
            throw new Exception('Unable to cleanup Logs Table - SQL Error [' . var_export($S->DB()->getLastError(), true) . ']');
        }
        return "Success. date < '" . $formated . "', found: '" . $count . "', deleted: '" . $res->getAffectedRows() . "'. Oldest entry: '" . $oldest . "' newest: '" . $newest . "'.";
    }
}

?>