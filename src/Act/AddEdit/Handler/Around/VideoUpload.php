<?php
// unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class VideoUpload extends Around
{
  function run()
  {
    $attr_code = $A->getCode();
    if(!isset($this->gettedObjectData['_'.$attr_code.'_config'])){
      return null;
    }
    $mod_video = \Verba\_mod('video');
    $attr_code = $A->getCode();
    $vCfg = \video::getVideoConfig($this->gettedObjectData['_'.$attr_code.'_config']);
    if(!$vCfg){
      $this->log()->error('Unable to parse Video Conf data');
      return false;
    }

    //Проверка по сурсовому файлу
    if(empty($this->gettedObjectData['_tmp_name']) || !\Verba\FileSystem\Local::isFile($this->gettedObjectData['_tmp_name'])){
      $this->log()->error('File data error');
      return false;
    }

    //Получение инфо по видеофайлу и проверка на валидность
    $fileInfo = $mod_video->getVideoInfo($this->gettedObjectData['_tmp_name']);
    if(!isset($fileInfo['ID_VIDEO_ID'])){
      $this->log()->error('Impossible to obtain data on the video file');
      return false;
    }

    // имя хранения видео
    $fsh = new  \Verba\FileSystem\Local();
    $dir = $vCfg->getPath();
    if($vCfg->getKeepOriginalName()){
      if($fsh->fileExists($dir.'/'.$this->gettedObjectData['filename'])){
        if(is_string($generatedName  = $fsh->genNewFileName($dir.'/'.$this->gettedObjectData['filename'], true))){
          $storagefileName = $vCfg->getPrefix().$generatedName;
        }
      }else{
        $storagefileName = $vCfg->getPrefix().$this->gettedObjectData['filename'];
      }
    }

    if(!isset($storagefileName)){
      $storagefileName = $vCfg->getPrefix().\Hive::make_random_string(10, 10).'.'.$vCfg->getFormat();
    }
    $refreshedData = array();
    if(isset($fileInfo['ID_VIDEO_WIDTH'])){
      $refreshedData['width'] = (int)$fileInfo['ID_VIDEO_WIDTH'];
    }
    if(isset($fileInfo['ID_VIDEO_HEIGHT'])){
      $refreshedData['height'] = (int)$fileInfo['ID_VIDEO_HEIGHT'];
    }
    if(isset($fileInfo['ID_VIDEO_FORMAT'])){
      $refreshedData['format'] = $fileInfo['ID_VIDEO_FORMAT'];
    }

    $refreshedData['path'] = $this->gettedObjectData['_tmp_name'];
    $refreshedData['status'] = 1;
    $this->setGettedObjectData($refreshedData);

    return $storagefileName;
  }
}
