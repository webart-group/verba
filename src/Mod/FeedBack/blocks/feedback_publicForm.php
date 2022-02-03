<?php
class feedback_publicForm extends \Verba\Mod\Routine\Block\Form{

  public $valid_otype = 'feedback';

  function init(){
    $this->rq->setOt('feedback');
  }

  function prepare()
  {
    $this->rq->action = 'new';
    $this->cfg = 'public public/feedback';

    $U = User();
    if($U->getAuthorized()){
      $customizeCfg = array(
        'fields' => array(
          'name' => array(
            'value' => $U->getValue('display_name'),
            'readonly' => true,
          ),
          'email' => array(
            'value' => $U->email,
            'readonly' => true,
          ),
        )
      );
      $this->dcfg = array_replace_recursive($this->dcfg, $customizeCfg);
    }
  }
}
?>