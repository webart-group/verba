<?php
namespace Verba;

class Base
{
    /**
     * @var \DBDriver\mysql\Driver
     */
    protected $DB;
    /**
     * @var \Verba\Loger
     */
    protected $log;
    protected $_logAlias;

    /**
     * Возвращает интерфейс работы с БД
     *
     * @return \DBDriver\mysql\Driver
     */
    public function DB()
    {
        global $S;
        return is_object($this->DB)
            ? $this->DB
            : ($this->DB = $S->DbConnect());
    }

    /**
     *
     * @return \Verba\Loger
     */
    public function log()
    {
        return is_object($this->log)
            ? $this->log
            : ($this->log = \Verba\Loger::create($this->getLogAlias()));
    }

    public function getLogAlias()
    {
        if (!isset($this->_logAlias)) {
            $this->_logAlias = $this->makeLogAlias();
        }
        return $this->_logAlias;
    }

    public function makeLogAlias()
    {
        return get_class($this);
    }

}
