<?php

namespace Verba\Blueprints\create\templates;


use Verba\Blueprints\create\fxs\AttrFx_ForeignId;

class AttrTemplate_ForeignId
{
    public function apply($row): array
    {
        $r = [
            'data_type' => 'integer',
            'form_element' => 'text',
            'foreign_id' => 1,
            '_' => [
                'fxs' => [
                    [AttrFx_ForeignId::class, [1, 2]],
                ],
                'index' => true,
            ]
        ];

        return array_replace_recursive($r, $row);
    }
}