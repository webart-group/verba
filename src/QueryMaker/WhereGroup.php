<?php
namespace Verba\QueryMaker;

class WhereGroup
{
    public $name = false;
    public $connector = '&&';
    public $where = array();
    /**
     * @var \Verba\QueryMaker
     */
    protected $qm;

    function __construct($qm, $name = false, $conn = false)
    {
        $this->qm = $qm;
        if (is_string($conn) && !empty($conn)) {
            $this->setConnector($conn);
        }
        if (is_string($name) && !empty($name)) {
            $this->setName($name);
        }
    }

    public function setName($name)
    {
        $this->name = (string)$name;
    }

    public function setConnector($conn)
    {
        $this->connector = (string)$conn;
    }

    /**
     * Добавление условий выборки
     *
     * @param string $value Значение поля. В режиме asis должно содержать полный синтаксис where подусловия например myfield = 'condition'
     * @param string|false $alias Задает алиас условия по которому в будущем можно к нему обратиться. Передав false - включает режим asis
     * @param string|false $field Название поля. В режиме asis задаст будет использован как алиас условия.
     * @param string|false $vault Ваулт в формате.
     * @param string $operator Оператор сравнения.
     * @param string $connector Условие соединения с другими частями where.
     */
    public function addWhere()
    {
        $args = func_get_args();
        list($alias, $data) = call_user_func_array(array($this->qm, 'createWhereData'), $args);
        $this->where[$alias] = $data;
    }

    public function removeWhere($alias)
    {
        if (!is_string($alias) || !array_key_exists($alias, $this->where)) {
            return false;
        }
        unset($this->where[$alias]);
    }

    public function getWhere($alias = false)
    {
        return is_string($alias) && isset($this->where[$alias])
            ? $this->where[$alias]
            : (!$alias
                ? $this->where
                : null);
    }

    public function compile()
    {
        if (!count($this->where)) {
            return '';
        }
        $r = '';
        foreach ($this->where as $a => $data) {
            $r .= $this->qm->compileWhereItem($data);
        }
        return ' ' . $this->connector . ' ( ' . substr($r, 3) . ' )';
    }
}