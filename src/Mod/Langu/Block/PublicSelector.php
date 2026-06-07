<?php

namespace Verba\Mod\Langu\Block;
class PublicSelector extends \Verba\Block\Html
{
    public $templates = array(
        'content' => '/page/local/lang-selector/wrap.tpl',
        'item' => '/page/local/lang-selector/item.tpl'
    );

    function build()
    {
        foreach (\Verba\Lang::getUsedLC() as $lc) {
            $lcSelected = false;
            if(\Verba\Lang::$locale == $lc){
                $lcSelected = true;
                $this->tpl->assign([
                    'CURRENT_LC_NAME' => \Verba\Lang::getLCShortName($lc),
                    'CURRENT_LC_NAME_TITLE' => \Verba\Lang::getLCName($lc),
                ]);
            }

            $this->tpl->assign([
                'LC_URL' => \Verba\var2url($_SERVER['REQUEST_URI'], 'lc=' . $lc),
                'LC_CODE' => $lc,
                'LC_NAME' => \Verba\Lang::getLCShortName($lc),
                'LC_NAME_TITLE' => \Verba\Lang::getLCName($lc),
                'LC_SELECTED_SIGN' => $lcSelected ? ' active' : '',
            ]);
            $this->tpl->parse('LC_ITEMS', 'item', true);
        }

        $this->content = $this->tpl->parse(false, 'content');

        return $this->content;
    }
}
