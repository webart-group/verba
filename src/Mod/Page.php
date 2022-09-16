<?php
namespace Verba\Mod;

class Page extends \Verba\Mod{
    use \Verba\ModInstance;
  private $cache_dir = '';

  function init(){
    $this->cache_dir = SYS_CACHE_DIR.'/'.$this->cache_subway;
     \Verba\FileSystem\Local::needDir($this->cache_dir);
  }

  function getBlocksCacheDir(){
    $this->cache_dir;
  }

  function cron_purgeJsCssCache(){
    $dateFormat = 'Y-m-d H:i:s';
     \Verba\FileSystem\Local::scandir($this->gC('cache css path'), 1, true, array($this, 'validateOrDeleteJsCssCacheFile'));
     \Verba\FileSystem\Local::scandir($this->gC('cache js path'), 1, true, array($this, 'validateOrDeleteJsCssCacheFile'));
    $startAt = date($dateFormat, strtotime("+1 week"));

    return array(2, array('startAt' => $startAt));
  }

  function validateOrDeleteJsCssCacheFile($filepath){
    $stat =  \Verba\FileSystem\Local::fileStat($filepath);
    if(time() - $stat['mtime'] > 3600 * 24 * 30){
       \Verba\FileSystem\Local::del_file($filepath);
    }
  }
}
