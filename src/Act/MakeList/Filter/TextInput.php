<?php
namespace Verba\Act\MakeList\Filter;

class TextInput extends \Verba\Act\MakeList\Filter{

  function build(){
    $this->tpl->clear_tpl(array_keys($this->templates));
    $this->tpl->define($this->templates);

    if(isset($this->value)){
      $this->E->setValue($this->value);
    }

    $this->tpl->assign(array(
      'FILTER_ELEMENT' => $this->E->build()
    ));

    return $this->tpl->parse(false, 'content');
  }

}
?>