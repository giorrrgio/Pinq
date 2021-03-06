<?php

namespace Pinq\Iterators\Generators;

use Pinq\Iterators\Common;
use Pinq\Iterators\IJoinToIterator;

/**
 * Implementation of the join iterator using generators.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
abstract class JoinIterator extends IteratorGenerator implements IJoinToIterator
{
    use Common\JoinIterator;

    /**
     * @var IGenerator
     */
    protected $outerIterator;

    /**
     * @var IGenerator
     */
    protected $innerIterator;

    public function __construct(IGenerator $outerIterator, IGenerator $innerIterator)
    {
        parent::__construct($outerIterator);
        self::__constructIterator();
        $this->outerIterator =& $this->iterator;
        $this->innerIterator = $innerIterator;
    }

    public function walk(callable $function)
    {
        foreach ($this->outerIterator as $outerKey => &$outerValue) {
            foreach ($this->innerGenerator($outerKey, $outerValue) as $innerKey => &$innerValue) {
                $function($outerValue, $innerValue, $outerKey, $innerKey);
            }
        }
    }

    final protected function &iteratorGenerator(IGenerator $iterator)
    {
        $projectionFunction = $this->projectionFunction;
        $count              = 0;

        foreach ($this->outerIterator as $outerKey => $outerValue) {
            foreach ($this->innerGenerator($outerKey, $outerValue) as $innerKey => $innerValue) {
                $value = $projectionFunction($outerValue, $innerValue, $outerKey, $innerKey);
                yield $count++ => $value;
                unset($value);
            }
        }
    }

    final protected function defaultIterator(IGenerator $iterator)
    {
        return $this->hasDefault ?
                new CoalesceIterator($iterator, $this->defaultValue, $this->defaultKey) : $iterator;
    }

    /**
     * @param mixed $outerKey
     * @param mixed $outerValue
     *
     * @return IGenerator
     */
    abstract protected function innerGenerator($outerKey, $outerValue);
}
