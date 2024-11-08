<?php

namespace Verba\Blueprints\create\templates;


class AttrTemplate_UpdatedAt extends AttrTemplate_CreatedAt
{
    public function apply($row): array
    {
        $r = parent::apply($row);
        $r['not_editable'] = 0;
        $r['title_ua'] = 'Змінено';
        $r['title_ru'] = 'Изменено';
        $r['title_en'] = 'Modified At';
        $r['attr_code'] = 'updated_at';

        return $r;
    }
}