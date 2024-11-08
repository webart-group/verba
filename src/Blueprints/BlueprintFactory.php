<?php

namespace Verba\Blueprints;

class BlueprintFactory
{
    public static function create(AbstractBlueprint $blueprint): BlueprintServiceInterface
    {
        return new CreateService($blueprint);
    }

    public static function update(AbstractBlueprint $blueprint): BlueprintServiceInterface
    {
        return new UpdateService($blueprint);
    }
}
