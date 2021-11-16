<?php
namespace Mod\User\Block\Login;

class Json extends \Verba\Block\Json
{
    /**
     * @var Form
     */
    protected $form;

    function init()
    {
        $this->form = new Form($this->rq, [
            'placement' => 'modal'
        ]);

        $this->addItems(['form' => $this->form]);
    }

    function build()
    {
        $this->content = array(
            'title' => '',
            'body' => $this->form->getContent(),
        );
        return $this->content;
    }
}

