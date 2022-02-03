<?php

namespace Mod;

class Cron extends \Verba\Mod
{
    use \Verba\ModInstance;
    private $urlHash = 'f4c827e-d41d8cd98f00b204e9800998ecf84e27';
    public $dateFormat = 'Y-m-d H:i:s';

    function getUrlHash()
    {
        return $this->urlHash;
    }

    /**
     * ### States ###
     *
     * 0-9 - work variants
     * 1 - added
     * 2 - queued
     *
     * 10-19 - in progress
     * 10 - in progress
     *
     * 20-19 - paused
     * 20 - in pause,
     *
     * 50-59 - to remove
     * 50 - require to remove
     *
     * ### Signals ### return signals
     *
     * 2 - queue
     *
     */

    function runTask($task)
    {
        $updData = array();
        $returnSignal = null;
        $returnData = null;
        if (!is_array($task) || empty($task) || !isset($task['id'])) {
            return array($returnSignal, $returnData, $updData);
        }
        $q = "UPDATE " . SYS_DATABASE . ".`_cron` SET
`state` = '10',
`lastStart` = '" . date($this->dateFormat) . "'
WHERE id = '" . $task['id'] . "'";
        $this->DB()->query($q);
        $handler = false;

        try {

            if (!empty($task['args'])) {
                $args = unserialize($task['args']);
            } else {
                $args = array();
            }

            // Если обработчик это метод модуля
            if (!empty($task['mod'])) {
                $handler = array(
                    \Verba\_mod($task['mod']),
                    $task['method'],
                );
                $h_txt = $handler[0]->getCode();
                $m_txt = $task['method'];
            } else {
                // Если обработчик сделан через класс.
                if (class_exists($task['method'])) {
                    $Class = new $task['method']();
                    $h_txt = get_class($Class);

                    if (is_array($args) && count($args) && $Class instanceof \Configurable) {
                        $Class->applyConfigDirect($args);
                        $args = array();
                    }
                    // Block
                    if ($Class instanceof \Block) {
                        $Class->prepare();
                        $handler = array($Class, 'build');
                        // Cron task
                    } else {
                        $m_txt = 'run';
                        $handler = array($Class, $m_txt);
                    }
                } else {
                    if (function_exists($task['method'])) {
                        $handler = array($task['method']);
                        $h_txt = 'function';
                        $m_txt = $task['method'];
                    }
                }
            }

            if (!$handler) {
                $updData['state'] = 50;
            } else {

                $__timestart = microtime(true);
                list($returnSignal, $returnData) = call_user_func_array($handler, $args);
                $worktime = round((microtime(true) - $__timestart), 4);
                $updData['lastWorkTime'] = $worktime;

                $this->log()->event('Cron task end. Return signal is:' . var_export($returnSignal, true) . ' handler: ' . $h_txt . '::' . $m_txt . ' worktime:' . $worktime);
            }

            switch ($returnSignal) {
                case 2:
                    $updData['state'] = 2;
                    if (isset($returnData['startAt'])
                        && false !== ($startAt = strtotime($returnData['startAt']))
                        && $startAt > 0) {
                        $updData['startAt'] = date($this->dateFormat, $startAt);
                    } else {
                        $updData['startAt'] = date($this->dateFormat, strtotime($task['startAt']));
                    }
                    break;
                //case 0:
                default:
                    $_task_is_deleted = true;
                    $uq = "DELETE FROM " . SYS_DATABASE . ".`_cron` WHERE `id` = '" . $task['id'] . "'";
                    $this->DB()->query($uq);
            }

            if (!isset($_task_is_deleted) && !empty($updData)) {
                $updDataFields = array();
                foreach ($updData as $fcode => $fvalue) {
                    $updDataFields[$fcode] = '`' . $this->DB()->escape($fcode) . '` = \'' . $this->DB()->escape($fvalue) . '\'';
                }
                $q = "UPDATE " . SYS_DATABASE . ".`_cron` SET
  " . implode(',', $updDataFields) . "
  WHERE id = '" . $task['id'] . "'";
                $this->DB()->query($q);
            }

        } catch (\Exception $e) {
            $this->log()->error($e->getMessage());
        }

        return array($returnSignal, $returnData, $updData);
    }

