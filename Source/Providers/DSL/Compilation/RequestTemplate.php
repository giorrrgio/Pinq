<?php

namespace Pinq\Providers\DSL\Compilation;

use Pinq\Queries;

/**
 * Base class of the request template interface.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class RequestTemplate extends QueryTemplate implements IRequestTemplate
{
    public function __construct(Queries\IParameterRegistry $parameters, array $structuralParameterNames)
    {
        parent::__construct($parameters, $structuralParameterNames);
    }
}