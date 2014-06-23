<?php

namespace Pinq\Iterators\Generators;

use Pinq\Iterators\IOrderedMap;
use Pinq\Iterators\Common;

/**
 * Implementation of the ordered map iterator using generators for iteration.
 *
 * @author Elliot Levin <elliot@aanet.com.au>
 */
class OrderedMap extends Generator implements IOrderedMap
{
    use Common\OrderedMap;
    
    public function __construct(\Traversable $iterator = null)
    {
        parent::__construct();
        
        if($iterator !== null) {
            $this->setAll($iterator);
        }
    }
    
    public function setAll(\Traversable $elements)
    {
        foreach($elements as $key => &$value) {
            $this->setRef($key, $value);
        }
    }
    
    public function &getIterator()
    {
        foreach($this->keys as $position => $key) {
            yield $key => $this->values[$position];
        }
    }
}
