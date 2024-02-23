<?php

namespace Verba\Mod\Acp\Router\Contracts;

interface CatalogActionConfigInterface
{
    public function isCatalogActionConfigApplicable();

    public function getCatalogActionConfig();
}