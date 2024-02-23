<?php

namespace Verba\Mod\Index\Block;

use Verba\Mod\Service\Block\ServicesList;
use Verba\Mod\Service\Transformers\ServicePromo;

class ServicesPromo extends ServicesList
{
    public string $transformerClass = ServicePromo::class;
}
