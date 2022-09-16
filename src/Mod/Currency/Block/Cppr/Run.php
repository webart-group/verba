<?php

namespace Verba\Mod\Currency\Block\Cppr;

class Run extends \Verba\Block\Json
{

    function build()
    {
//    $Cron = \Verba\_mod('cron');
//    $taskId = $Cron->addTask('','cron_shopRecalcCurrencyPaysysPairsRatio');
//
//    if($taskId){
//      $task = $Cron->getTask($taskId);
//      list($rs, $rd, $ud) = $Cron->runTask($task);
//      if($ud['lastWorkTime']){
//        $this->content = $ud['lastWorkTime'];
//      }
//    }

        \Verba\_mod('shop')->refreshCpprSystem();
        $this->content = 1;
        return $this->content;
    }

}
