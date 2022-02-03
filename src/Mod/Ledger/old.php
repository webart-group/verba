<?php
namespace Mod;

class LedgerAdmin extends \Verba\Mod{

  public $typeCodes = array(
    'income' => 1,
    'cost' => 0,
  );

  function makeAction(&$bp){
    global $S;
    switch($bp['action']){
      case 'ledgerentrylist':
          $handler = 'ledgerentryList';
          break;
      case 'list':
          $handler = 'listJson';
          break;
      case 'finstatperiodstaff':
          $handler = 'finstatPeriodStaff';
          break;
      default :
        $handler = null;
    }

    if(!$handler && !isset($bp['__handledByUrlFragment'])){
      $uf1 = strtolower($S->url_fragments[1]);
      switch($uf1){
        case 'cost':
        case 'income':
          $bp['action'] = isset($S->url_fragments[2]) ? $S->url_fragments[2] : 'list' ;
          $bp['ot_code'] = 'ledgerentry';
          $bp['type'] = $uf1;
          $bp['ot_id'] = \Verba\_oh('ledgerentry')->getID();
          $handler = 'entryRedirector';
          break;
        case 'finstatday':
          $bp['action'] = isset($S->url_fragments[2]) ? $S->url_fragments[2] : 'list' ;
          $bp['ot_code'] = 'finstatday';
          $bp['ot_id'] = \Verba\_oh('finstatday')->getID();
          $handler = 'finstatdayRedirector';
          break;
        case 'staff':
          $bp['action'] = isset($S->url_fragments[2]) ? $S->url_fragments[2] : 'list' ;
          $bp['ot_code'] = 'employee';
          $bp['ot_id'] = \Verba\_oh($bp['ot_code'])->getID();
          $handler = 'staffRedirector';
          break;
        case 'ledgerstaff':
          $bp['action'] = isset($S->url_fragments[2]) ? $S->url_fragments[2] : 'list' ;
          $bp['ot_code'] = 'ledgerstaff';
          $bp['ot_id'] = \Verba\_oh($bp['ot_code'])->getID();
          $handler = 'ledgerstaffRedirector';
          break;
        case 'finstatstaff':
          $bp['action'] = isset($S->url_fragments[2]) ? $S->url_fragments[2] : 'list' ;
          $bp['ot_code'] = 'finstatstaff';
          $bp['ot_id'] = \Verba\_oh($bp['ot_code'])->getID();
          $handler = 'finstatstaffRedirector';
          break;
      }
    }

    if(!$handler){
      $handler = parent::makeAction($bp);
    }

    return $handler;
  }

  function ledgerentryList($bp = null){
    $bp = $this->extractBParams($bp);
    $type = isset($bp['type']) ? $bp['type'] : 'cost';

    if(!isset($bp['cfg'])){
      $bp['cfg'] = 'acp-ledgerentry acp-ledgerentry-'.$type;
    }

    $list = init_list_table($bp['ot_id'], null, $bp);
    $qm = $list->QM();
    $qm->addWhere($this->typeCodes[$type], 'type');
    $l = $list->generateList();
    $q = $list->QM()->getQuery();

    return \Verba\Response\Json::wrap(true, $l);
  }

  function entryRedirector($bp = null){
    global $S;
    $bp['__handledByUrlFragment'] = true;
    $bp = $this->extractBParams($bp);
    switch($bp['action']){
      case 'cuform':
        if(isset($bp['iid']) && !empty($bp['iid'])){
          $bp['action'] = 'updateform';
        }else{
          $bp['action'] = 'createform';
        }
        $bp['cfg'] = 'acp-ledgerentry acp-ledgerentry-'.$bp['type'].' acp-ledgerentry-'.$bp['action'];
        break;
      case 'create':
      case 'update':
        break;
       case 'remove':
        $bp['action'] = 'remove';
        break;
      case null:
      case '':
      case 'list':
        $bp['action'] = 'ledgerentrylist';
        break;
    }

    if(!isset($r)){
      $r = $this->dispatcher($bp);
    }
    return $r;

  }

  function handleAmount($list, $row){
    return number_format(\Verba\reductionToCurrency($row['amount']), 2, '.', ' ');
  }

  function handleAmountBase($list, $row){
    return number_format(\Verba\reductionToCurrency($row['amountBase']), 2, '.', ' ');
  }

  // FINSTAT

