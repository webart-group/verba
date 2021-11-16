<?php
namespace Verba\Act\AddEdit\Handler\Around;

use Act\AddEdit\Handler\Around;

class EditorImage extends Around
{
  function run()
  {
    if(!isset($this->value)){
      return null;
    }
    // поиск тегов <img>
    preg_match_all( '#<img(.+?)>#is', $this->value, $matches );
    $result = array();
    // если есть теги <img>, то в массив $result записываются свойства картинок (src, width, ...)
    if(count($matches) < 1){
      return $this->value;
    }
    $i = 0;
    foreach($matches[0] as $img){
      preg_match_all( '#src="(.+?)"#is', $img, $src_matches );
      if(count($src_matches) > 0){
        $result[$i]['src'] = $src_matches[1][0];
        $result[$i]['img'] = $img;
      }
      preg_match_all( '#width="(.+?)"#is', $img, $w_matches );
      if(count($w_matches) > 0){
        if($w_matches[1][0])$result[$i]['width'] = $w_matches[1][0];
      }
      preg_match_all( '#height="(.+?)"#is', $img, $h_matches );
      if(count($h_matches) > 0){
        if($h_matches[1][0])$result[$i]['height'] = $h_matches[1][0];
      }
      $result[$i]['prefix'] = mb_substr($src_matches[1][0], 0, 7) == '//' ? '' : SYS_ROOT;
      list($result[$i]['file_width'], $result[$i]['file_height'], $type, $attr) = getimagesize($result[$i]['prefix'].$result[$i]['src']);
      if(!$result[$i]['width']) $result[$i]['width'] = $result[$i]['file_width'];
      if(!$result[$i]['height']) $result[$i]['height'] = $result[$i]['file_height'];
      ++$i;
    }

    $_mod = \Verba\_mod($this->params['mod']);
    $cfg = $_mod->gC($this->params['cfg_key']);
    if(!$cfg) return $this->value;

    $mImage = \Verba\_mod('image');
    $FileSchemeHandler = new  \Verba\FileSystem\Local();

    foreach($result as $img => $idata){
      // если локальный файл
      if(!$mImage->isRemotePicURL($idata['src'])){
        $path = $_mod->getImagePath().mb_substr(dirname($idata['src']), mb_strlen($_mod->getImageURL()));
        $file_path = $path.'/'.basename($idata['src']);

        //генерация иконок
        if(is_array($cfg['icons']) && count($cfg['icons']['items']) > 0){
          foreach($cfg['icons']['items'] as $num => $item){
            if(($idata['width'] > $item['width'] || $idata['height'] > $item['height']) || $item['always'] === true){
              $icon_path = isset($item['path']) ? $item['path'] : $path.$cfg['icons']['path'];
              $icon_path = $_mod->getImagePath().$icon_path;
              if(!is_dir($icon_path)) $FileSchemeHandler->make_dir($icon_path);
              $prefix = isset($item['prefix']) ? $item['prefix'].'_' : $item['width'].'x'.$item['height'].'_';
              $icon = $mImage->repackImage($file_path, $icon_path.'/'.$prefix.basename($idata['src']), $item['width'], $item['height'] );
            }
          }
        }

        // если ширина или высота превышают размер preview, то создаётся иконка и заменяется src
        if($idata['file_width'] > $cfg['preview']['width'] || $idata['file_height'] > $cfg['preview']['height'] || $idata['width'] > $cfg['preview']['width'] || $idata['height'] > $cfg['preview']['height']){
          $new_sizes = $mImage->calcImageWH($idata['width'], $idata['height'], $cfg['preview']['width'], $cfg['preview']['height']);
          $result[$img]['new_width'] = $new_sizes['width'];
          $result[$img]['new_height'] = $new_sizes['height'];
          $dir = $_mod->getImagePath().mb_substr(dirname($idata['src']), mb_strlen($_mod->getImageURL()));
          $l = strlen($cfg['icons']['path']);
          if(mb_substr($dir, -$l, $l) != $cfg['icons']['path']) $dir.= $cfg['icons']['path'];
          if(!is_dir($dir)) $FileSchemeHandler->make_dir($dir);
          $result[$img]['new_src'] = $mImage->repackImage(SYS_ROOT.$idata['src'], $dir.'/'.basename($idata['src']), $cfg['preview']['width'], $cfg['preview']['height'] );
          $result[$img]['new_src'] = str_replace($_mod->getImagePath(), $_mod->getImageURL(), $result[$img]['new_src']);
          $result[$img]['new_img'] = preg_replace('#src="(.+?)"#is', 'src="'.$result[$img]['new_src'].'"', $result[$img]['img']);
          $result[$img]['new_img'] = preg_replace('#width="(.+?)"#is', '', $result[$img]['new_img']);
          $result[$img]['new_img'] = preg_replace('#height="(.+?)"#is', '', $result[$img]['new_img']);

          // поиск тегов <img>, заключённых в теги <a></a>
          preg_match_all( '#<a(.+?)>'.addcslashes($result[$img]['img'], '()').'</a>#is', $this->value, $matches);
          // если картинка является ссылкой, заменяем картинку на иконку
          if(count($matches[0]) > 0 ){
            $this->value = preg_replace(
              '#'.addcslashes($result[$img]['img'], '()').'#',
              $result[$img]['new_img'],
              $this->value
            );
          }else{ // если картинка не является ссылкой, заменяем картинку на иконку-ссылку на оригинал
            $this->value = preg_replace(
              '#'.addcslashes($result[$img]['img'], '()').'#',
              '<a href="'.$result[$img]['src'].'" title="" border="0">'.$result[$img]['new_img'].'</a>',
              $this->value
            );
          }
        }
      }else{ // если удалённый файл
        $result[$img]['new_src'] = $idata['src'];
        $result[$img]['new_img'] = $idata['img'];
        // если ширина или высота превышают размер preview, то проставляются ширина и высота без замены src
        if($idata['file_width'] > $cfg['preview']['width'] || $idata['file_height'] > $cfg['preview']['height'] || $idata['width'] > $cfg['preview']['width'] || $idata['height'] > $cfg['preview']['height']){
          $new_sizes = $mImage->calcImageWH($idata['width'], $idata['height'], $cfg['preview']['width'], $cfg['preview']['height']);
          $result[$img]['new_img'] = preg_replace('#width="(.+?)"#is', '', $result[$img]['new_img']);
          $result[$img]['new_img'] = preg_replace('#height="(.+?)"#is', '', $result[$img]['new_img']);
          if($new_sizes['width'] > 0) $result[$img]['new_img'] = preg_replace('#/?>#is', 'width="'.$new_sizes['width'].'" >', $result[$img]['new_img']);
          if($new_sizes['height'] > 0) $result[$img]['new_img'] = preg_replace('#/?>#is', 'height="'.$new_sizes['height'].'" >', $result[$img]['new_img']);
          $this->value = preg_replace(
            '#'.addcslashes($result[$img]['img'], '()').'#',
            $result[$img]['new_img'],
            $this->value
          );
        }
      }
    }

    return $this->value;
  }
}
