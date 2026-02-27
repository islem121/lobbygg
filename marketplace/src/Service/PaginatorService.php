<?php

namespace App\Service;

use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginatorService
{
    /**
     * @param Query $query
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function paginate(Query $query, int $page = 1, int $limit = 10): array
    {
        $paginator = new Paginator($query);
        
        $paginator
            ->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        $totalItems = count($paginator);
        $pagesCount = ceil($totalItems / $limit);

        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'total' => $totalItems,
            'pages' => $pagesCount,
            'current_page' => $page,
            'limit' => $limit
        ];
    }
}
