<?php

namespace PanicDevs\Modulite\Actions;

use Illuminate\Contracts\Foundation\Application;
use PanicDevs\Modulite\Contracts\ModuleResolverInterface;
use PanicDevs\Modulite\Services\ModuleResolvers\NwidartModuleResolver;
use PanicDevs\Modulite\Services\ModuleResolvers\PanicDevsModuleResolver;

final class CreateModuleResolver
{
    public function handle(Application $app): ModuleResolverInterface
    {
        $approach = $app['config']->get('modulite.modules.approach');

        $defaultApproach = static::getDefaultApproach();

        $class = !($defaultApproach[$approach] ?? '') ?
            (static::approachIsResolvable($approach) ?
                $approach :
                throw new \Exception(
                    "Configured module approach [{$approach}] is missing or not resolvable."
                )) :
            $defaultApproach[$approach];

        return $app->make($class);
    }

    private static function approachIsResolvable(string $approach): bool
    {
        return
            class_exists($approach) &&
            is_subclass_of($approach, ModuleResolverInterface::class);
    }

    private static function getDefaultApproach(): array
    {
        return [
            'panicdevs' => PanicDevsModuleResolver::class,
            'nwidart'   => nwidartModuleResolver::class,
        ];
    }
}