  function cron_finstatCountPreviousDay($bp){

    $requiredTime = strtotime("-2 day");
    $r = $this->finstatPeriod($requiredTime);
    $r = $this->finstatPeriodStaff($requiredTime);

    $dateFormat = 'Y-m-d H:i:s';
    $nextStart = strtotime("+1 day");
    $startAt = date($dateFormat,
      mktime(1,0,0,date('m',$nextStart),date('d',$nextStart),date('Y', $nextStart)));

    return array(2, array('startAt' => $startAt));
  }

  function finstatPeriod($timestampFrom, $timestampTill = null){

    try{
      if(!$timestampTill){
        $timestampTill = time();
      }
      if(!\Verba\isTimestampValid($timestampFrom) || !\Verba\isTimestampValid($timestampTill)){
        throw new Exception('Bad timestamp');
      }

      $datefrom = date('Y-m-d 00:00:00', $timestampFrom);
      $datetill = date('Y-m-d 23:59:59', $timestampTill);

      //Gold GrossProfit
      $_gw = \Verba\_oh('goldwow');
      $q = "SELECT
DATE(`created`) as `date`,
SUM(`topay`) as `topay`,
SUM(`profit`) as `profit`,
SUM(`discount`) as `discount`,
SUM(`taxPaysystem`) as `taxPaysystem`,
SUM(`cost`) as `total`
FROM ".$_gw->vltURI()."
WHERE created >= '".$datefrom."' && created <= '".$datetill."' && `status` >= '11110'
GROUP BY DATE(`created`)";

      $sqlr = $this->DB()->query($q);
      if(!$sqlr){
        throw new Exception('SQL Error:'.$this->DB()->getLastError());
      }

      $r = array();
      $zerro = array(
          'topay' => 0,
          'profit' => 0,
          'discount' => 0,
          'taxPaysystem' => 0,
          'total' => 0,
          'incomeAdditional' => 0,
          'expenseAdditional' => 0,
        );
      while($row = $sqlr->fetchRow()){
        $r[$row['date']] = array_replace_recursive($zerro, $row);
      }

      //ArcheAge
      //Gold GrossProfit
      $_gaa = \Verba\_oh('goldaa');
      $q = "SELECT
DATE(`created`) as `date`,
SUM(`topay`) as `topay`,
SUM(`profit`) as `profit`,
SUM(`discount`) as `discount`,
SUM(`taxPaysystem`) as `taxPaysystem`,
SUM(`cost`) as `total`
FROM ".$_gaa->vltURI()."
WHERE created >= '".$datefrom."' && created <= '".$datetill."' && `status` >= '11110'
GROUP BY DATE(`created`)";

      $sqlr = $this->DB()->query($q);
      if(!$sqlr){
        throw new Exception('SQL Error:'.$this->DB()->getLastError());
      }

      while($row = $sqlr->fetchRow()){
        if(isset($r[$row['date']])){
          $r[$row['date']]['topay'] += $row['topay'];
          $r[$row['date']]['profit'] += $row['profit'];
          $r[$row['date']]['discount'] += $row['discount'];
          $r[$row['date']]['taxPaysystem'] += $row['taxPaysystem'];
          $r[$row['date']]['total'] += $row['total'];
        }else{
          $r[$row['date']] = array_replace_recursive($zerro, $row);
        }

      }

      $_le = \Verba\_oh('ledgerentry');
      $q = "SELECT
DATE(`date`) as `date`,
SUM(IF(`type` = 1, `amountBase`, 0)) as `incomeAdditional`,
SUM(IF(`type` = 0, `amountBase`, 0)) as `expenseAdditional`
FROM ".$_le->vltURI()."
WHERE `date` >= '".$datefrom."' && `date` <= '".$datetill."'
GROUP BY DATE(`date`)";

      $sqlr = $this->DB()->query($q);
      if(!$sqlr){
        throw new Exception('SQL Error:'.$this->DB()->getLastError());
      }
      while($row = $sqlr->fetchRow()){
        if(!isset($r[$row['date']])){
          $r[$row['date']] = $zerro;
        }
        $r[$row['date']]['incomeAdditional'] = $row['incomeAdditional'];
        $r[$row['date']]['expenseAdditional'] = $row['expenseAdditional'];
      }

      if(!count($r)){
        return 0;
      }

      //DB update
      $qPrefix = "INSERT INTO `".SYS_DATABASE."`.`finstat` (
`date`,
`grossIncome`,
`profit`,
`spanding`,
`netProfit`,
`netProfitPercent`,
`discount`,
`taxPaysystem`,
`total`,
`incomeAdditional`,
`expenseAdditional`
 ) VALUES ";

      $qSuffix = "\nON DUPLICATE KEY UPDATE
`grossIncome` = VALUES(`grossIncome`),
`profit` = VALUES(`profit`),
`spanding` = VALUES(`spanding`),
`netProfit` = VALUES(`netProfit`),
`netProfitPercent` = VALUES(`netProfitPercent`),
`discount` = VALUES(`discount`),
`taxPaysystem` = VALUES(`taxPaysystem`),
`total` = VALUES(`total`),
`incomeAdditional` = VALUES(`incomeAdditional`),
`expenseAdditional` = VALUES(`expenseAdditional`)
";
      $v = array();
      foreach($r as $day => $d){
        $profit = round($d['profit'] + $d['incomeAdditional'], 2);
        $spanding = round($d['expenseAdditional'], 2);
        $netProfit = round($d['profit'] + $d['incomeAdditional'] - $spanding, 2);
        $netProfitPercent = $d['topay'] > 0
          ? round(($netProfit / $d['topay']) * 100, 2)
          : 0;

        $v[] = '('
        ."'".$day."',"
        ."'".$d['topay']."'," // grossIncome
        ."'".$profit."'," //profit
        ."'".$spanding."'," //spanding
        ."'".$netProfit."'," //netProfit
        ."'".$netProfitPercent."'," //netProfitPercent
        ."'".round($d['discount'],2)."'," //discount
        ."'".round($d['taxPaysystem'],2)."'," //taxPaysystem
        ."'".round($d['total'],2)."'," //total
        ."'".round($d['incomeAdditional'],2)."'," //incomeAdditional
        ."'".round($d['expenseAdditional'],2)."'" //costsAdditional
        .')';
      }

      $q = $qPrefix. implode(',', $v).$qSuffix;
      $sqlr = $this->DB()->query($q);

      return $r;
    }catch(Exception $e){

      $this->log()->error($e->getMessage(). "\n\n \$timestampFrom: ".var_export($timestampFrom, true).'('.date('Y-m-d H:i:s', $timestampFrom).'), $timestampTill: '.var_export($timestampTill, true).' ('.date('Y-m-d H:i:s', $timestampTill).')');

      return false;
    }

  }

  function finstatdayRedirector($bp = null){
    global $S;
    $bp['__handledByUrlFragment'] = true;
    $bp = $this->extractBParams($bp);
    switch($bp['action']){
      case 'recountperiodtab':
        $method = 'recountPeriodtab';
        break;
      case 'recountperiod':
        $method = 'recountPeriod';
        break;
      case 'cuform':
        if(isset($bp['iid']) && !empty($bp['iid'])){
          $bp['action'] = 'updateform';
        }else{
          $bp['action'] = 'createform';
        }
        $bp['cfg'] = 'acp-finstatday acp-finstatday-'.$bp['action'];
        break;
      case 'create':
      case 'update':
        break;
      case 'remove':
        $bp['action'] = 'remove';
        break;
      case null:
      case '':
      case 'list':
        $bp['cfg'] = 'acp-finstatday';
        $bp['action'] = 'list';
        break;
    }

    if(isset($method)){
      $r = $this->$method($bp);
    }else{
      $r = $this->dispatcher($bp);
    }
    return $r;

  }

  function handleFinstatDate($list, $row){
    return $row['date'];
  }

  function recountPeriodTab(){
    $tpl = $this->tpl();
    $tpl->define(array(
      'tab' => '/ledger/acp/recountperiod/tab.tpl'
    ));

    $tpl->assign(array(
      'DATESELECT_REGION' => SYS_LOCALE,
      'DATEPERIOD_FROM_VALUE' => $this->value['from'] ? date($this->dateFormat['display'],$this->value['from']) : '',
      'DATEPERIOD_TILL_VALUE' => $this->value['till'] ? date($this->dateFormat['display'],$this->value['till']) : '',
    ));

    return \Verba\Response\Json::wrap(true, $tpl->parse(false, 'tab'));
  }

  function recountPeriod(){
    try{
      $from = isset($_POST['from']) ? strtotime($_POST['from']) : false;
      $till = isset($_POST['till']) && !empty($_POST['till']) ? strtotime($_POST['till']) : time();
      if(!\Verba\isTimestampValid($from) || !\Verba\isTimestampValid($till)){
        throw new Exception('Bad period range');
      }

      $r = $this->finstatPeriod($from, $till);
      $r = $this->finstatPeriodStaff($from, $till);

      return \Verba\Response\Json::wrap(true, \Verba\Lang::get('ledger acp recountperiod complete'));

    }catch(Exception $e){
      $this->log()->error($e->getMessage());
      return \Verba\Response\Json::wrap(false, $e->getMessage());
    }
  }

  //STAFF

  function finstatPeriodStaff($timestampFrom, $timestampTill = null){
    //$timestampFrom = strtotime('2012-08-01 22:23');
    try{
      if(!$timestampTill){
        $timestampTill = time();
      }
      if(!\Verba\isTimestampValid($timestampFrom) || !\Verba\isTimestampValid($timestampTill)){
        throw new Exception('Bad timestamp');
      }

      $datefrom = date('Y-m-d 00:00:00', $timestampFrom);
      $datetill = date('Y-m-d 23:59:59', $timestampTill);

      $y0 = (int)date('Y', $timestampFrom);
      $m0 = (int)date('m', $timestampFrom);
      $y1 = (int)date('Y', $timestampTill);
      $m1 = (int)date('m', $timestampTill);
      $range = array();
      for(;$y0 <= $y1; $y0++){
        for(;($y0 != $y1 && $m0 < 13) || ($y0==$y1 && $m0 <= $m1); $m0++){
          $range[$y0.'-'.str_pad($m0, 2, '0', STR_PAD_LEFT)] = mktime(0,0,0,$m0,1,$y0);
        }
        $m0 = 1;
      }

      $_finstat = \Verba\_oh('finstatday');
      $_ledgerstaff = \Verba\_oh('ledgerstaff');
      $_employee = \Verba\_oh('employee');

      $q = "SELECT id, `since`, `percentage` FROM ".$_employee->vltURI()." WHERE `percentage` > 0";
      $sqlr = $this->DB()->query($q);
      if(!$sqlr){
        throw new Exception('SQL Error:'.$this->DB()->getLastError());
      }
      if(!$sqlr->getNumRows()){
        return;
      }
      $prt = array();
      while($row = $sqlr->fetchRow()){
        $prt[$row['id']] = array(
          'percentage' => (float)$row['percentage'],
          'range' => array(),
          'sinceTimestamp' => $row['since'] != '0000-00-00 00:00:00' ? strtotime($row['since']) : false,
        );
      }

      $q = "SELECT
DATE_FORMAT(`date`,'%Y-%m') as `date`,
SUM(`amountBase`) as `amountBase`,
ls.`employeeId`
FROM ".$_ledgerstaff->vltURI()." as ls
RIGHT JOIN ".$_employee->vltURI()." as sf ON sf.id = ls.employeeId
WHERE date >= '".$datefrom."' && date <= '".$datetill."' && sf.percentage > 0
GROUP BY DATE_FORMAT(`date`,'%Y-%m'), employeeId";

      $sqlr = $this->DB()->query($q);
      if(!$sqlr){
        throw new Exception('SQL Error:'.$this->DB()->getLastError());
      }
      $zerro = array('paid' => 0);
      while($row = $sqlr->fetchRow()){
        $prt[$row['employeeId']]['range'][$row['date']] = $zerro;
        $prt[$row['employeeId']]['range'][$row['date']]['paid'] = (float)$row['amountBase'];
      }

      $q = "SELECT
DATE_FORMAT(f.`date`,'%Y-%m') as `date`,
SUM(f.netProfit) AS netProfit
FROM ".$_finstat->vltURI()." AS f
WHERE f.`date` >= '".$datefrom."' && f.`date` <= '".$datetill."'
GROUP BY DATE_FORMAT(f.`date`,'%Y-%m')";
      $sqlr = $this->DB()->query($q);
      if(!$sqlr){
        throw new Exception('SQL Error:'.$this->DB()->getLastError());
      }
      $netProfit = array();
      while($row = $sqlr->fetchRow()){
        $netProfit[$row['date']] = (float)$row['netProfit'];
      }
      $qPrefix = "INSERT INTO ".SYS_DATABASE.".`finstatstaff`
      (`date`, `employeeId`, `profit`, `paid`, `balance`) VALUES ";
      $qSuffix = "\nON DUPLICATE KEY UPDATE
`profit` = VALUES(`profit`),
`paid` = VALUES(`paid`),
`balance` = VALUES(`balance`)
";
      $values = array();

      foreach($prt as $employeeId => $empData){

        foreach($range as $cdate => $ctime){
          if(is_int($empData['sinceTimestamp'])
          && $ctime < $empData['sinceTimestamp']){
            continue;
          }
          $cNetProfit = round((isset($netProfit[$cdate]) ? $netProfit[$cdate] : 0), 2, PHP_ROUND_HALF_DOWN);

          $findata = isset($empData['range'][$cdate])
            ? $empData['range'][$cdate]
            : $zerro;

          $paid = round($findata['paid'], 2, PHP_ROUND_HALF_DOWN);

          $profit = round(($cNetProfit / 100) *  $empData['percentage'], 2, PHP_ROUND_HALF_DOWN);
          $balance = round($profit - $paid, 2, PHP_ROUND_HALF_DOWN);
          $values[] = "('".date('Y-m-t',strtotime($cdate))."','".$employeeId."','".$profit."','".$paid."','".$balance."')";
          if(count($values) > 4){
            $qUpdate = $qPrefix . implode(",", $values) . $qSuffix;
            $this->DB()->query($qUpdate);
            $values = array();
          }
        }
      }
      if(count($values)){
        $qUpdate = $qPrefix . implode(",", $values) . $qSuffix;
        $this->DB()->query($qUpdate);
      }

      return true;
    }catch(Exception $e){

      $this->log()->error($e->getMessage(). "\n\n \$timestampFrom: ".var_export($timestampFrom, true).'('.date('Y-m-d H:i:s', $timestampFrom).'), $timestampTill: '.var_export($timestampTill, true).' ('.date('Y-m-d H:i:s', $timestampTill).')');

      return false;
    }

  }

  function staffRedirector($bp = null){
    global $S;
    $bp['__handledByUrlFragment'] = true;
    $bp = $this->extractBParams($bp);
    switch($bp['action']){
      case 'cuform':
        if(isset($bp['iid']) && !empty($bp['iid'])){
          $bp['action'] = 'updateform';
        }else{
          $bp['action'] = 'createform';
        }
        $bp['cfg'] = 'acp-staff acp-staff-'.$bp['action'];
        break;
      case 'create':
      case 'update':
        break;
      case 'remove':
        $bp['action'] = 'remove';
        break;
      case null:
      case '':
      case 'list':
        $bp['cfg'] = 'acp-staff';
        $bp['action'] = 'list';
        break;
    }

    if(isset($method)){
      $r = $this->$method($bp);
    }else{
      $r = $this->dispatcher($bp);
    }
    return $r;

  }

  function ledgerstaffRedirector($bp = null){
    global $S;
    $bp['__handledByUrlFragment'] = true;
    $bp = $this->extractBParams($bp);
    switch($bp['action']){
      case 'cuform':
        if(isset($bp['iid']) && !empty($bp['iid'])){
          $bp['action'] = 'updateform';
        }else{
          $bp['action'] = 'createform';
        }
        $bp['cfg'] = 'acp-ledgerstaff acp-ledgerstaff-'.$bp['action'];
        break;
      case 'create':
      case 'update':
        break;
      case 'remove':
        $bp['action'] = 'remove';
        break;
      case null:
      case '':
      case 'list':
        $bp['cfg'] = 'acp-ledgerstaff';
        $bp['action'] = 'list';
        break;
    }

    if(isset($method)){
      $r = $this->$method($bp);
    }else{
      $r = $this->dispatcher($bp);
    }
    return $r;

  }

  function handleBalanceDate($list, $row){
    return date('Y-m',strtotime($row['date']));
  }

  function handleEmployee($list, $row){
    return $row['employeeId__value'];
  }

  function finstatstaffRedirector($bp = null){
    global $S;
    $bp['__handledByUrlFragment'] = true;
    $bp = $this->extractBParams($bp);
    switch($bp['action']){
//      case 'cuform':
//        if(isset($bp['iid']) && !empty($bp['iid'])){
//          $bp['action'] = 'updateform';
//        }else{
//          $bp['action'] = 'createform';
//        }
//        $bp['cfg'] = 'acp-finstatstaff acp-finstatstaff-'.$bp['action'];
//        break;
//      case 'create':
//      case 'update':
//        break;
//      case 'remove':
//        $bp['action'] = 'remove';
//        break;
      case null:
      case '':
      case 'list':
        $bp['cfg'] = 'acp-finstatstaff';
        $bp['action'] = 'list';
        break;
    }

    if(isset($method)){
      $r = $this->$method($bp);
    }else{
      $r = $this->dispatcher($bp);
    }
    return $r;

  }


}
?>