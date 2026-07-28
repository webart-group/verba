<?php
namespace Verba\Mod\Cart\Actions;

class SwitchUserByEmail extends \Verba\Block\Html
{

    function build()
    {
        $this->content = \Verba\_mod('cart')->switchCustomerByEmail($this->request->asArray());
        return $this->content;
    }
}