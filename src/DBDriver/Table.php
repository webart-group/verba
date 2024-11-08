<?php

namespace Verba\DBDriver;

class Table
{
    protected \Verba\DBDriver\mysql\Driver $driver;
    protected $name;

    public function __construct(string $name, \Verba\DBDriver\mysql\Driver $driver)
    {
        $this->driver = $driver;
        $this->name = $name;
    }

    public function setName($name): self
    {
        $this->name = $name;
        return $this;
    }

    public function insert(array $array): \Verba\DBDriver\mysql\Result
    {
        if(!isset($array[0]) || !is_array($array[0])){
            $array = [$array];
        }
        $sqlr = null;
        foreach ($array as $data) {
            $fields = '`'.implode('`,`', array_keys($data)).'`';
            $values = "'".implode("','", array_values($data))."'";

            $query = "INSERT INTO `{$this->name}` ({$fields}) VALUES ({$values})";

            $sqlr = $this->driver->query($query);
        }

        return $sqlr;
    }
}


