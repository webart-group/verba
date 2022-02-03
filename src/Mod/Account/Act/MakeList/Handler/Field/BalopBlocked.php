<?php

namespace Mod\Account\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;

class BalopBlocked extends Field{

  function run(){

    if(!$this->list->row['block']
      && empty($this->list->row['holdTill'])
    ){
      return '';
    }

    if(empty($this->list->row['holdTill'])){

      $str = \Verba\Lang::get('balop descriptions blocked');

    }else{

      $str = \Verba\Lang::get('balop descriptions holdTill', array(
        'holdTill' => \Mod\Shop::formatDate(strtotime($this->list->row['holdTill'])),
      ));

    }

    return $str;
  }

}
