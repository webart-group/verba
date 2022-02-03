<?php
namespace Mod\Profile\Block\Auth;

use Verba\Block\Html;

class Ui extends Html
{
    function init()
    {
        $this->addItems(\Verba\User()->getAuthorized()
            ? new User($this)
            : new Guest($this)
        );
    }
}