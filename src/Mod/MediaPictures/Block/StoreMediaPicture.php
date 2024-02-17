<?php

namespace Verba\Mod\MediaPictures\Block;

use Verba\Block\Json;
use Verba\QueryMaker;
use function Verba\_oh;
use function Verba\_mod;

class StoreMediaPicture extends Json
{
    function build()
    {
        return ($this->content = _mod('MediaPictures')->handleMediaPicture($this->rq));
    }
}
