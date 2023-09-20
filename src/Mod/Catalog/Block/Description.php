<?php

namespace Verba\Mod\Catalog\Block;

class Description extends \Verba\Block\Html
{

    public $templates = array(
        'content' => 'catalog/show/description.tpl'
    );

    public $tplvars = array(
        'BLOCK_CONTENT' => '',
        'CATALOG_EMPTY_SIGN' => ' hidden',
    );

    function build()
    {
        $this->content = '';

        $service = $this->_parent->gsr->service;

        if (!$service) {
            return $this->content;
        }

        $description = $service->getValue('description');
        if (isset($description) && !empty($description)) {

            $this->tpl->assign(array(
                'BLOCK_CONTENT' => $description,
                'CATALOG_EMPTY_SIGN' => '',
            ));

        } else {

            return $this->content;

        }

        $this->content = $this->tpl->parse(false, 'content');

        return $this->content;
    }
}
