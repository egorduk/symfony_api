<?php

namespace Btc\FrontendApiBundle\Service;

use Btc\CoreBundle\Entity\RestEntityInterface;

interface RestServiceInterface
{
    /**
     * Gets the item.
     *
     * @api
     *
     * @param int $id
     *
     * @return RestEntityInterface
     */
    public function get($id);

    /**
     * Get a list of items.
     *
     * @param int $limit  the limit of the result
     * @param int $offset starting from the offset
     *
     * @return array
     */
    public function all($limit = 10, $offset = 0);

    /**
     * Creates new item.
     *
     * @api
     *
     * @param array $parameters
     *
     * @return RestEntityInterface
     */
    public function post(array $parameters);

    /**
     * Edits an item.
     *
     * @api
     *
     * @param RestEntityInterface $obj
     * @param array               $parameters
     *
     * @return RestEntityInterface
     */
    public function put(RestEntityInterface $obj, array $parameters);

    /**
     * Partially updates the item.
     *
     * @api
     *
     * @param RestEntityInterface $obj
     * @param array               $parameters
     *
     * @return RestEntityInterface
     */
    public function patch(RestEntityInterface $obj, array $parameters);

    /**
     * Deletes the item.
     *
     * @api
     *
     * @param RestEntityInterface $obj
     *
     * @return bool
     */
    public function delete(RestEntityInterface $obj);
}
