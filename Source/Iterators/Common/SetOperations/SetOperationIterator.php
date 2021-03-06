<?php

namespace Pinq\Iterators\Common\SetOperations;

/**
 * Common functionality for a set operation iterator
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
trait SetOperationIterator
{
    /**
     * @var ISetFilter
     */
    protected $setFilter;

    final protected function __constructIterator(ISetFilter $setFilter)
    {
        $this->setFilter = $setFilter;
    }
}
