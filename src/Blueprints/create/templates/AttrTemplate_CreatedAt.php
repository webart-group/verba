<?php

namespace Verba\Blueprints\create\templates;


use Verba\Blueprints\create\fxs\AttrFx_CreatedAt;

class AttrTemplate_CreatedAt
{
    public function apply($row): array
    {
        $r = [
            'attr_code' => 'created_at',
            'title_ua'=> 'Створено',
            'title_ru'=> 'Создано',
            'title_en' => 'Created At',
            'data_type' => 'datetime',
            'avtofield' => 1,
            'not_editable' => 1,
            'form_element' => 'datetimeselector',
            '_' => [
                'fxs' => [
                    [AttrFx_CreatedAt::class],
                ],
            ]
        ];

        return array_replace_recursive($r, $row);
    }
}