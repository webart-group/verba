<?php

namespace Verba\ObjectType;

class DataVault
{
    private $id;
    private $scheme;
    private $host;
    private $root;
    private $object;
    private $user;
    private $password;
    private $port;
    private $URI;

    function __construct($fetched_row)
    {
        $this->setScheme($fetched_row['scheme']);
        $this->setHost($fetched_row['host']);
        $this->setUser($fetched_row['user']);
        $this->setPort($fetched_row['port']);
        $this->setPassword($fetched_row['password']);
        $this->setRoot($fetched_row['root']);
        $this->setObject($fetched_row['object']);
        $this->setID($fetched_row['vlt_id']);
        $this->setURI();
    }

    function setScheme($type)
    {
        $this->scheme = $type;
    }

    function getScheme()
    {
        return $this->scheme;
    }

    function setRoot($root)
    {
        $this->root = self::convertRoot($root);
    }

    function getRoot()
    {
        return $this->root;
    }

    function setObject($object)
    {
        $this->object = is_string($object) && !empty($object) ? $object : "";
    }

    function getObject()
    {
        return $this->object;
    }

    function setURI()
    {
        $this->URI = "`" . $this->getRoot() . "`.`" . $this->getObject() . "`";
    }

    function getURI()
    {
        return $this->URI;
    }

    function setID($id)
    {
        $this->id = (int)$id;
    }

    function getID()
    {
        return $this->id;
    }

    function setHost($var)
    {
        $this->host = (string)$var;
    }

    function getHost()
    {
        return $this->host;
    }

    function setUser($var)
    {
        $this->user = (string)$var;
    }

    function getUser()
    {
        return $this->user;
    }

    function setPassword($var)
    {
        $this->password = (string)$var;
    }

    function getPassword()
    {
        return $this->password;
    }

    function setPort($var)
    {
        $this->port = (int)$var;
    }

    function getPort()
    {
        return $this->port;
    }

    static function convertRoot($root)
    {
        return empty($root) ? SYS_DATABASE : (string)$root;
    }
}
