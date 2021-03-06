<?php

namespace Pinq\Parsing\PHPParser;

use Pinq\Parsing\FunctionStructure;
use Pinq\Parsing\IFunctionReflection;
use Pinq\Parsing\ParserBase;
use Pinq\Parsing\PHPParser\Visitors\FixedNamespaceResolver;

/**
 * Function parser implementation utilising nikic\PHP-Parser to
 * accurately locate and convert functions into the equivalent
 * expression tree
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class Parser extends ParserBase
{
    /**
     * The PHP-Parser parser instance, static because it is expensive
     * to instantiate.
     *
     * @var \PHPParser_Parser
     */
    private static $phpParser;

    /**
     * The array containing the located functions nodes grouped by the
     * file in which they were defined then grouped by their respective
     * location hashes.
     *
     * @var array<array<array<LocatedFunctionNode>>>
     */
    private static $locatedFunctions;

    public function __construct()
    {

    }

    protected function parseFunction(IFunctionReflection $reflection, $filePath)
    {
        if (self::$phpParser === null) {
            self::$phpParser = new \PHPParser_Parser(new \PHPParser_Lexer());
        }

        $locatedFunctionNodes = $this->getLocatedFunctionNodesIn($filePath);
        $matchingFunctionNode = $this->getMatchingFunctionNode($locatedFunctionNodes, $reflection);

        return new FunctionStructure(
                $matchingFunctionNode->getDeclaration(),
                AST::convert($matchingFunctionNode->getBodyNodes()));
    }

    private function getLocatedFunctionNodesIn($filePath)
    {
        if (!isset(self::$locatedFunctions[$filePath])) {
            $parsedNodes = self::$phpParser->parse(file_get_contents($filePath));

            //Resolve any relative, used or aliased types to their fully qualified equivalent
            $namespaceResolverTraverser = new \PHPParser_NodeTraverser();
            $namespaceResolver = new FixedNamespaceResolver();
            $namespaceResolverTraverser->addVisitor($namespaceResolver);
            $resolvedNodes = $namespaceResolverTraverser->traverse($parsedNodes);

            //Locate all function nodes
            $functionLocatorTraverser = new \PHPParser_NodeTraverser();
            $functionLocator = new Visitors\FunctionLocatorVisitor($filePath);
            $functionLocatorTraverser->addVisitor($functionLocator);

            $functionLocatorTraverser->traverse($resolvedNodes);

            self::$locatedFunctions[$filePath] = $functionLocator->getLocatedFunctionNodesMap();
        }

        return self::$locatedFunctions[$filePath];
    }

    /**
     * @param                      array <array<LocatedFunctionNode>> $locatedFunctionNodes
     * @param  IFunctionReflection $reflection
     *
     * @throws \Pinq\Parsing\InvalidFunctionException
     * @return LocatedFunctionNode
     */
    private function getMatchingFunctionNode(array $locatedFunctionNodes, IFunctionReflection $reflection)
    {
        $locationHash = $reflection->getLocation()->getHash();

        if (empty($locatedFunctionNodes[$locationHash])) {
            throw \Pinq\Parsing\InvalidFunctionException::invalidFunctionMessage(
                    'Cannot parse function, the function could not be located',
                    $reflection->getInnerReflection()
            );
        }

        //Should be multiple functions defined on a single line
        //Perform all possible resolution to resolve the conflict.
        //So we attempt to match on the function signature as well.
        //Magic constants / scopes are resolved in parameter expressions
        //to allow matching of functions with these special constants in
        //default values.
        /** @var $matchedFunctionsByLocation LocatedFunctionNode[] */
        $matchedFunctionsByLocation = $locatedFunctionNodes[$locationHash];
        $functionSignature          = $reflection->getSignature();
        $fullyMatchedFunctions      = [];

        foreach ($matchedFunctionsByLocation as $matchedFunction) {
            //Determine and resolve magic constants/scopes with the function reflection of which 
            //we are searching
            $magicData                        = $reflection->resolveMagic($matchedFunction->getDeclaration());
            $resolvedMatchedFunctionSignature = $matchedFunction->getSignature()->resolveMagic($magicData);

            if ($functionSignature->getHash() === $resolvedMatchedFunctionSignature->getHash()) {
                $fullyMatchedFunctions[] = $matchedFunction;
            }
        }

        if (empty($fullyMatchedFunctions)) {
            throw \Pinq\Parsing\InvalidFunctionException::invalidFunctionMessage(
                    'Cannot parse function, the function\'s signature could not be matched',
                    $reflection->getInnerReflection()
            );
        } elseif (count($fullyMatchedFunctions) > 1) {
            throw \Pinq\Parsing\InvalidFunctionException::invalidFunctionMessage(
                    'Cannot parse function, %d ambiguous functions are defined on the same line '
                    . 'with identical signatures',
                    $reflection->getInnerReflection(),
                    count($locatedFunctionNodes[$locationHash])
            );
        }

        return $fullyMatchedFunctions[0];
    }
}
