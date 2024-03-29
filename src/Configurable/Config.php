<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 20.08.19
 * Time: 18:18
 */
namespace Verba\Configurable;

class Config extends \Verba\Configurable
{
    protected $_confCreatePropOnFly = true;

    function __construct($cfg)
    {
        $this->applyConfigDirect($cfg);
    }

}