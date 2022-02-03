<?php

namespace Mod\Offer\Act\MakeList\Handler\Field;

use \Act\MakeList\Handler\Field;

class StoreInfo extends Field
{
    public $templates = array (
        'field-store-content' => '/game/list/fields/store.tpl',
        'field-store-content-pic' => '/game/list/fields/store-picture.tpl'
    );

    function run()
    {
        $mImage = \Verba\_mod('image');
        $tpl = $this->list->tpl();

        if (!$tpl->isDefined('field-store-content')) {
            $tpl->define($this->templates);
        }

        $tpl->assign(array(
            'STORE_TITLE' => $this->list->row['storeId__value'],
            'STORE_ONLINE_STATUS_SIGN' => '',
        ));
        $_store = \Verba\_oh('store');
        if (!empty($this->list->row['store_picture'])) {
            $imgCfg = $mImage->getImageConfig($_store->p('picture_config'));
            $tpl->assign(array(
                'STORE_PIC_URL' => $imgCfg->getFullUrl(basename($this->list->row['store_picture']), 'ico32'),
                'STORE_PIC_SIGN' => '',
            ));
            $tpl->parse('STORE_PIC', 'field-store-content-pic');
        } else {
            $tpl->assign(array(
                'STORE_PIC' => '',
                'STORE_PIC_SIGN' => ' no-ico',
            ));
        }
        /**
         * @var  $mUser \Verba\User\User
         */
        $mUser = \Verba\_mod('user');
        $ostatus = $mUser->getOnlineStatusByDatetime($this->list->row['store_last_activity']);
        $tpl->assign(array(
            'STORE_ONLINE_STATUS_SIGN' => $ostatus,
        ));
        $this->list->rowClass[] = $ostatus;

        return $tpl->parse(false, 'field-store-content');
    }
}
