<?php

namespace Verba\Mod\Catalog\helpers;

class PromoBlockPlaceHolder extends \Verba\Block\Html
{
    public $templates = array(
        'content' => 'catalog/pbphl/wrap.tpl'
    );

    function build()
    {
        $this->content = '';
        $item = $this->getItem(0);
        if (!$item || !$item->content) {
            return $this->content;
        }

        $block = $this->getBlockByRole('layout');
        $block->tpl->assign('PROMO_BLOCK_PLACEHOLDER', $item->content);

        $this->content = $this->tpl->parse(false, 'content');
        return $this->content;
    }

}
