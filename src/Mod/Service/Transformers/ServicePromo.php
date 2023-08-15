<?php


namespace Verba\Mod\Service\Transformers;


class ServicePromo
{
    public function transform($item)
    {
        return [
            'title' => $item['title'] ?? null,
            'text_preview' => $item['text_preview'] ?? null,
        ];
    }
}
