<?php
class paysys_requestForm extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'shop/paysys/process/rq_form.tpl',
  );

  /**
   * @var $Order \Mod\Order\Model\Order
   */
  public $Order;

  /**
   * @var $Psmod PaySystemBase
   */
  public $Psmod;

  /**
   * @var $PaySend PaymentTransactionSend
   */
  public $PaySend;

  public $tplvars = array(
    'RQ_FIELDS' => '',
  );

  public $autosend = 1;

  function build(){

    if(!is_object($this->PaySend)){
      throw  new \Verba\Exception\Building('Required PaySend handler');
    }

    if(!$this->PaySend->isValid()){
      throw  new \Verba\Exception\Building($this->PaySend->getDescription());
    }

    if(empty($this->PaySend->url)){
      throw  new \Verba\Exception\Building('Paysys gateway url error');
    }

    if(is_object($this->PaySend)
      && is_object($this->PaySend->request))
    {
      $fields = $this->PaySend->request->getFields();
    }

    if(!is_array($fields)){
      $fields = array();
    }

    $Url = new \Url($this->PaySend->url);

    if($this->PaySend->requestMethod == 'GET'){
      $getParams = $Url->getParams();
      if(is_array($getParams) && count($getParams)){
        $fields = array_replace_recursive($getParams, $fields);
      }
    }


    if(count($fields)){
      $str = '';
      foreach($fields as $fieldKey => $fieldData){
        $str .="\n".'<input type="hidden" name="'.$fieldKey.'" value="'.addslashes($fieldData).'">';
      }
      $this->tpl->assign('RQ_FIELDS',$str);
    }

    $gotoUrl = $Url->get(true);
    $this->tpl->assign(array(
      'RQ_AUTOSEND' => (int)((bool)$this->autosend),
      'RQ_URL' => $gotoUrl,
      'RQ_METHOD' => $this->PaySend->requestMethod,
    ));

    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }

}

?>
