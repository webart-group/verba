<?php
namespace Mod\Catalog;

class MapTransformer
{
    function transform(array $catalog):array
    {
        return [
            'id' => $catalog['id'],
            'url' => $catalog['url'],
            'title' => $catalog['title'],
            'description' => $catalog['description'],
            'code' => $catalog['code'],
            'items' => $catalog['items'],
        ];
    }
}