<?php

namespace Pinq\Parsing;

use Pinq\Expressions as O;

/**
 * Implementation of the function interpreter interface.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class FunctionInterpreter implements IFunctionInterpreter
{
    /**
     * @var IParser
     */
    protected $parser;

    /**
     * @var IFunctionStructure[]
     */
    protected static $resolvedFunctionCache;

    public function __construct(IParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @return IFunctionInterpreter
     */
    public static function getDefault()
    {
        return new self(new PHPParser\Parser());
    }

    final public function getParser()
    {
        return $this->parser;
    }

    public function getReflection(callable $function)
    {
        return FunctionReflection::fromCallable($function);
    }

    public function getStructure(IFunctionReflection $reflection)
    {
        $globalHash = $reflection->getGlobalHash();

        if (!isset(self::$resolvedFunctionCache[$globalHash])) {
            $functionStructure                        = $this->parser->parse($reflection);
            $functionMagic                            = $reflection->resolveMagic($functionStructure->getDeclaration());
            self::$resolvedFunctionCache[$globalHash] = $functionStructure->resolveMagic($functionMagic);
        }

        return self::$resolvedFunctionCache[$globalHash];
    }
}
