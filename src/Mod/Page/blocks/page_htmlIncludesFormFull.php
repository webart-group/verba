<?php
class page_htmlIncludesFormFull extends \Verba\Block\Html{


  function init(){

    $this->addCss(array(
      array('jquery-ui','/js/jquery/ui/theme/pepper-grinder'),
      array('jquery-ui-timepicker-addon', '/js/jquery/timepicker-addon'),
      array('colorpicker', '/js/jquery/plugins/colorpicker/css'),
      array('select2.min', '/js/jquery/select2/css'),
      array('form'),
      array('picupload', 'form/fe'),
    ), 900);

    $this->addScripts(array(
      array('jquery-ui.min', 'jquery/ui'),
      array('jquery.ui.datepicker-ru', 'jquery/ui'),
      array('jquery-ui-timepicker-addon', 'jquery/timepicker-addon'),
      array('form formValidator','form'),
      array('select2.min', 'jquery/select2/js'),
      array('ckeditor', '/externals/ckeditor'),
      array('ckfinder', '/externals/ckfinder'),
      array('multi-parent-selector location-selector', 'form/e'),
      array('picupload', 'form/fe'),
      array('ratingstars', 'form/fe'),
      array('old', 'form/validators'),
      array('jquery.ui.widget', 'jquery/file-upload/9.5.7/js/vendor'),
      array('jquery.iframe-transport jquery.fileupload fileupload-ui-kmv', 'jquery/file-upload/9.5.7/js'),
    ), 900);
  }

}
?>