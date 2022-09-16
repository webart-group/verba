<?php
namespace Verba;

class ImportExport extends User{

  function __construct($mod_id){
    parent::__construct($mod_id);
    $this->applyConfig(strtolower(get_class()));
  }

  function makeAction(&$bp){
    switch($bp['action']){
      case 'uploadform_json':
            $handler = 'uploadFormJson';
            break;
      case 'import':
            $handler = 'import';
            break;
      case 'exportform':
            $handler = 'exportForm';
            break;
      case 'exportnow':
            $handler = 'exportNow';
            break;
      default:
            $handler = false;
            break;

    }
    return $handler;
  }

  function import($bp = null, $url = array()){

    $path = $_FILES['Filedata']['tmp_name'];
    \Verba\_mod('File');

    try{
      $fCfg = \Verba\Mod\File::getFileConfig('userimport');
      if($_FILES['Filedata']['size'] > $fCfg->getMaxUploadFilesize()){
        throw new Exception('fe fileupload big_file');
      }
      $type =  \Verba\FileSystem\Local::getMIME($path);

      if(empty($type)){
        throw new Exception('fe fileupload cant_get_mimetype');
      }

      if(strpos($type, 'text') === false){
        throw new Exception('fe fileupload bad_filetype');
      }

      $f = fopen($path, 'r');
      if(!is_resource($f)){
        throw new Exception('fe fileupload cant_open_file');
      }

      $separator[0] = ',';
      $separator[1] = '~';

      $_user = \Verba\_oh('user');

      for ($i=0; $data=fgetcsv($f,1000,';',"'"); $i++){
        if($i==0){
          continue;
        }

        $phones = preg_split("/[,\s\~]/", $data[1], -1, PREG_SPLIT_NO_EMPTY);

        if(!$phones){
          continue;
        }

        $qm = new \Verba\QueryMaker($_user->getID(), false,
          array('phone', 'phone_2', 'phone_3'));

        $where_str = '';
        $phones = user::formatPhoneNumber($phones);
        if(count($phones) == 0) continue;
        foreach($phones as $idx =>  $number){
          $number_safe = $this->DB()->escape_string($number);
          $where_str .= " || (`phone` LIKE '%".$number_safe."' || `phone_2` LIKE '%".$number_safe."' || `phone_3` LIKE '%".$number_safe."')";
        }

        $qm->addWhere(substr($where_str, 3));
        $query = $qm->getQuery();
        $oRes = $this->DB()->query($query);

        if(!is_object($oRes) || $oRes->getNumRows() <= 0){
          continue;
        }
        while($row = $oRes->fetchRow()){
          $updData = array();
          $updData['discount'] = $data[2];
          $updData['discount_card'] = $data[3];
          $r = $this->addEditNow(array('action' => 'editnow', 'iid' => $row[$_user->getPAC()]), $updData);
          if(is_numeric($r->getIID())){
            $result[] = $r->getIID();
          }else{
            $error = true;
          }
        }
      }
      fclose($f);
      if($error){
        throw new Exception('aenow error msg');
      }
      return '0###'.Lang::get('fe fileupload updated').' - '.count($result);
      exit;
    }catch(Exception $e){
      $message = \Verba\Lang::get($e->getMessage());
      if(empty($message)) $message = $e->getMessage();
      return '0###'.$message;
      exit;
    }
  }

  function uploadFormJson($BParams = null){
    return \Verba\Response\Json::wrap(\Verba\_mod('file')->addEditForm(array('action' => 'new'), 'acp-file', $_REQUEST['url']));
  }

  function exportForm($bp = null){
    $tpl = $this->tpl();
    $tpl->define(array(
      'body' => '/user/export/body.tpl',
    ));
    $tpl->assign(array(
      'UEXPORT_ACTION_URL' => $this->gC('export url'),
      'SYS_IMAGES_URL' => SYS_IMAGES_URL,
    ));

    return \Verba\Response\Json::wrap($tpl->parse(false, 'body'));
  }

  function exportNow($bp = null){
    try{
      set_time_limit(7200);
      $filename = 'rh-user-export_'.date('Y-m-d-H-i-s').'_'.\Hive::make_random_string(3,3).'.xls';
      $rel= '/userexport';
      $url = SYS_VAR_URL.$rel.'/'.$filename;
      $path = SYS_VAR_DIR.$rel;
      if(!\Verba\FileSystem\Local::needDir(SYS_VAR_DIR.$rel, 0755)){
        throw new Exception('userimportexport export errors unable_to_create_export_directory');
      }
       \Verba\FileSystem\Local::dirDeleteRecursive($path, array('xls'), false, false);

      $f = fopen($path.'/'.$filename, 'w');

      if(!is_resource($f)){
        throw new Exception('userimportexport export errors unable_to_create_export_file');
      }

      include 'externals/phpexcel/PHPExcel.php';

      $_user = \Verba\_oh('user');

      $qm = new \Verba\QueryMaker($_user, false, true);

      $objPHPExcel =  new PHPExcel();
      $objPHPExcel->getProperties()->setTitle('');
      $objPHPExcel->setActiveSheetIndex(0);
      $aSheet = $objPHPExcel->getActiveSheet();

      $alpha = $alfabet = range('A', 'Z');

      $fields = $this->gc('export fields');

      for($i = 0; $i < count($fields); $i++){
        $aSheet->SetCellValue($alpha[$i].'1', $fields[$i]);
      }
      $Y = 2;

      $sqlr = $this->DB()->query("SELECT COUNT(*) as ccc FROM `".SYS_DATABASE."`.`users`");
      if($sqlr && $sqlr->getNumRows()){
        $totalRows = (int)$sqlr->getFirstValue();
      }else{
        $totalRows = 0;
      }
      for($start = 0, $n = 1000; $start < $totalRows; $start += 1000){
        $qm->addLimit($n, $start);
        $qm->makeQuery();
        $q = $qm->getQuery();
        $sqlr =  $qm->run();
        if(!$sqlr || !$sqlr->getNumRows()){
          break;
        }

        while($row = $sqlr->fetchRow()){
          for($i = 0; $i < count($fields); $i++){
            $field = $fields[$i];
            $val = (string)$row[$field];

            if(!empty($val) && $val{0} == '='){
              $val = "'".$val."'";
            }
            $aSheet->SetCellValue($alpha[$i].$Y, $val);
          }
          $Y++;
        }
      }

      $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
      $objWriter->save($path.'/'.$filename);

      return \Verba\Response\Json::wrap(array('fileUrl' => $url, 'filename' => $filename));
    }catch(Exception $e){
      $last = error_get_last();
      $this->log()->error($e->getMessage());
      $this->log()->error(var_export($last, true));
      $this->log()->error(var_export($row, true));
      return \Verba\Response\Json::wrap(\Verba\Lang::get($e->getMessage()));
    }
  }
}
?>