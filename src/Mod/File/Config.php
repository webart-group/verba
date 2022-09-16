<?php
/**
 * @author webart.group
 * @author Кудрявцев Максим (Kudriavtsev Maksym), <kmv@webart.group>
 * @copyright See copyright.md
 * Date: 23.08.19
 * Time: 19:56
 */

namespace Verba\Mod\File;


class Config extends \Verba\Configurable{

    public $rawCfg;
    protected $extensions = array();
    protected $maxUploadSize = 200000000;
    protected $keepOriginalName = false;
    protected $nameGenerator = null;
    protected $downloadUrl = false;
    protected $uploadUrl = false;
    protected $chmod = 0665;
    protected $maxNumberOfFiles = null;
    protected $renameIfExists = false;

    function __construct($cfg){

        $this->path = SYS_UPLOAD_DIR;
        $this->url = SYS_UPLOAD_URL;

        if(is_array($cfg)){
            $this->rawCfg = $cfg;
            $this->applyConfigDirect($cfg);
        }
    }

    public  function getFromRaw(){
        $args = func_get_args();

        if(count($args) > 0 && is_array($this->rawCfg)){
            if(count($args) == 1 && strpos($args[0], ' ') !== false){
                $args = preg_split("/\s+/", $args[0]);
            }
            $v = &$this->rawCfg;
            foreach($args as $c_node){
                if(!is_array($v) || !array_key_exists($c_node, $v)) return null;
                $v = &$v[$c_node];
            }
        }
        return $v;
    }

    protected function setPath($val){
        $dir = (string)$this->getPath();

        if(is_array($val)){
            if(!isset($val[0]) || !is_array($val[0]) || !isset($val[0][0])){
                throw new \Exception('Bad path generator format');
            }
            $call = count($val[0]) > 1 && isset($val[0][1])
                ? array(\Verba\_mod($val[0][0]), $val[0][1])
                : $val[0][0];
            if(count($val) > 1){
                $call_args = array_merge(array($this), array_slice($val, 1));
            }else{
                $call_args = array($this);
            }
            $dir = call_user_func_array($call, $call_args);
        }elseif(is_string($val)){
            $dir = '/' == $val{0}
                ? SYS_ROOT.$val
                : $dir.'/'.$val;
        }

        $this->path = rtrim($dir, '/');
    }

    public  function getPath(){
        return $this->path;
    }

    public  function getFilepath($fileName){
        return $this->getPath().'/'.$fileName;
    }

    protected function setUrl($val){
        $url = (string)$this->getUrl();
        if(is_array($val)){
            if(!isset($val[0]) || !is_array($val[0]) || !isset($val[0][0])){
                throw new \Exception('Bad url generator format');
            }
            $call = count($val[0]) > 1 && isset($val[0][1])
                ? array(\Verba\_mod($val[0][0]), $val[0][1])
                : $val[0][0];
            if(count($val) > 1){
                $call_args = array_merge(array($this), array_slice($val, 1));
            }else{
                $call_args = array($this);
            }
            $url = call_user_func_array($call, $call_args);
        }elseif(is_string($val) && strlen($val) != 0){
            $url = ('/' == $val{0}
                ? $val
                : $url.'/'.$val);
        }
        $this->url = rtrim($url,'/');
    }
    public  function getUrl(){
        return $this->url;
    }
    public  function getFileUrl($fileName){
        return $this->getUrl().'/'.$fileName;
    }

    protected function setDownloadUrl($val){

        if(is_array($val)){
            if(!isset($val[0]) || !is_array($val[0]) || !isset($val[0][0])){
                throw new \Exception('Bad downloadUrl generator format');
            }
            $call = count($val[0]) > 1 && isset($val[0][1])
                ? array(\Verba\_mod($val[0][0]), $val[0][1])
                : $val[0][0];
            if(count($val) > 1){
                $call_args = array_merge(array(0 => $this, 1 => false), array_slice($val, 1));
            }else{
                $call_args = array(0 => $this, 1 => false);
            }
            $this->downloadUrl = array('h' => $call, 'args' => $call_args);
        }elseif(is_string($val) && strlen($val) != 0){
            $url = (('/' == $val{0}
                ? $val
                : SYS_UPLOAD_URL.'/'.$val));
            $this->downloadUrl = rtrim($url,'/');
        }

    }
    public  function getDownloadUrl($fileName = false){
        $r = null;
        if(is_string($this->downloadUrl)){
            return is_string($fileName) && !empty($fileName)
                ? $this->downloadUrl.'/'.$fileName
                : $this->downloadUrl;
        }elseif(is_array($this->downloadUrl)){
            $call_args = $this->downloadUrl['args'];
            $call_args[1] = $fileName;
            if(func_num_args() > 1){
                $custom_args = func_get_args();
                $call_args = array_merge($call_args, array_slice($custom_args, 1));
            }
            $r = call_user_func_array($this->downloadUrl['h'], $call_args);
        }

        return $r;
    }

