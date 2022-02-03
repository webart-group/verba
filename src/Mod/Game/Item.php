<?php

namespace Mod\Game;

class Item
{
    protected $data = array();
    protected $services = array();

    function __construct($gameCat)
    {
        if (!$gameCat || !is_array($gameCat)) {
            return false;
        }

        if (array_key_exists('_items', $gameCat)
            && is_array($gameCat['_items']) && count($gameCat['_items'])) {
            foreach ($gameCat['_items'] as $srvId => $serviceData) {
                $this->addService($srvId, $serviceData);
            }

            unset($gameCat['_items']);
        }

        $this->data = $gameCat;
    }

    function __get($prop)
    {
        return array_key_exists($prop, $this->data) ? $this->data[$prop] : null;
    }

    function getData()
    {
        return $this->data;
    }

    function addService($id, $serviceData)
    {
        $this->services[$id] = new Service($this, $serviceData);
    }

    function getServiceByCode($code)
    {
        if (!count($this->services)) {
            return null;
        }
        $r = false;
        foreach ($this->services as $sid => $service) {
            if ($service->code != $code) {
                continue;
            }
            return $service;
        }
        return false;
    }

    function getServiceById($id)
    {
        return !count($this->services)
        || !array_key_exists($id, $this->services)
            ? null
            : $this->services[$id];
    }

    function getService($idOrCode)
    {
        return is_numeric($idOrCode)
            ? $this->getServiceById($idOrCode)
            : $this->getServiceByCode($idOrCode);
    }

    function getFirstService()
    {
        if (!count($this->services)) {
            return null;
        }
        reset($this->services);
        return current($this->services);
    }

    function getServices()
    {
        return $this->services;
    }

    function serviceExists($sid)
    {
        return is_object($this->getService($sid));
    }

    function getUrlByAction($action = false, $serviceId = false)
    {

        if ($serviceId && is_object($Service = $this->getService($serviceId))) {
            $serviceSuffix = '/' . $Service->code;
        } elseif (is_object($firstService = $this->getFirstService())) {
            $serviceCode = !empty($firstService->code)
                ? $firstService->code
                : $firstService->id;
            $serviceSuffix = '/' . $serviceCode;
        } else {
            $serviceSuffix = '';
        }

        $str = '/' . $this->code . $serviceSuffix;

        if (is_string($action)) {
            $str = '/' . $action . $str;
        }

        return $str;
    }

}
