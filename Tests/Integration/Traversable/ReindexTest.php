<?php

namespace Pinq\Tests\Integration\Traversable;

class ReindexTest extends TraversableTest
{
    protected function _testReturnsNewInstanceOfSameTypeWithSameScheme(\Pinq\ITraversable $traversable)
    {
        return $traversable->reindex();
    }

    /**
     * @dataProvider theImplementations
     */
    public function testThatExecutionIsDeferred(\Pinq\ITraversable $traversable, array $data)
    {
        $this->assertThatExecutionIsDeferred(function (callable $function) use ($traversable) {
            return $traversable->where($function)->reindex();
        });
    }

    /**
     * @dataProvider everything
     */
    public function testThatValuesReindexesTheValuesByTheirZeroBasedPosition(\Pinq\ITraversable $traversable, array $data)
    {
        $this->assertMatches($traversable->reindex(), array_values($data));
    }

    /**
     * @dataProvider everything
     */
    public function testThatKeysSupportNonScalarKeys(\Pinq\ITraversable $traversable, array $data)
    {
        $values = $traversable
                ->indexBy(function () { return new \stdClass(); })
                ->reindex();

        $this->assertMatches($values->reindex(), array_values($data));
    }
}
