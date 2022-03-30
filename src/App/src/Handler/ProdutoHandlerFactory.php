<?php

namespace App\Handler;

use Psr\Container\ContainerInterface;

class ProdutoHandlerFactory
{
    public function __invoke(ContainerInterface $container): ProdutoHandler
    {
        return new ProdutoHandler($container->get('DbAdapter'));
    }
}
