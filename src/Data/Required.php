<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 20.08.19
 * Time: 18:30
 */
namespace Verba\Data;

class Required extends \Verba\Data
{
    public $type = 'required';

    function __construct($cfg = null)
    {
        parent::__construct($cfg);
        $this->regErrCodes(array('required' => 'required'));
    }

    function validate()
    {
        if (!$this->getValue()) {
            $this->error('required');
            return false;
        } else return true;
    }
}