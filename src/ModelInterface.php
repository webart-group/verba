<?php
namespace Verba;

interface ModelInterface
{
    /**
     * Возвращает массив атрибутов соответствующих заданным условиям
     * @param mixed $attr_list Входящие атрибуты. Может быть: массивом или строкой - кода(ов) атрибутов; true = все возможные атрибуты, false - только примариАттр
     * @param string|array $allowed_behaviors Массив или строка содержащая группы атрибуты из которых могут присутствовать в результате
     * @param string|array $denied_behaviors Массив cодержащая группы (или одну группу если строка) атрибутовы из которых Не могут присутствовать в результате
     * @param string|array $rights Если передано строкой или массивом, то атрибуты имеющие restrict_key будут просеяны на допуск по переданному этому праву
     *
     * @return false|array
     */
    function getAttrs($attr_list = false, $allowed_behaviors = false, $denied_behaviors = false, $rights = false);
}