    function runTasks()
    {
        set_time_limit(3600 * 24);
        $totaltime = $i = 0;

        $taskStart = date($this->dateFormat);
        do {
            $q = "SELECT * FROM " . SYS_DATABASE . ".`_cron` 
      WHERE state < 10 && startAt <= '" . $taskStart . "' LIMIT 1";
            $sqlr = $this->DB()->query($q);
            if (!$sqlr || !$sqlr->getNumRows()) {
                break;
            }
            $row = $sqlr->fetchRow();
            $i++;

            list($returnSignal, $returnData, $updData) = $this->runTask($row);
            $totaltime += $updData['lastWorkTime'];

        } while ($row);
        if (!$totaltime) {
            return 'Waiting...';
        }
        return $i . ' in ' . $totaltime . ' s';
    }

    function addTask($mod, $method, $args = null, $startAt = null)
    {
        $r = false;
        try {
            $args = isset($args) ? serialize($args) : '';

            $task = $this->getTask($mod, $method);

            $uri = SYS_DATABASE . "._cron";
            if ($task) {
                $q = "UPDATE " . $uri . " SET 
        `args` = '" . $this->DB()->escape($args) . "',
        `startAt` = '" . $this->DB()->escape($startAt) . "',
        `state` = '1'
        WHERE `id` = '" . $task['id'] . "' LIMIT 1";

                $sqlr = $this->DB()->query($q);
                if (!$sqlr) {
                    throw new \Exception('Sql-error while update task.');
                } else {
                    $r = $task['id'];
                }

            } else {

                $q = "INSERT INTO " . $uri . " (
          `mod`,
          `method`,
          `args`,
          `startAt`,
          `state`,
          `created`
          )VALUES(
            '" . $this->DB()->escape($mod) . "',
            '" . $this->DB()->escape($method) . "',
            '" . $this->DB()->escape($args) . "',
            '" . $this->DB()->escape($startAt) . "',
            '0',
            '" . date("Y-m-d H:i:s") . "'
          )
        ";

                $sqlr = $this->DB()->query($q);
                if (!$sqlr || !$sqlr->getAffectedRows()) {
                    throw new \Exception('Unable to add Cron Task to sql table.');
                } else {
                    $r = $sqlr->getInsertId();
                }
            }
        } catch (\Exception $e) {
            $this->log()->error($e->getMessage() . ' mod:\' . var_export($mod, true) . \'::\' . var_export($method, true)');
            return false;
        }
        return $r;
    }

    function taskExists($mod, $method)
    {
        $q = "SELECT * FROM " . SYS_DATABASE . "._cron
    WHERE
    `mod` = '" . $this->DB()->escape_string($mod) . "'
    && `method` = '" . $this->DB()->escape_string($method) . "'";
        $sqlr = $this->DB()->query($q);
        return ($sqlr->getNumRows()) ? true : false;
    }

    /**
     * @param int|modCode $arg1 id задачи или модуль если 2 аргумента
     * @param null|method|block $arg2
     * @return bool|null
     */
    function getTask($arg1, $arg2 = false)
    {
        $args = func_get_args();
        if (func_num_args() == 1) {
            if (is_numeric($args[0])) {
                $field = 'id';
            } else {
                $field = 'alias';
            }

            $where = "`" . $field . "` = '" . $this->DB()->escape($args[0]) . "'";

            // если аргументов больше 1:
            // арг1 не пустая строка: арг1 = модуль, арг2 = метод
            // арг1 пустая строка: арг1 = '', арг2 = класс блока
        } else {
            $where = "`mod` = '" . $this->DB()->escape($args[0]) . "'
    && `method` = '" . $this->DB()->escape($args[1]) . "'";
        }

        $sqlr = $this->DB()->query("SELECT * FROM " . SYS_DATABASE . "._cron
    WHERE " . $where);

        if (!$sqlr->getNumRows()) {
            return null;
        }
        return $sqlr->fetchRow();
    }

    function removeTask($taskId)
    {
        if (!\Verba\convertToIdList($taskId)) {
            return false;
        }
        $sqlr = $this->DB()->query("DELETE FROM " . SYS_DATABASE . "._cron
    WHERE
    `id` IN ('" . implode("', '", $taskId) . "')");

        return $sqlr->getAffectedRows();
    }
}
