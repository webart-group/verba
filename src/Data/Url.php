<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 20.08.19
 * Time: 19:36
 */

namespace Verba\Data;


class Url extends \Verba\Data
{
    public $type = 'url';
    public $remote = false;

    function __construct($cfg = null)
    {
        parent::__construct($cfg);

        $this->regErrCodes(array(
            'type' => 'bad_format_url'));
    }

    function setRemote($val)
    {
        $this->remote = (bool)$val;
    }

    function getRemote()
    {
        return $this->remote;
    }

    function validate()
    {
        $this->clearErrors();
        $Url = new \Verba\Url();
        if (!$Url->parse($this->getValue(), $this->getRemote())) {
            $this->error('type');
            return false;
        }

        $this->setValue($Url->get($this->getRemote(), true));
        return true;
    }

    function getCmpConfProps()
    {
        $parent = parent::getCmpConfProps();
        $self = array('remote' => $this->getRemote());
        return array_merge($parent, $self);
    }
}