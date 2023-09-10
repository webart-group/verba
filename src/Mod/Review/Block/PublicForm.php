<?php

namespace Verba\Mod\Review\Block;
class PublicForm extends \Verba\Block\Html
{

    public $templates = array(
        'content' => 'review/form/wrap.tpl',
    );

    function build()
    {

        $this->addScripts(array('form', 'form'));
        $this->addCSS(array('form'));

        $_rw = \Verba\_oh('review');

        $form = $_rw->initForm(array('action' => 'new'));
        $form->applyConfig('public public-review');

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