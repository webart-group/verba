<?php

namespace Mod\Account\Act\MakeList\Handler\Field\Acp;

use Act\MakeList\Handler\Field;

class AccountGravityAndEaseButton extends Field{

  function run(){

    $e = new \page_eInteractive(array(), array(
      'eid' => $this->list->getListId().'_'.$this->list->row['ot_id'].'_'.$this->list->row['id'].'_btn_acpAccountEaseOrGravity',
      'rq_data' => array(
        'iid' => $this->list->row['id'],
      ),
      'component'=> 'accountBalancer',
      'templates' => array(
        'ui' => '/acp/account/eInteractive/easeOrGravity.tpl'
      )
    ));

    $b2 = $e->run();

    $this->list->mergeHtmlIncludes($e);

    return $b2;
  }
}
