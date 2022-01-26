<?php

namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class SitemapRefresh extends Around
{
    function run()
    {
        $this->ah->listen('beforeComplete', 'doRefresh', $this);
    }

    function doRefresh()
    {
        if(!$this->ah->isUpdated()){
            return;
        }
        $h = (int)date('H');
        if($h > 4){
            $ts = strtotime('+1 day');
        }else{
            $ts = time();
        }

        $startAt = date("Y-m-d H:i:s", mktime( 4, 0, 0, date('m', $ts), date('d', $ts), date('Y')));

        \Verba\_mod('cron')->addTask('sitemap', 'generateAndReplace', null, $startAt);
    }
}
