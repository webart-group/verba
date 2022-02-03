<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 23.08.19
 * Time: 21:01
 */

namespace Mod\Payment\Transaction;


class Send extends \Verba\Mod\Payment\Transaction
{
    /**
     * @var array
     */
    public $requestData;
    public $requestMethod = 'POST';
    public $io = 0;
}
