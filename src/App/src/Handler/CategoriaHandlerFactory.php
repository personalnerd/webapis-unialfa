<?php

namespace App\Handler;

use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

class CategoriaHandlerFactory
{
    public function __invoke(ContainerInterface $container): CategoriaHandler
    {
        return new CategoriaHandler(get_class($container), $container->get('DbAdapter'));
    }
}
