<?php
/*
class FileAdmin extends \Verba\Mod{

  function makeAction($bp){
    switch($bp['action']){
      case 'list':
        $handler = 'manageList'; break;

      case 'update':
        $handler = 'cuNow'; break;

      case 'create':
        $handler = 'cuNow_create'; break;

      case 'newnow':
      case 'editnow':
        $handler = 'addEditNow'; break;

      default:
        $handler = false;
        break;
    }

    if(!$handler){
      $handler = parent::makeAction($bp);
    }

    return $handler;
  }

  function manageList($bp = null){
    $bp = $this->extractBParams($bp);
    if(!isset($bp['cfg']) || !is_string($bp['cfg']) || empty($bp['cfg'])){
      $bp['cfg'] = 'acp-filekey';
    }
    return \Verba\Response\Json::wrap(true, $this->baseList($bp));
  }

  // \Act\AddEdit as Json wrap
  function cuNow_create($bp = null){
    try{
      $mod = \Verba\_mod('File');
      $bp['action'] = 'create';
      $r = $mod->addEditNow($bp);
      if($r instanceof \Act\AddEdit){
        $r = array($r);
      }elseif(!is_array($r) || !count(!$r) || !(current($r) instanceof \Act\AddEdit) ){
        throw new Exception('Unexpected AddEdit result');
      }
      $response = array('files'=>array());
      foreach($r as $ae){
        if($ae instanceof \Act\AddEdit){
          $id = $ae->getIID();
          if(!$id){
            throw new Exception($ae->log()->getMessagesAsStr('error'));
          }
          $odata = $ae->getObjectData();
          $response['files'][] = array(
            'name' => $odata['filename'],
            'size' => $odata['size'],
            'url' => '',
            'thumbnail_url' => '',
            'delete_url' => '/acp/h/gamecardadmin/filekey/remove?iid',
            'delete_type' => 'POST',
          );
        }elseif($ae instanceof Exception){
          throw $ae;
        }else{
          throw new Exception('Unexcpected AddEdit result');
        }
      }
      return json_encode($response);
    }catch(Exception $e){
      return json_encode($e->getMessage());
    }
  }

}*/
