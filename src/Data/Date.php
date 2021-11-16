<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 20.08.19
 * Time: 19:33
 */
namespace Verba\Data;

class Date extends \Verba\Data
{
    public $type = 'date';

    function __construct($cfg = null)
    {
        parent::__construct($cfg);

        $this->regErrCodes(array(
            'type' => 'bad_format_date',
            'min' => 'bad_size_date_min',
            'max' => 'bad_size_date_max'));
    }

    function validate()
    {
        $this->clearErrors();
        $v = $this->getValue();

        if (!(is_numeric($v) && ($parsedVal = intval($v)) && $parsedVal == $v
            || is_string($v) && is_int($parsedVal = strtotime($v)))) {
            $this->error('type');
        }
        if (is_int($this->min) && $parsedVal < $this->min) {
            $this->error('min', array($this->getMin()));
        }
        if (is_int($this->max) && $parsedVal > $this->max) {
            $this->error('max', array($this->getMax()));
        }
        if (count($this->errors)) return false;

        $this->setValue($parsedVal);
        return true;
    }
}