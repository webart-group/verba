<?php

namespace Verba\Blueprints\create\templates;


use Verba\Blueprints\create\fxs\AttrFx_OwnerId;

class AttrTemplate_OwnerId
{
    public function apply($row): array
    {
        $r = [
            'attr_code' => 'owner_id',
            'title_ua'=> 'Власника',
            'title_ru'=> 'Владелец',
            'title_en' => 'Owner',
            'data_type' => 'foreign_id',
            'avtofield' => 1,
            'not_editable' => 1,
            'form_element' => 'text',
            'foreign_id' => 1,
            '_' => [
                'fxs' => [
                    [AttrFx_OwnerId::class],
                ],
                'index' => true,
            ]
        ];

        return array_replace_recursive($r, $row);
    }
}
