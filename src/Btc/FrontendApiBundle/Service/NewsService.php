<?php

namespace Btc\FrontendApiBundle\Service;

use Doctrine\ORM\EntityManager;
use Exmarkets\NewsBundle\Repository\ArticleRepository;

class NewsService extends RestService
{
    private $articleRepository;

    public function __construct(EntityManager $em, $entityClass, ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;

        parent::__construct($em, $entityClass);
    }

    public function findAllPublished($limit = 10, $offset = 0)
    {
        return $this->articleRepository
            ->findAllPublished($limit, $offset);
    }

    public function findOneBySlug($slug)
    {
        return $this->articleRepository
            ->findOneBySlug($slug);
    }
}
