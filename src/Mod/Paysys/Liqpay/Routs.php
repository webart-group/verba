<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 16.08.19
 * Time: 17:54
 */

namespace Mod\Local;


class Routs extends \Block
{
    function route(){
        $rq = clone $this->request;
        array_shift($rq->uf);
        switch($rq->uf[0]){
            case 'testnotifyform':
                $h = new \paysys_liqpay_AcpTestNotifyForm($rq);
                break;
            case 'testnotify':
                $h = new \paysys_liqpay_AcpTestNotifyHandler($rq);
                break;
        }
        if(!isset($h)){
            throw new \Exception\Routing();
        }

        return $h->route();
    }
}
