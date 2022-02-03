<?php
namespace Mod\Profile\Block\Toolbar\Dropdown\Button;


class User extends \Verba\Block\Html {

    public $templates = array(
        'content' => 'profile/toolbar/dropdown/button/user.tpl',
    );

    function prepare(){
        $U = User();
        $displayName = $U->display_name;
        if(!$displayName){
            $displayName = '??';
        }

        if($U->getUserpic()){
            $userpic = '<i class="pic-32 img-thumbnail" style="background-image:url(\''.$U->getUserpic().'\');"></i>';
        }else{
            $userpic = '';
        }

        $this->tpl->assign(array(
            'USER_DISPLAY_NAME' => htmlspecialchars($displayName),
            'USERPIC' => $userpic,
        ));
    }
}
