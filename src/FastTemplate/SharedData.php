<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 21.08.19
 * Time: 16:35
 */

namespace FastTemplate;

class SharedData
{

    public $LOADED = array();
    public $FILELIST = array();

    protected $clientsCount = 0;

    /**
     * @param $FT \FastTemplate
     */
    function init($FT)
    {
        $FT->LOADED = &$this->LOADED;
        $FT->FILELIST = &$this->FILELIST;

        $this->clientsCount++;
    }

    function clientMinus($FT)
    {
        $this->clientsCount--;
    }

    function getClientsCount()
    {
        return $this->clientsCount;
    }
}