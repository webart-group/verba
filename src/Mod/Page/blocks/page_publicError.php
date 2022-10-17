<?php

class page_publicError extends \Verba\Block\Html
{

    public $templates = array(
        'content' => 'page/error/page.tpl',
    );
    /**
     * @var Exception
     */
    public $e;

    function route()
    {
        $response = new \Verba\Response\Html($this);;

        $response->addItems(array(
            'PAGE_BODY' => $this,
        ));
        return $response;
    }

    function build()
    {
        if ($this->e) {
            if (constant('SYS_IS_PRODUCTION') === false || \Verba\User()->in_group(USR_ADMIN_GROUP_ID)) {
                $this->content = "<pre>" . $this->e->getMessage() . "\n\n" . $this->e->getTraceAsString() . "</pre>";
            }
        }

        $this->tpl->assign(array(
            'PAGE_CONTENT' => isset($this->content) ? $this->content : \Verba\Lang::get('error page default_msg'),
        ));

        $this->setNamedMeta('title', \Verba\Lang::get('error page title'));

        $this->content = $this->tpl->parse(false, 'content');

        return $this->content;
    }
}
