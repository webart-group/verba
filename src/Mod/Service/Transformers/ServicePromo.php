<?php


namespace Verba\Mod\Service\Transformers;


use function Verba\_mod;

class ServicePromo
{
    public function transform($item)
    {
        $mImage = _mod('image');

        if (!empty($item['picture'])) {
            $imgCfg = $mImage->getImageConfig('content');
            $picture = $imgCfg->getFullUrl(basename($item['picture']));
        }else{
            $picture = null;
        }

        return [
            'title_preview' => $item['title_preview'] ?? null,
            'text_preview' => $item['text_preview'] ?? null,
            'pucture' => $picture,
        ];
    }
}
