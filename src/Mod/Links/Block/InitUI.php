<?php
namespace Verba\Mod\Links\Block;

class InitUI extends \Verba\Block\Json
{

    public $lkcfg = '';

    function build()
    {
        $cfg = array(
            'cfgName' => $this->lkcfg,
            'post' => array(
                'primSlID' => $_REQUEST['primSlID']
            )
        );

        $this->content = array(
            'title' => \Verba\Lang::get('importkorm acp convert_init_ui_title'),
            'body' => \Verba\_mod('links')->initLinkingFace($cfg),
        );
        return $this->content;
    }
}
