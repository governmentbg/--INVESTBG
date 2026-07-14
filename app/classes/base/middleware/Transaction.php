<?php

declare(strict_types=1);

namespace base\middleware;

use vakata\database\DBInterface;
use vakata\http\Request;
use vakata\http\Response;

class Transaction
{
    protected DBInterface $db;

    public function __construct(DBInterface $db)
    {
        $this->db = $db;
    }
    /**
     * @param Request $req
     * @param callable(Request):Response $next
     * @return Response
     */
    public function __invoke(Request $req, callable $next): Response
    {
        $this->db->begin(true);
        try {
            $res = $next($req);
            $this->db->commit(true);
        } catch (\Throwable $e) {
            $this->db->rollback(true);
            throw $e;
        }
        return $res;
    }
}
