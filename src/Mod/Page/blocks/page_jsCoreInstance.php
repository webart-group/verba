<?php
class page_jsCoreInstance extends \Verba\Block\Html{

  function prepare()
  {
    // Добавление конфигурации системы (версия)
    $this->addJsBefore("
$.extend(true, window.sysCfg, ".json_encode(array(
        'version' => SYS_VERSION
      )).");");
  }

}