    protected function setUploadUrl($val){

        if(is_array($val)){
            if(!isset($val[0]) || !is_array($val[0]) || !isset($val[0][0])){
                throw new \Exception('Bad uploadUrl generator format');
            }
            $call = count($val[0]) > 1 && isset($val[0][1])
                ? array(\Verba\_mod($val[0][0]), $val[0][1])
                : $val[0][0];

            $call_args = count($val) > 1
                ? array_merge(array($this), array_slice($val, 1))
                : array($this);

            $this->uploadUrl = call_user_func_array($call, $call_args);
        }elseif(is_string($val) && strlen($val) != 0){
            $url = (('/' == $val{0}
                ? $val
                : SYS_UPLOAD_URL.'/'.$val));
            $this->uploadUrl = rtrim($url,'/');
        }
    }
    public  function getUploadUrl(){
        return $this->uploadUrl;
    }

    protected  function setKeepOriginalName($val){
        $this->keepOriginalName = (bool)$val;
    }
    public  function getKeepOriginalName(){
        return $this->keepOriginalName;
    }

    protected  function setMaxUploadSize($val){
        if(!is_numeric($val)){
            return false;
        }
        $this->maxUploadSize = intval($val);
    }
    public  function getMaxUploadSize(){
        return $this->maxUploadSize;
    }

    protected  function setMaxNumberOfFiles($val){
        $this->maxNumberOfFiles = intval($val);
    }
    public  function getMaxNumberOfFiles(){
        return $this->maxNumberOfFiles;
    }

    public function setExtensions($val = null){
        if($val === null){
            $this->extensions = array();
        }

        if(!is_array($val) && is_string($val)){
            $val = array($val);
        }

        if(!$val || !is_array($val)){
            return false;
        }

        $this->extensions = $val;
    }
    public  function inExtensions($extension){
        if(!count($this->extensions)) return true;
        return in_array((string)$extension, $this->extensions);
    }
    public  function getExtensions(){
        return $this->extensions;
    }

    public function runCustomFilenameGenerator($filename, $fdata){
        if(!is_array($this->nameGenerator)
            || !isset($this->nameGenerator[0])
            || !is_array($this->nameGenerator[0])
            || !isset($this->nameGenerator[0][0])){
            throw new \Exception('Bad Filename generator format');
        }

        $call = count($this->nameGenerator[0]) > 1 && isset($this->nameGenerator[0][1])
            ? array(\Verba\_mod($this->nameGenerator[0][0]), $this->nameGenerator[0][1])
            : $this->nameGenerator[0][0];

        $call_args = array($filename, $fdata, $this);
        if(count($this->nameGenerator) > 1){
            $call_args = array_merge($call_args, array_slice($this->nameGenerator, 1));
        }
        return call_user_func_array($call, $call_args);
    }

    public function generateFilename($filename, $fdata){

        if($this->nameGenerator !== null){
            $r = $this->runCustomFilenameGenerator($filename, $fdata);
        }else{

            if($this->getKeepOriginalName() && $filename){
                $r = $filename;
            }else{
                $pathInfo = $filename ? pathinfo($filename) : array();

                $r = \Verba\Hive::make_random_string(15, 15)
                    . ( isset($pathInfo['extension']) && !empty($pathInfo['extension'])
                        ? '.'.$pathInfo['extension']
                        : '');
            }
        }

        if(!\Verba\FileSystem\Local::fileExists($this->getPath().'/'.$r)){
            return $r;
        }

        if($this->renameIfExists){
            $r =  \Verba\FileSystem\Local::genNewFileName($this->getPath().'/'.$r, true);
        }
        return $r;
    }

    public function getNameGenerator(){
        return $this->nameGenerator;
    }

    public function getChmod(){
        return $this->chmod;
    }
    public function setChmod($val){
        $this->chmod = $val;

        return $this->chmod;
    }
}
