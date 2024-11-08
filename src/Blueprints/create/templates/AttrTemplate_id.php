<?php

namespace Verba\Blueprints\create\templates;


use Verba\Blueprints\create\fxs\AttrFx_Autoincrement;

class AttrTemplate_id
{
    public function apply($row): array
    {
        $r = [
            'attr_code' => 'id',
            'title_ru'=> 'ID',
            'title_en' => 'ID',
            'title_ua'=> 'ID',
            'data_type' => 'integer',
            'form_element' => 'text',
            'priority' => 10,
            'not_editable' => 1,
            'avtofield' => 1,
            '_' => [
                'fxs' => [
                    [AttrFx_Autoincrement::class, ['third', 'fourth']],
                ],
                'migration' => false
            ]
        ];

        return array_replace_recursive($r, $row);
    }
}