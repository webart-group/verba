<?php
namespace Verba\Data;


class _Float extends \Verba\Data
{
    public $type = 'float';
    public $precision = 2;

    function setPrecision($val)
    {
        if (!$val = intval($val)) return false;
        $this->precision = $val;
        return $this->precision;
    }

    function getPrecision()
    {
        return $this->precision;
    }

    function setMin($val)
    {
        $this->min = \Verba\reductionToFloat($val, $this->precision, true);
    }

    function setMax($val)
    {
        $this->max = \Verba\reductionToFloat($val, $this->precision, true);
    }

    function validate()
    {
        $this->clearErrors();
        $v = $this->getValue();
        if (!is_numeric($v) || !($vFloat = \Verba\reductionToFloat($v, $this->precision, true))) {
            $this->error('type');
        }
        if (is_int($this->min) && $vFloat < $this->min) {
            $this->error('min', array($this->getMin()));
        }
        if (is_int($this->max) && $vFloat > $this->max) {
            $this->error('max', array($this->getMax()));
        }
        if (count($this->errors)) return false;

        $this->setValue($vFloat);
        return true;
    }

    function getCmpConfProps()
    {
        $parent = parent::getCmpConfProps();
        $self = array('precision' => $this->getPrecision());
        return array_merge($parent, $self);
    }
}