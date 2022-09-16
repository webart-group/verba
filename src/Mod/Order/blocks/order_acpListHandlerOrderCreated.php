<?php
class order_acpListHandlerOrderCreated extends ListHandlerField{

  function run(){
    $tpl = $this->list->tpl();

    if(!$tpl->isDefined('time-cell')) {
      $tpl->define(array(
        'time-cell' => 'acp/list/time/time-cell.tpl',
        'time-dmy' => 'acp/list/time/time-dmy.tpl',
      ));
    }

    $timestamp = strtotime($this->list->row['created']);
    $ctime = time();


    $tpl->assign(array(
      'TIME_FULL_VALUE' => utf8fix(strtolower(strftime('%Y %d %b %R', $timestamp))),
      'TIME_HM_VALUE' => date('H:i', $timestamp)
    ));

    if($ctime - $timestamp > 3600 * 12){
      if(date('Y', $timestamp) != date('Y', $ctime)){
        $dmy =  strftime('%d %b', $timestamp);
      }else{
        $dmy =  strftime('%Y %d %b', $timestamp);
      }
      $dmy = utf8fix(strtolower($dmy));

      $tpl->assign(array(
        'TIME_DMY_VALUE' => $dmy
      ));

      $tpl->parse('TIME_DMY', 'time-dmy');
    }else{
      $tpl->assign('TIME_DMY', '');
    }

    return $tpl->parse(false, 'time-cell');
  }

}
?>