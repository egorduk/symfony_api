<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\RestEntityInterface;
use Doctrine\ORM\EntityManager;

class RestService implements RestServiceInterface
{
    private $em;
    private $entityClass;
    private $repository;

    public function __construct(EntityManager $em, $entityClass)
    {
        $this->em = $em;
        $this->entityClass = $entityClass;
        $this->repository = $this->em->getRepository($this->entityClass);
    }

    /**
     * @param int $id
     *
     * @return RestServiceInterface
     */
    public function get($id)
    {
        return $this->repository
            ->find($id);
    }

    /**
     * @param array $parameters
     *
     * @return RestServiceInterface
     */
    public function getOneBy(array $parameters)
    {
        return $this->repository
            ->findOneBy($parameters);
    }

    /**
     * @param int $limit  the limit of the result
     * @param int $offset starting from the offset
     *
     * @return RestServiceInterface[]
     */
    public function all($limit = 10, $offset = 0)
    {
        return $this->repository
            ->findBy([], null, $limit, $offset);
    }

    /**
     * @param RestEntityInterface $obj
     * @param array               $parameters
     *
     * @return RestEntityInterface
     */
    public function put(RestEntityInterface $obj, array $parameters = null)
    {
        return $this->repository->save($obj, true);
    }

    /**
     * @param RestEntityInterface $obj
     * @param array               $parameters
     * @param bool                $isFlush
     *
     * @return RestEntityInterface
     */
    public function patch(RestEntityInterface $obj, array $parameters = null, $isFlush = true)
    {
        if (is_null($parameters)) {
            return $this->repository->save($obj, $isFlush);
        }
    }

    /**
     * @param array $parameters
     *
     * @return RestEntityInterface
     */
    public function post(array $parameters)
    {
        return $this->createEntity();
    }

    /**
     * @deprecated
     *
     * @param RestEntityInterface $obj
     *
     * @return bool
     */
    public function delete(RestEntityInterface $obj)
    {
        return $this->repository
            ->remove($obj, true);
    }

    /**
     * Gets new instance.
     *
     * @return RestEntityInterface
     */
    public function createEntity()
    {
        return new $this->entityClass();
    }
}
