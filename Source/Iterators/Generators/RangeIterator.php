<?php

namespace Pinq\Iterators\Generators;

use Pinq\Iterators\Common;

/**
 * Implementation of the range iterator using generators.
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
class RangeIterator extends IteratorGenerator
{
    use Common\RangeIterator;
    
    public function __construct(\Traversable $iterator, $startAmount, $rangeAmount)
    {
        parent::__construct($iterator);
        self::__constructIterator($startAmount, $rangeAmount);
    }
    
    protected function iteratorGenerator(\Traversable $iterator)
    {
        $start = $this->startPosition;
        $end = $this->endPosition;
        
        $position = 0;
        
        foreach($iterator as $key => $value) {
            if($end !== null && $position >= $end) {
                break;
            } elseif ($position >= $start) {
                yield $key => $value;
            }
            
            $position++;
        }
    }
}
