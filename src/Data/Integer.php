<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 20.08.19
 * Time: 19:33
 */

namespace Verba\Data;


class Integer extends Numeric
{
    public $type = 'integer';

    function validate()
    {
        $this->clearErrors();
        $v = $this->getValue();
        if (false === ($vInt = intval($v)) || $vInt != $v) {
            $this->error('type');
        }
        if (is_int($this->min) && $vInt < $this->min) {
            $this->error('min', array($this->getMin()));
        }
        if (is_int($this->max) && $vInt > $this->max) {
            $this->error('max', array($this->getMax()));
        }
        if (count($this->errors)) return false;

        $this->setValue($vInt);
        return true;
    }
}