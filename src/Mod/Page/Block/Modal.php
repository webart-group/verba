<?php
/**
 * @copyright
 */
namespace Mod\Page\Block;

class Modal extends \Verba\Block\Html{

    public $templates = array(
        'content' => 'page/elements/modal.tpl'
    );

    public $role = 'default-modal';
}
