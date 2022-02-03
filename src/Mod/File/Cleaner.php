<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 23.08.19
 * Time: 19:57
 */

namespace Mod\File;


class Cleaner extends \Verba\Base{
    protected $fCfg;
    protected $filename;
    protected $oh;

    function __construct($oh, $cfg, $filename){
        \Verba\_mod('file');
        if(is_string($cfg)){
            $this->fCfg = \Mod\File::getFileConfig($cfg);
        }elseif($cfg instanceof Config){
            $this->fCfg = $cfg;
        }else{
            throw new \Exception('Bad file Config name/instance');
        }
        if(!($oh instanceof \Model)){
            throw new \Exception('Bad oh resource');
        }
        $this->oh = $oh;

        if($filename){
            $this->setFilename($filename);
        }
    }

    function setFilename($val){
        $this->filename = (string)$val;
    }

    function delete(){

        if(empty($this->filename)){
            $this->log()->error('Bad file name');
            return false;
        }

        $fsh = new  \Verba\FileSystem\Local();
        $c_path = $this->fCfg->getFilepath($this->filename);
        if(!$fsh->isFile($c_path) || !$fsh->del_file($c_path)){
            $this->log()->warning('Unable to delete file: ['.var_export($c_path, true).']', false);
        }
        return true;
    }
}
