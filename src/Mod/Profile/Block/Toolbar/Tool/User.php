<?php
namespace Mod\Profile\Block\Toolbar\Tool;

use Mod\Profile\Block\Toolbar\Tool;
use Verba\User\Model\User;

class User  extends Tool
{
    /**
     * @var U
     */
    protected $U;

    function init(){
        $this->U = User();

        $this->userId = $this->U->getId();

        parent::init();
    }
}