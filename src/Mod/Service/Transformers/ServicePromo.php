<?php


namespace Verba\Mod\Service\Transformers;


class ServicePromo
{
    public function transform($item)
    {
        return [
            'title_preview' => $item['title_preview'] ?? null,
            'text_preview' => $item['text_preview'] ?? null,
        ];
    }
}
