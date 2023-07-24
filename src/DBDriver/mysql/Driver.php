<?php

namespace Verba\DBDriver\mysql;

class Driver implements \Verba\DBDriver\Driver
{
    /**
     * @var \mysqli
     */
    public $miObj;
    public $queryCounter = 0;
    private $mysqlError = false;
    private $lastResult;
    private $connectData;
    private $debug = 0;
    private $logfile = null;
    private $queriesStat = array();

    function __construct($connectData = array(), $debugCfg = array())
    {
        $this->connectData = $connectData;

        if (isset($debugCfg['sqlQueriesLog'])) {
            $this->debug = (bool)$debugCfg['sqlQueriesLog'];
        }

        $this->connect($connectData);
    }

    function __destruct()
    {
        if (!$this->debug) {
            return;
        }
        if (is_resource($this->logfile)) {
            $this->writeQueriesStat();
            $this->closeLogFile();
        }
    }

    function __sleep()
    {
        return array('connectData');
    }

    function __wakeup()
    {
        $this->connect($this->connectData);
    }

    private function openLogFile()
    {
         \Verba\FileSystem\Local::needDir(SYS_VAR_DIR . '/SQL');
        $fname = strftime('%m-%d-%H-%M-%S') . '.txt';
        $this->logfile = fopen(SYS_VAR_DIR . '/SQL/' . $fname, 'a');
        return $this->logfile;
    }

    private function closeLogFile()
    {
        fclose($this->logfile);
    }

    private function getLogFile()
    {
        if ($this->logfile === null) {
            $this->openLogFile();
        }
        return $this->logfile;
    }

    private function logWrite($str)
    {
        $this->getLogFile();
        if (!is_resource($this->logfile)) {
            return false;
        }
        fwrite($this->logfile, $str);
    }

    private function writeQueriesStat()
    {
        if (count($this->queriesStat)) {
            arsort($this->queriesStat, SORT_NUMERIC);
            $longestN = key($this->queriesStat);
            $longestT = current($this->queriesStat);
        } else {
            $longestN = $longestT = 0;
        }

        $this->logWrite("\r\nTotal queries: " . $this->queryCounter . ", longest: #" . $longestN . "(" . $longestT . ")\r\n");
    }

    function getResource()
    {
        return $this->miObj;
    }

    public function connect($connectData)
    {
        $this->miObj = new \mysqli(
            $connectData['host'],
            $connectData['user'],
            $connectData['password'],
            $connectData['database'],
            $connectData['port']
        );

        if (\mysqli_connect_errno()) {
            $this->miObj = false;
        }
    }

    /**
     * Возвращает интерфейс работы с mysql-результатом
     *
     * @param string $query
     * @return \DBDriver\mysql\Result
     */
    public function query($query)
    {
        $this->queryCounter++;
        if ($this->debug) {
            $time_start = microtime(true);
            $this->logWrite("\r\n#" . $this->queryCounter . " " . str_repeat('-', 50) . "\r\n" . $query . "\r\n");
        }
        if (!is_string($query) || empty($query) || !($result = $this->miObj->query($query))) {
            $message = "SQL Error: " . $this->miObj->errno . "\n" . $this->miObj->error . "\nQuery:\n" . var_export($query, true) . "\n";
            throw new \Exception($message);
        }
        if ($this->debug) {
            $exec_time = round((microtime(true) - $time_start), 4);
            $this->logWrite("\r\n time: " . $exec_time . "\r\n");
            $this->queriesStat[$this->queryCounter] = $exec_time;
        }
        $this->lastResult = new Result($result);
        $this->lastResult->miInsertId = $this->miObj->insert_id;
        $this->lastResult->miAffectedRows = (int)$this->miObj->affected_rows;

        return $this->lastResult;
    }

    function close()
    {
        $this->miObj->close();
    }

    public function escape_string($strIn)
    {
        return $this->miObj->real_escape_string($strIn);
    }

    public function escape($strIn)
    {
        return $this->escape_string($strIn);
    }

    public function getLastError()
    {
        return $this->miObj->error;
    }

    public function getLastResult()
    {
        return $this->lastResult;

    }

    public function multiQuery($query)
    {
        $r_array = false;
        if ($this->miObj->multi_query($query)) {
            do {
                $this->lastResult = new \DBDriver\mysql\Result($this->miObj->store_result());
                $this->lastResult->miInsertId = $this->miObj->insert_id;
                $this->lastResult->miAffectedRows = $this->miObj->affected_rows;
                $r_array[] = $this->lastResult;

            } while ($this->miObj->next_result());
        }
        return $r_array;
    }

    public function getQueryCount()
    {
        return $this->queryCounter;
    }

    public function makeWhereStatement($values, $field, $table = false, $val_type = 'int', $glue = '||', $INStatement = true, $negation = false)
    {
        $output = '';
        $glue = !in_array($glue, array('||', '&&')) ? '||' : $glue;
        $val_type = $val_type === 'int' || $val_type === 'str' ? 'int' : $val_type;

        if (!is_array($values) && !((is_string($values) || is_numeric($values)) && settype($values, 'array')) || !is_string($field) || empty($field)) {
            return false;
        }

        $field = '`' . $this->escape_string($field) . '`';
        if (is_string($table) && !empty($table)) {
            $field = '`' . $this->escape_string($table) . '`.' . $field;
        }
        $not = !((bool)$negation)
            ? ''
            : ($INStatement ? ' NOT' : ' !');
        //$field IN(value0, value1, value2) -конструкция
        if ($INStatement) {

            foreach ($values as $c_value) {
                $output .= ", '" . $c_value . "'";
            }

            $output = !empty($output) ? ' ' . $field . $not . ' IN (' . mb_substr($output, 1) . ') ' : false;

            // (field = value || field = value2 || ...) конструкция
        } else {
            foreach ($values as $c_value) {
                $output .= $glue . " " . $field . " '.$not.'= '" . $this->escape_string($c_value) . "'";
            }

            $output = !empty($output) ? ' (' . mb_substr($output, 3) . ') ' : false;
        }

        return $output;
    }

    static public function formatDateTime($ts = null)
    {
        if ($ts === null) {
            $ts = time();
        }
        return date('Y-m-d H:i:s', $ts);
    }
}
