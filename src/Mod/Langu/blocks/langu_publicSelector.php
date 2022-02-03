<?php

class langu_publicSelector extends \Verba\Block\Html
{
    public $templates = array(
        'content' => '/lang/wrap.tpl',
        'item' => '/lang/item.tpl'
    );

    function build()
    {
        foreach (\Verba\Lang::getUsedLC() as $lc)
        {
            $this->tpl->assign(array(
                'LC_URL' => \Verba\var2url($_SERVER['REQUEST_URI'], 'lc=' . $lc),
                'LC_CODE' => $lc,
                'LC_NAME' => \Verba\Lang::getLCShortName($lc),
                'LC_NAME_TITLE' => \Verba\Lang::getLCName($lc),
                'LC_SELECTED_SIGN' => \Verba\Lang::$lang == $lc ? ' selected' : '',
            ));
            $this->tpl->parse('LC_ITEMS', 'item', true);
        }

        $this->content = $this->tpl->parse(false, 'content');

        return $this->content;
    }
}
