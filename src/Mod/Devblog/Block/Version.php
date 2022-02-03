<?php

namespace Mod\Devblog\Block;

use Mod\Devblog;

class Version extends \Verba\Block\Html
{
    public $templates = array(
        'content' => 'devblog/version/content.tpl'
    );

    public $css = [
        ['devblog']
    ];

    const COOKIE_NAME = 'app_version';

    public function prepare()
    {

        $v = (string)\Verba\Hive::conf('version');

        if(!$_COOKIE[self::COOKIE_NAME] || $_COOKIE[self::COOKIE_NAME] !== $v)
        {
            $sign = ' new-update-exists';
            setcookie(self::COOKIE_NAME, $v, (time() + 7776000), '/', '.' . SYS_PRIMARY_HOST);
        }else{
            $sign = '';
        }

        $this->tpl->assign([
            'VERSION' => $v,
            'NEW_UPDATE_EXISTS_SIGN' => $sign,
        ]);
    }
}
