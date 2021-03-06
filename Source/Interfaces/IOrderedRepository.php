<?php

namespace Pinq\Interfaces;

use Pinq\IRepository;

/**
 * The API for subsequent orderings of a IRepository
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
interface IOrderedRepository extends IRepository, IOrderedCollection
{
    /**
     * {@inheritDoc}
     * @return IOrderedRepository
     */
    public function thenBy(callable $function, $direction);

    /**
     * {@inheritDoc}
     * @return IOrderedRepository
     */
    public function thenByAscending(callable $function);

    /**
     * {@inheritDoc}
     * @return IOrderedRepository
     */
    public function thenByDescending(callable $function);
}
