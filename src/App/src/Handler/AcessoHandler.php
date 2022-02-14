<?php

declare(strict_types=1);

namespace App\Handler;

use DI\Container as PHPDIContainer;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class AcessoHandler implements RequestHandlerInterface
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

        $method = $request->getMethod();

        $sql = new Sql($this->dbAdapter);

        switch ($method) {
            case 'GET':
                $data = [];

                $select = $sql->select('acesso');
                $select->columns(['token']);

                if($request->getHeader('x-id') != null) {
                    $select->where(['id' => $request->getHeader('x-id')]);
                }

                $stmt = $sql->prepareStatementForSqlObject($select);
                $recordSet = $stmt->execute();

                while(($record = $recordSet->current()) !== false) {
                    $data[] = $record;

                    $recordSet->next();
                }

                return new JsonResponse($data);
            break;

            case 'POST':
                $body = json_decode($request->getBody()->getContents());

                $insert = $sql->insert('acesso');
                $insert->values(['token' => $body->token], $insert::VALUES_MERGE);
                $stmt = $sql->prepareStatementForSqlObject($insert);
                $stmt->execute();

                return new EmptyResponse(201);
            break;

            case 'PATCH':
                $body = json_decode($request->getBody()->getContents());

                $update= $sql->update('acesso');
                $update->set(['token' => $body->token]);
                $update->where(['id' => $request->getHeader('x-id')]);

                $stmt = $sql->prepareStatementForSqlObject($update);
                $recordSet = $stmt->execute();

                return new EmptyResponse(204);
            break;

            case 'DELETE':
                $delete = $sql->delete('acesso');
                $delete->where(['id' => $request->getHeader('x-id')]);

                $stmt = $sql->prepareStatementForSqlObject($delete);
                $recordSet = $stmt->execute();

                return new EmptyResponse(204);
            break;
        }
    }
}
