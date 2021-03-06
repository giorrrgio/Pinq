<?php

namespace Pinq\Iterators\Generators;

use Pinq\Iterators\Common;
use Pinq\Iterators\IJoinIterator;

/**
 * Implementation of the join iterator using generators.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class UnfilteredJoinIterator extends JoinIterator implements IJoinIterator
{
    public function filterOn(callable $function)
    {
        return new JoinOnIterator(
                $this->outerIterator,
                $this->innerIterator,
                $function);
    }

    public function filterOnEquality(callable $outerKeyFunction, callable $innerKeyFunction)
    {
        return new JoinOnEqualityIterator(
                $this->outerIterator,
                $this->innerIterator,
                $outerKeyFunction,
                $innerKeyFunction);
    }

    protected function innerGenerator($outerKey, $outerValue)
    {
        return new OrderedMap($this->defaultIterator($this->innerIterator));
    }
}
