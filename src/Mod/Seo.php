<?php
namespace Mod;

class Seo extends \Verba\Mod{
    use \Verba\ModInstance;

  static function genItemUrlFragment($oh, $row){
    $oh = \Verba\_oh($oh);
    $r = isset($row['url_code']) && !empty($row['url_code'])
      ? preg_replace('/\s+/', '', $row['url_code']).'-'.$row[$oh->getPAC()]
      : $row[$oh->getPAC()];
    return $r;
  }

  static function extractItemIdFromUrlFragment($str, $oh = false){
    if(!is_string($str)
    || !preg_match("/\-(\d+)$/", $str, $buff)){
      return false;
    }
    return $buff[1];
  }

  static function idToSeoStr($row, $data = array(), $url = '/'){

    $data =(array)$data;
    $url = (string)$url;
    if(is_object($row) && $row instanceof \Model\Item){
      $row = $row->toArray();
    }
    $oh = \Verba\_oh($row['ot_id']);
    if(!is_array($row) || !isset($row[$oh->getPAC()])){
      return $url;
    }
    if(isset($row['url_code']) && !empty($row['url_code'])){
      $url .= $row['url_code'].'-';
    }
    $url .= $oh->getCode().'-'.$row[$oh->getPAC()];
    if(!empty($data)){
      $U = new \Url($url);
      $U->setParams($data);
      $url = $U->get();
    }

    return  $url;
  }

  static function extractImageUrlFromText($src){
    preg_match("/\<img([^>]+)\/\>/i", $src, $buff);
    if(isset($buff[1]) && !empty($buff[1])
    && preg_match("/src=\"(.*?)\"/i", $buff[1], $buff)){
      $url = new \Url($buff[1][0]);
      return $url->get(true);
    }
    return false;
  }

  function addLiveInternetMetrika(){
    if(!SYS_IS_PRODUCTION){
      return '';
    }
    $tpl = $this->tpl();
    $tpl->define(array('li_metrika' => 'pages/li-metrika.tpl'));
    return $tpl->parse(false, 'li_metrika');
  }

  function addReformal(){
    if(!SYS_IS_PRODUCTION){
      return '';
    }
    $tpl = $this->tpl();
    $tpl->define(array('reformal_block' => 'pages/reformal.tpl'));
    return $tpl->parse(false, 'reformal_block');
  }

  function addVKApi(){
    if(!SYS_IS_PRODUCTION){
      return false;
    }
    $tpl = $this->tpl();
    $tpl->define(array('vk-api' => 'pages/vk-api.tpl'));
    Page()->addScripts(null, null, null, array('url' => '//vk.com/js/api/openapi.js?95'));
    Page()->addJsAfter($tpl->parse(false, 'vk-api'));
    return true;
  }

  function parseFacebookSDK(){
    if(!SYS_IS_PRODUCTION){
      return false;
    }
    $tpl = $this->tpl();
    $tpl->define(array('fb-sdk' => 'pages/fb-sdk.tpl'));

    return $tpl->parse(false, 'fb-sdk');
  }

  function parseGoogleConversionCode($conversion_alias){
    if(!SYS_IS_PRODUCTION){
      return '';
    }
    $tpl = $this->tpl();
    $tpl->clear_tpl('ggl_conversions');
    $tpl->define(array(
      'ggl_conversions' => 'tracking/ggl/conv/'.$conversion_alias.'.tpl'
    ));
    return $tpl->parse(false, 'ggl_conversions');
  }
}
?>