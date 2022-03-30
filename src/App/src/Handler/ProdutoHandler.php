<?php

namespace App\Handler;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use Laminas\Db\Sql\Sql;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProdutoHandler implements RequestHandlerInterface
{
    public function __construct(
        private Adapter $dbAdapter
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = [];

        $method = $request->getMethod();
        $idProduto = $request->getAttribute("idProduto");

        $sql = new Sql($this->dbAdapter);

        switch ($method) {
            case 'GET':
                $data = [];

                $select = $sql->select('produto');
                $select->columns(['id', 'categoria_id', 'nome', 'preco']);

                $queryParams = $request->getQueryParams();
                if (!empty($queryParams["id"]) && is_numeric($queryParams["id"])) {
                    $select->where(['id' => (int)$queryParams["id"]]);
                }

                $stmt = $sql->prepareStatementForSqlObject($select);
                $recordSet = $stmt->execute();

                while (($record = $recordSet->current()) !== false) {
                    $data[] = $record;

                    $recordSet->next();
                }

                return new JsonResponse(empty($data) ? ["message" => "Sem registro de produtos para esse filtro!"] : $data);

            case 'POST':
                $body = json_decode($request->getBody()->getContents());

                $select = $sql->select('categoria');
                $select->columns(['id']);
                $select->where(['id' => (int)$body->categoria_id]);

                $stmt = $sql->prepareStatementForSqlObject($select);
                $recordSet = $stmt->execute();

                if ($recordSet->count() <= 0) {
                    return new JsonResponse(["message" => "Não existe nenhuma categoria com o ID informado"], StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
                }

                try {
                    $insert = $sql->insert('produto');
                    $insert->values(['nome' => $body->nome, 'categoria_id' => (int)$body->categoria_id, 'preco' => (float) $body->preco], $insert::VALUES_MERGE);
                    $stmt = $sql->prepareStatementForSqlObject($insert);
                    $stmt->execute();
                } catch (InvalidQueryException $e) {
                    return new JsonResponse(
                        [
                            "message" => sprintf(
                                "%s - %s",
                                "Erro ao executar query. Detalhes: ",
                                $e->getMessage()
                            )
                        ],
                        StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
                    );
                }

                return new EmptyResponse(201);

            case 'PATCH':
                if (empty($idProduto) || !is_numeric($idProduto)) {
                    return new JsonResponse(
                        ["message" => "ID do produto inválido"],
                        StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
                    );
                }

                $body = json_decode($request->getBody()->getContents());

                if (!empty($body->nome)) {
                    $data["nome"] = $body->nome;
                }

                if (!empty($body->categoria_id) && is_numeric($body->categoria_id)) {
                    $data["categoria_id"] = (int)$body->categoria_id;
                }

                if (!empty($body->preco)) {
                    $data["preco"] = $body->preco;
                }

                $update = $sql->update('produto');
                $update->set($data);
                $update->where(['id' => (int)$idProduto]);

                $stmt = $sql->prepareStatementForSqlObject($update);
                $recordSet = $stmt->execute();

                return new EmptyResponse(204);

            case 'DELETE':
                if (empty($idProduto) || !is_numeric($idProduto)) {
                    return new JsonResponse(
                        ["message" => "ID do produto inválido"],
                        StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
                    );
                }

                $select = $sql->select('produto');
                $select->columns(['id']);
                $select->where(['id' => (int)$idProduto]);

                $stmt = $sql->prepareStatementForSqlObject($select);
                $recordSet = $stmt->execute();

                if ($recordSet->count() <= 0) {
                    return new JsonResponse(["message" => "Não existe nenhum produto com este ID para excluir"], StatusCodeInterface::STATUS_NOT_FOUND);
                }

                $delete = $sql->delete('produto');
                $delete->where(['id' => (int)$idProduto]);

                $stmt = $sql->prepareStatementForSqlObject($delete);
                $recordSet = $stmt->execute();

                return new EmptyResponse(204);
        }

        return new JsonResponse(["message" => "Não há produtos registrados."]);
    }
}
