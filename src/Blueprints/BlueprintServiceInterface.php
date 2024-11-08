<?php

namespace Verba\Blueprints;

interface BlueprintServiceInterface
{
    public function run(): BlueprintServiceResultInterface;
}

