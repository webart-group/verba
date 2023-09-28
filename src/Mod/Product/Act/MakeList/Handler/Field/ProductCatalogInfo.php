<?php
namespace Verba\Mod\Product\Act\MakeList\Handler\Field;


use Verba\Act\MakeList\Handler\HandlerInterface;
use Verba\Act\MakeList\Handler\Field;

class ProductCatalogInfo extends Field implements HandlerInterface
{
    function run()
    {
        return [
            'catalog' => [
                'id' => $this->list->row['cat_id'],
                'title' => $this->list->row['cat_title'],
                'code' => $this->list->row['cat_code'],
            ]
        ];
    }
}
