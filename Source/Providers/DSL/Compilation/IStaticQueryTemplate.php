<?php

namespace Pinq\Providers\DSL\Compilation;

use Pinq\Queries;

/**
 * Interface for a request / operation query that contains no
 * structural parameters and hence stores the fully compiled query.
 *
*@author Elliot Levin <elliotlevin@hotmail.com>
 */
interface IStaticQueryTemplate extends IQueryTemplate
{
    /**
     * Gets the original query object.
     *
     * @return ICompiledQuery
     */
    public function getCompiledQuery();
}
