<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 23.08.19
 * Time: 21:03
 */

namespace Mod\Payment\Transaction;


class Receive
    extends \Verba\Mod\Payment\Transaction
    implements ReceiveInterface
{

    public $io = 1;

    function successPayment(){
        return false;
    }
}
