<?php
namespace Verba\Mod\Order\Act\MakeList\Handler\Field;

use Act\MakeList\Handler\Field;
use Verba\Mod\Image;

class Store extends Field
{
    public $sharedTpl = true;
    public $templates = [
        'content' => 'order/acp/list/handler/store/content.tpl'
    ];

    function run()
    {
        $this->tpl->assign([
            'STORE_PICTURE' => Image::pictureToImgTag($this->list->row['store_picture'], 'picture', \Verba\_oh('store'), true),
            'STORE_NAME' => htmlspecialchars($this->list->row['storeId__value']),
            'STORE_ID' => $this->list->row['storeId']
        ]);

        return $this->tpl->parse(false, 'content');
    }
}
