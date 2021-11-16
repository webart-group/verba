<?php
namespace Verba\Data;

class Regexp extends \Verba\Data
{
    public $type = 'regexp';
    public $modificators;
    public $format;

    function __construct($cfg = null)
    {
        parent::__construct($cfg);

        $this->regErrCodes(array(
            'type' => 'bad_format_' . $this->getType(),
            'min' => 'bad_size_regexp_min',
            'max' => 'bad_size_regexp_max'));
    }

    function setFormat($val)
    {
        if (!is_string($val) || empty($val)) return false;
        $this->format = $val;
    }

    function getFormat()
    {
        return $this->format;
    }

    function setModificators($val)
    {
        if (!is_string($val) || empty($val)) return false;
        $this->modificators = $val;
    }

    function getModificators()
    {
        return $this->modificators;
    }

    function validate()
    {
        $this->clearErrors();
        $format = $this->getFormat();
        $modificators = is_string($this->getModificators()) ? $this->getModificators() : '';
        $v = trim((string)$this->getValue());

        if (!preg_match("/$format/$modificators", $v, $matches)) {
            $this->error('type');
        }
        if (is_int($this->min) && mb_strlen($v) < $this->min) {
            $this->error('min', array($this->getMin()));
        }
        if (is_int($this->max) && mb_strlen($v) > $this->max) {
            $this->error('max', array($this->getMax()));
        }

        if (count($this->errors)) {
            return false;
        }

        $this->setValue($v);
        return true;
    }

    function getCmpConfProps()
    {
        $parent = parent::getCmpConfProps();
        $self = array('format' => $this->getFormat(), 'modificators' => $this->getModificators());
        return array_merge($parent, $self);
    }
}