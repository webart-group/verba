<?php

namespace Verba\Blueprints\create\templates;


class AttrTemplate_Priority
{
    public function apply($row): array
    {
        $r = [
            'attr_code' => 'priority',
            'title_ua'=> 'Пріоритет',
            'title_ru'=> 'Приоритет',
            'title_en' => 'Priority',
            'data_type' => 'integer',
            'form_element' => 'text',
            '_' => [
                'migration' => [
                    'options' => ['limit' => 11],
                ],
                'index' => true,
            ]
        ];

        return array_replace_recursive($r, $row);
    }
}