<?php
class comment_publicForm extends \Verba\Block\Html{

  public $templates = array(
    'content' => 'comment/form/wrap.tpl',
  );

  function prepare(){
    $this->getBlockByRole('default-default-modal')->unmute();
  }

  function build(){

    $this->addScripts(array('form', 'form'));
    $this->addCSS(array('form'));

    $_comm = \Verba\_oh('comment');

    $form = $_comm->initForm(array('action' => 'new'));
    $form->addParents($this->request->ot_id, $this->request->iid);
    $form->applyConfig('public public-comment');

    $this->tpl->assign(array(
      'THIS_FORM_ID' => $form->getID()
    ));
    $this->tpl->assign(array(
      'THIS_FORM' => $form->makeForm(),
    ));
    $this->content = $this->tpl->parse(false, 'content');
    return $this->content;
  }
}
?>