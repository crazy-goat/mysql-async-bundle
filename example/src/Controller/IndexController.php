<?php

declare(strict_types=1);

namespace App\Controller;

use App\MysqlAsyncPoll;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public function __construct(private readonly MysqlAsyncPoll $asyncConnection, private readonly Connection $syncConnection)
    {
    }

    #[Route('/')]
    #[Route('/async')]
    public function async(): Response
    {
        $result1 = $this->asyncConnection->executeQuery('SELECT "query 1", SLEEP(0.05)');
        $result2 = $this->asyncConnection->executeQuery('SELECT "query 1", SLEEP(0.05)');
        $result3 = $this->asyncConnection->executeQuery('SELECT "query 1", SLEEP(0.05)');

        return new JsonResponse([
            'result1' => $result1->fetchScalar(),
            'result2' => $result2->fetchScalar(),
            'result3' => $result3->fetchScalar(),
        ]);
    }

    #[Route('/sync')]
    public function sync(): Response
    {
        $result1 = $this->syncConnection->executeQuery('SELECT "query 1", SLEEP(0.05)');
        $result2 = $this->syncConnection->executeQuery('SELECT "query 1", SLEEP(0.05)');
        $result3 = $this->syncConnection->executeQuery('SELECT "query 1", SLEEP(0.05)');

        return new JsonResponse([
            'result1' => $result1->fetchOne(),
            'result2' => $result2->fetchOne(),
            'result3' => $result3->fetchOne(),
        ]);
    }
}
