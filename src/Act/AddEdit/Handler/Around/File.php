<?php
//unhandled
namespace Verba\Act\AddEdit\Handler\Around;

use \Verba\Act\AddEdit\Handler\Around;

class File extends Around
{
  function run(){
    if(!isset($cValue)){
      return null;
    }
    $storagefileName = '';
    $fsize = 0;
    $originalName = '';
    try{
      $acode = $this->A->getCode();
      if(!isset($this->gettedObjectData['_'.$acode.'_config'])){
        throw new \Exception('File config value is not found');
      }
      $fs = new  \Verba\FileSystem\Local();
      \Verba\_mod('File');
      $fCfg = File::getFileConfig($this->ah()->gettedObjectData['_'.$acode.'_config']);
      if(!$fCfg){
        throw new \Exception('File config is not found');
      }

      //uploaded
      if(isset($cValue['_temp_name']) && !empty($cValue['_temp_name'])){
        if(!$fs->isFile($cValue['_temp_name'])){
          throw new \Exception('Uploaded temp file is not found or unavailable');
        }

        $originalName = $cValue['_name'];

        //filesize
        $fsize = filesize($cValue['_temp_name']);
        if(!isset($this->extendedData[$acode]) || !is_array($this->ah()->extendedData[$acode])){
          $this->ah()->extendedData[$acode] = array();
        }
        if($fCfg->getMaxUploadSize() > 0 && ((int)$fsize > $fCfg->getMaxUploadSize())){
          throw new \Exception('File is too big');
        }

        // gen storage filename
        $storagefileName = $fCfg->generateFilename($cValue['_name'], $this->ah()->gettedObjectData);

        //move uploaded file from temp to target destination
        if(!$fs->needDir($fCfg->getPath())){
          throw new \Exception('Unable to create file storage dir ['.$fCfg->getPath().']');
        }

        if($this->ah()->extendedData['forcedFileCopyInstedMove'] == 1){
          $cr = copy($cValue['_temp_name'], $fCfg->getPath().'/'.$storagefileName);
        }else{
          $cr = $fs->move($cValue['_temp_name'], $fCfg->getPath().'/'.$storagefileName);
        }

        if(!$cr){
          throw new \Exception('cant move uploaded file');
        }
        if($fCfg->getChmod()){
          chmod($fCfg->getPath().'/'.$storagefileName, $fCfg->getChmod());
        }
      }

      // remove previous file if new file was reuploaded
      $exists_value = $this->getExistsValue($this->A->getCode());
      if($this->action == 'edit'
        && (isset($cValue['_remove']) && $cValue['_remove'] == 1)
        || (is_string($exists_value) && !empty($exists_value)
          && $exists_value != $storagefileName)
      ){
        $iu = new \FileCleaner($this->oh, $fCfg, $exists_value);
        $removed = $iu->delete();
      }

      //$fi = $fs->getFileInfoResource(FILEINFO_MIME_TYPE);
      //$this->extendedData[$acode]['mime'] = $fi->file($cValue['_temp_name']);
      $this->ah()->extendedData[$acode]['size'] = $fsize;
      $this->ah()->extendedData[$acode]['name'] = $originalName;

      return $storagefileName;

    }catch(\Exception $e){
      $this->log()->error($e->getMessage());
      return false;
    }
  }
}
