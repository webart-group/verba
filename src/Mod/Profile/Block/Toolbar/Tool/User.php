<?php
namespace Verba\Mod\Profile\Block\Toolbar\Tool;

use Verba\Mod\Profile\Block\Toolbar\Tool;
use Verba\Mod\User\Model\User;

class User  extends Tool
{
    /**
     * @var User
     */
    protected $U;

    function init(){
        $this->U = \Verba\User();

        $this->userId = $this->U->getId();

        parent::init();
    }
}