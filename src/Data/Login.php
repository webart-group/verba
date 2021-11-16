<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 20.08.19
 * Time: 19:35
 */

namespace Verba\Data;


class Login extends \Verba\Data
{
    public $type = 'login';
    public $format = "[\w\-\.]+";

}