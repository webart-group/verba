<?php
namespace Verba\Mod\Debug\Block;

class DebugCallTree
{
    /**
     * Строит дерево items в виде:
     * [
     *   'key' => [
     *     'class' => 'Fully\\Qualified\\ClassName',
     *     'items' => [ ... ],
     *   ],
     * ]
     */
    static function collectResponseItemsTree($node): array
    {
        if (!is_object($node) || !isset($node->items) || !is_array($node->items)) {
            return [];
        }

        $tree = [];

        foreach ($node->items as $key => $child) {
            $className = is_object($child) ? get_class($child) : gettype($child);

            $tree[$key] = [
                'class' => $className,
                'items' => static::collectResponseItemsTree($child),
            ];
        }

        return $tree;
    }

    /**
     * Форматирует дерево в текст:
     * key -> ClassName
     *     childKey -> ChildClass
     */
    static function formatItemsTree(array $tree, int $level = 0): string
    {
        $lines = [];
        $indent = str_repeat('    ', $level); // 4 пробела

        foreach ($tree as $key => $node) {
            $lines[] = $indent . $key . ' -> ' . ($node['class'] ?? 'unknown');

            if (!empty($node['items'])) {
                $lines[] = static::formatItemsTree($node['items'], $level + 1);
            }
        }

        return implode("\n", $lines);
    }
}
