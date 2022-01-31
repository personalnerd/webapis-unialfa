<?php

declare(strict_types=1);

namespace App\Handler;

use DI\Container as PHPDIContainer;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class AtualizaAcessoHandler implements RequestHandlerInterface
{
    private $containerName;
    private $router;
    private $dbAdapter;

    public function __construct(
        string $containerName,
        Router\RouterInterface $router,
        Adapter $dbAdapter
    ) {
        $this->containerName = $containerName;
        $this->router        = $router;
        $this->dbAdapter     = $dbAdapter;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = [];

        $random = random_bytes(16);
        $token = bin2hex($random);

        $sql = new Sql($this->dbAdapter);
        $update= $sql->update('acesso');
        $update->set(['token' => $token]);
        $update->where(['id' => $request->getAttribute('id')]);

        $stmt = $sql->prepareStatementForSqlObject($update);
        $recordSet = $stmt->execute();

        return new JsonResponse($data);
    }
}
