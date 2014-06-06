<?php

namespace Pinq\Iterators\Common;

use Pinq\Iterators\IOrderedIterator;
use Pinq\Iterators\IOrderedMap;

/**
 * Common functionality for the ordered iterator
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
trait OrderedIterator
{
    /**
     * @var callable[]
     */
    protected $orderByFunctions = [];

    /**
     * @var boolean[]
     */
    protected $isAscendingArray = [];

    protected function __constructIterator(callable $orderByFunction, $isAscending)
    {
        $this->orderByFunctions[] = Functions::allowExcessiveArguments($orderByFunction);
        $this->isAscendingArray[] = $isAscending;
    }
    
    /**
     * @param callable $orderByFunction
     * @param boolean $isAscending
     * @return IOrderedIterator
     */
    final public function thenOrderBy(callable $orderByFunction, $isAscending)
    {
        $newOrderedIterator = new self($this->getInnerIterator(), function () {}, true);
        
        $newOrderedIterator->orderByFunctions = $this->orderByFunctions;
        $newOrderedIterator->isAscendingArray = $this->isAscendingArray;
        $newOrderedIterator->orderByFunctions[] = Functions::allowExcessiveArguments($orderByFunction);
        $newOrderedIterator->isAscendingArray[] = $isAscending;

        return $newOrderedIterator;
    }
    
    /**
     * @return \Traversable
     */
    abstract protected function getInnerIterator();

    final protected function sortMap(IOrderedMap $map)
    {
        return $map->multisort($this->orderByFunctions, $this->isAscendingArray);
    }
}
