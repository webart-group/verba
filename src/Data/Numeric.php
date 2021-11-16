<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 20.08.19
 * Time: 19:32
 */

namespace Verba\Data;

class Numeric extends \Verba\Data
{
    public $type = 'numeric';

    function __construct($cfg = null)
    {
        parent::__construct($cfg);

        $this->regErrCodes(array(
            'type' => 'bad_format_numeric',
            'min' => 'bad_size_numeric_min',
            'max' => 'bad_size_numeric_max'));
    }

    function validate()
    {
        $this->clearErrors();
        $v = $this->getValue();

        if (!is_numeric($v)) {
            $this->error('type');
        }
        if (is_int($this->min) && $v < $this->min) {
            $this->error('min', array($this->getMin()));
        }
        if (is_int($this->max) && $v > $this->max) {
            $this->error('max', array($this->getMax()));
        }
        if (count($this->errors)) return false;

        return true;
    }
}