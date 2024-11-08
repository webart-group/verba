<?php

namespace Verba\Blueprints\create\templates;


use Verba\Blueprints\create\fxs\AttrFx_AgId;

class AttrTemplate_AgId
{
    public function apply($row): array
    {
        $r = [
            'attr_code' => 'ag_id',
            'title_ua'=> 'Група доступа',
            'title_ru'=> 'Группа доступа',
            'title_en' => 'Access Group',
            'data_type' => 'integer',
            'avtofield' => 1,
            'not_editable' => 1,
            'form_element' => 'text',
            '_' => [
                'fxs' => [
                    [AttrFx_AgId::class],
                ],
                'migration' => [
                    'options' => ['limit' => 11, 'signed' => false],
                ],
                'index' => true,
            ]
        ];

        return array_replace_recursive($r, $row);
    }
}