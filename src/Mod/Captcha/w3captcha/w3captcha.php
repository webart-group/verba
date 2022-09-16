<?php

/*

w3captcha - php-скрипт для генерации изображений CAPTCHA
версия: 1.1 от 08.02.2008
разработчики: http://w3box.ru
тип лицензии: freeware
w3box.ru © 2008

*/

class w3captcha extends Configurable {

  public $count=5;  /* количество символов */
  public $width=100; /* ширина картинки */
  public $height=48; /* высота картинки */
  public $font_size_min=42; /* минимальная высота символа */
  public $font_size_max=46; /* максимальная высота символа */
  public $pathToFont = '';
  public $font_file = 'Comic_Sans_MS.ttf'; /* путь к файлу относительно w3captcha.php */
  public $char_angle_min=-15; /* максимальный наклон символа влево */
  public $char_angle_max=15;  /* максимальный наклон символа вправо */
  public $char_angle_shadow=0;  /* размер тени */
  public $char_align=30;  /* выравнивание символа по-вертикали */
  public $start=2;  /* позиция первого символа по-горизонтали */
  public $interval=18;  /* интервал между началами символов */
  public $chars="0123456789"; /* набор символов */
  public $noise=10; /* уровень шума */
  public $session_key = 'captcha';

  public $str = '';
  /**
   * @var bool|resource
   */
  public $image = false;

  function __construct(){
    /**
     * @var $mCaptcha Captcha
     */

    $mCaptcha = \Verba\_mod('captcha');
    $this->setPathToFont($mCaptcha->getPath().'/w3captcha');
  }

  function generate(){

    $this->image = imagecreatetruecolor($this->width, $this->height);

    $background_color = imagecolorallocate($this->image, 255, 255, 255); /* rbg-цвет фона */
    $font_color = imagecolorallocate($this->image, 32, 64, 96); /* rbg-цвет тени */

    imagefill($this->image, 0, 0, $background_color);

    $pos = $this->start;
    $font_file = !empty($this->pathToFont) ? $this->pathToFont.'/'.$this->font_file : $this->font_file;

    $num_chars = strlen($this->chars);

    for ($i=0; $i<$this->count; $i++){

      $char = $this->chars[rand(0, $num_chars-1)];
      $font_size = rand($this->font_size_min, $this->font_size_max);
      $char_angle = rand($this->char_angle_min, $this->char_angle_max);
      imagettftext($this->image, $font_size, $char_angle, $pos, $this->char_align, $font_color, $font_file, $char);
      imagettftext($this->image, $font_size, $char_angle+$this->char_angle_shadow*(rand(0, 1)*2-1), $pos, $this->char_align, $background_color, $font_file, $char);
      $pos += $this->interval;
      $this->str .= $char;
    }

    if ($this->noise)
    {
      for ($i=0; $i<$this->width; $i++)
      {
        for ($j=0; $j<$this->height; $j++)
        {
          $rgb=imagecolorat($this->image, $i, $j);
          $r=($rgb>>16) & 0xFF;
          $g=($rgb>>8) & 0xFF;
          $b=$rgb & 0xFF;
          $k=rand(-$this->noise, $this->noise);
          $rn=$r+255*$k/100;
          $gn=$g+255*$k/100;
          $bn=$b+255*$k/100;
          if ($rn<0) $rn=0;
          if ($gn<0) $gn=0;
          if ($bn<0) $bn=0;
          if ($rn>255) $rn=255;
          if ($gn>255) $gn=255;
          if ($bn>255) $bn=255;
          $color = imagecolorallocate($this->image, $rn, $gn, $bn);
          imagesetpixel($this->image, $i, $j , $color);
        }
      }
    }

  }

  function setSessionKey($val){
    if(is_string($val) && !empty($val)){
      $this->session_key = $val;
    }
  }

  function setPathToFont($val){
    if(is_string($val) && !empty($val)){
      $this->pathToFont = $val;
    }
  }

  function output(){
    header('Content-type: image/gif');
    imagegif($this->image);
    imagedestroy($this->image);
  }

  function getCaptchaStr(){
    return $this->str;
  }

  function saveToSession(){
    $_SESSION[$this->session_key] = $this->getCaptchaStr();
  }
}

?>