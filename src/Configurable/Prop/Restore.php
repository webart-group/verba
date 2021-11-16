<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 20.08.19
 * Time: 18:23
 */
namespace Configurable\Prop;

class Restore
{
    function run($obj, $propName)
    {
        return is_array($obj::$_config_default) && array_key_exists($propName, $obj::$_config_default)
            ? $obj::$_config_default[$propName]
            : null;
    }
}
