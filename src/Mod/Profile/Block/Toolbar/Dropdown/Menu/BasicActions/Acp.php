<?php
namespace Mod\Profile\Block\Toolbar\Dropdown\Menu\BasicActions;

class Acp extends \Verba\Block\Html{

    public $templates = array(
        'content' => 'profile/toolbar/dropdown/menu/basic/acp.tpl',
    );

    function build(){

        $this->content = '';

        $U = User();
        $mAcp = \Verba\_mod('acp');
        $r = $mAcp->gC('access_rights');

        if(!is_array($r)){
            return $this->content;
        }

        foreach($r as $k => $data){
            if(!$U->chr($k, $data)){
                return $this->content;
            }
        }
        $this->tpl->assign(array( 'ACP_URL' => $mAcp->gC('url')));

        $this->content = $this->tpl->parse(false, 'content');
        return $this->content;
    }
}
