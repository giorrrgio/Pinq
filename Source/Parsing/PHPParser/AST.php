<?php

namespace Pinq\Parsing\PHPParser;

use Pinq\Expressions as O;
use Pinq\Expressions\Expression;
use Pinq\Expressions\Operators;
use Pinq\Parsing\ASTException;

/**
 * Converts the PHP-Parser nodes into the equivalent expression tree.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AST
{
    /**
     * @var \PHPParser_Node[]
     */
    private $nodes = [];

    public function __construct(array $nodes)
    {
        $this->nodes = $nodes;
    }

    /**
     * Converts the supplied php parser nodes to an equivalent
     * expression tree.
     *
     * @param \PHPParser_Node[] $nodes
     *
     * @return Expression[]
     */
    public static function convert(array $nodes)
    {
        return (new self($nodes))->getExpressions();
    }

    /**
     * Parses the nodes into the equivalent expression tree
     *
     * @return Expression[]
     */
    public function getExpressions()
    {
        return $this->parseNodes($this->nodes);
    }

    /**
     * @param \PHPParser_Node[] $nodes
     *
     * @return Expression[]
     */
    private function parseNodes(array $nodes)
    {
        return array_map(
                function ($node) {
                    return $this->parseNode($node);
                },
                $nodes
        );
    }

    /**
     * @param \PHPParser_Node $node
     *
     * @throws \Pinq\Parsing\ASTException
     * @return Expression
     */
    private function parseNode(\PHPParser_Node $node)
    {
        switch (true) {
            case $node instanceof \PHPParser_Node_Stmt:
                return $this->parseStatementNode($node);

            case $node instanceof \PHPParser_Node_Expr:
                return $this->parseExpressionNode($node);

            case $node instanceof \PHPParser_Node_Param:
                return $this->parseParameterNode($node);

            //Irrelevant node, no call time pass by ref anymore
            case $node instanceof \PHPParser_Node_Arg:
                return $this->parseNode($node->value);

            default:
                throw new ASTException('Unsupported node type: %s', get_class($node));
        }
    }

    /**
     * @param $node
     *
     * @return Expression
     */
    final public function parseNameNode($node)
    {
        if ($node instanceof \PHPParser_Node_Name) {
            return Expression::value(($node->isFullyQualified() ? '\\' : '') . (string)$node);
        } elseif (is_string($node)) {
            return Expression::value($node);
        }

        return $this->parseNode($node);
    }

    private function parseParameterNode(\PHPParser_Node_Param $node)
    {
        $type = $node->type;
        if ($type !== null) {
            $type = (string)$type;
            $lowerType = strtolower($type);
            if ($type[0] !== '\\' && $lowerType !== 'array' && $lowerType !== 'callable') {
                $type = '\\' . $type;
            }
        }

        return Expression::parameter(
                $node->name,
                $type,
                $node->default === null ? null : $this->parseNode($node->default),
                $node->byRef
        );
    }

    // <editor-fold defaultstate="collapsed" desc="Expression node parsers">

    public function parseExpressionNode(\PHPParser_Node_Expr $node)
    {
        $fullNodeName = get_class($node);
        $nodeType     = str_replace('PHPParser_Node_Expr_', '', $fullNodeName);
        switch (true) {
            case $mappedNode = $this->parseOperatorNode($node, $nodeType):
                return $mappedNode;

            case $node instanceof \PHPParser_Node_Scalar
                    && $mappedNode = $this->parseScalarNode($node):
                return $mappedNode;

            case $node instanceof \PHPParser_Node_Expr_Variable:
                return Expression::variable($this->parseNameNode($node->name));

            case $node instanceof \PHPParser_Node_Expr_Array:
                return $this->parseArrayNode($node);

            case $node instanceof \PHPParser_Node_Expr_FuncCall:
                return $this->parseFunctionCallNode($node);

            case $node instanceof \PHPParser_Node_Expr_New:
                return Expression::newExpression(
                        $this->parseNameNode($node->class),
                        $this->parseNodes($node->args)
                );

            case $node instanceof \PHPParser_Node_Expr_MethodCall:
                return Expression::methodCall(
                        $this->parseNode($node->var),
                        $this->parseNameNode($node->name),
                        $this->parseNodes($node->args)
                );

            case $node instanceof \PHPParser_Node_Expr_PropertyFetch:
                return Expression::field(
                        $this->parseNode($node->var),
                        $this->parseNameNode($node->name)
                );

            case $node instanceof \PHPParser_Node_Expr_ArrayDimFetch:
                return Expression::index(
                        $this->parseNode($node->var),
                        $node->dim === null ? null : $this->parseNode($node->dim)
                );

            case $node instanceof \PHPParser_Node_Expr_ConstFetch:
                return Expression::constant((string)$node->name);

            case $node instanceof \PHPParser_Node_Expr_ClassConstFetch:
                return Expression::classConstant(
                        $this->parseNameNode($node->class),
                        $node->name
                );

            case $node instanceof \PHPParser_Node_Expr_StaticCall:
                return Expression::staticMethodCall(
                        $this->parseNameNode($node->class),
                        $this->parseNameNode($node->name),
                        $this->parseNodes($node->args)
                );

            case $node instanceof \PHPParser_Node_Expr_StaticPropertyFetch:
                return Expression::staticField(
                        $this->parseNameNode($node->class),
                        $this->parseNameNode($node->name)
                );

            case $node instanceof \PHPParser_Node_Expr_Ternary:
                return $this->parseTernaryNode($node);

            case $node instanceof \PHPParser_Node_Expr_Closure:
                return $this->parseClosureNode($node);

            case $node instanceof \PHPParser_Node_Expr_Empty:
                return Expression::emptyExpression($this->parseNode($node->expr));

            case $node instanceof \PHPParser_Node_Expr_Isset:
                return Expression::issetExpression($this->parseNodes($node->vars));

            default:
                throw new ASTException(
                        'Cannot parse AST with unknown expression node: %s',
                        get_class($node));
        }
    }

    private function parseArrayNode(\PHPParser_Node_Expr_Array $node)
    {
        $itemExpressions = [];

        foreach ($node->items as $item) {
            //Keys must match
            $itemExpressions[] = Expression::arrayItem(
                    $item->key === null ? null : $this->parseNode($item->key),
                    $this->parseNode($item->value),
                    $item->byRef
            );
        }

        return Expression::arrayExpression($itemExpressions);
    }

    private function parseFunctionCallNode(\PHPParser_Node_Expr_FuncCall $node)
    {
        $nameExpression = $this->parseNameNode($node->name);

        if ($nameExpression instanceof O\TraversalExpression || $nameExpression instanceof O\VariableExpression) {
            return Expression::invocation(
                    $nameExpression,
                    $this->parseNodes($node->args)
            );
        } else {
            return Expression::functionCall(
                    $nameExpression,
                    $this->parseNodes($node->args)
            );
        }
    }

    private function parseTernaryNode(\PHPParser_Node_Expr_Ternary $node)
    {
        return Expression::ternary(
                $this->parseNode($node->cond),
                $node->if === null ? null : $this->parseNode($node->if),
                $this->parseNode($node->else)
        );
    }

    private function parseClosureNode(\PHPParser_Node_Expr_Closure $node)
    {
        $parameterExpressions = [];

        foreach ($node->params as $parameterNode) {
            $parameterExpressions[] = $this->parseParameterNode($parameterNode);
        }

        $usedVariables   = [];
        foreach($node->uses as $usedVariable) {
            $usedVariables[] =  Expression::closureUsedVariable($usedVariable->var, $usedVariable->byRef);
        }
        $bodyExpressions = $this->parseNodes($node->stmts);

        return Expression::closure(
                $node->byRef,
                $node->static,
                $parameterExpressions,
                $usedVariables,
                $bodyExpressions
        );
    }

    private function parseScalarNode(\PHPParser_Node_Scalar $node)
    {
        switch (true) {
            case $node instanceof \PHPParser_Node_Scalar_DNumber:
            case $node instanceof \PHPParser_Node_Scalar_LNumber:
            case $node instanceof \PHPParser_Node_Scalar_String:
                return Expression::value($node->value);

            case $node instanceof \PHPParser_Node_Scalar_DirConst:
                return Expression::constant('__DIR__');

            case $node instanceof \PHPParser_Node_Scalar_FileConst:
                return Expression::constant('__FILE__');

            case $node instanceof \PHPParser_Node_Scalar_NSConst:
                return Expression::constant('__NAMESPACE__');

            case $node instanceof \PHPParser_Node_Scalar_ClassConst:
                return Expression::constant('__CLASS__');

            case $node instanceof \PHPParser_Node_Scalar_TraitConst:
                return Expression::constant('__TRAIT__');

            case $node instanceof \PHPParser_Node_Scalar_FuncConst:
                return Expression::constant('__FUNCTION__');

            case $node instanceof \PHPParser_Node_Scalar_MethodConst:
                return Expression::constant('__METHOD__');

            case $node instanceof \PHPParser_Node_Scalar_LineConst:
                return Expression::value($node->getAttribute('startLine'));

            default:
                return;
        }
    }

    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Statement node parsers">

    private function parseStatementNode(\PHPParser_Node_Stmt $node)
    {
        switch (true) {

            case $node instanceof \PHPParser_Node_Stmt_Return:
                return Expression::returnExpression($node->expr !== null ? $this->parseNode($node->expr) : null);

            case $node instanceof \PHPParser_Node_Stmt_Throw:
                return Expression::throwExpression($this->parseNode($node->expr));

            case $node instanceof \PHPParser_Node_Stmt_Unset:
                return Expression::unsetExpression($this->parseNodes($node->vars));

            default:
                $this->verifyNotControlStructure($node);
                throw new ASTException(
                        'Cannot parse AST with unknown statement node: %s',
                        get_class($node));
        }
    }

    private static $constructStructureMap = [
            'Do'       => ASTException::DO_WHILE_LOOP,
            'For'      => ASTException::FOR_LOOP,
            'Foreach'  => ASTException::FOREACH_LOOP,
            'Goto'     => ASTException::GOTO_STATEMENT,
            'If'       => ASTException::IF_STATEMENT,
            'Switch'   => ASTException::SWITCH_STATEMENT,
            'TryCatch' => ASTException::TRY_CATCH_STATEMENT,
            'While'    => ASTException::WHILE_LOOP
    ];

    private function verifyNotControlStructure(\PHPParser_Node_Stmt $node)
    {
        $nodeType = str_replace('PHPParser_Node_Stmt_', '', get_class($node));

        if (isset(self::$constructStructureMap[$nodeType])) {
            throw ASTException::containsControlStructure(
                    self::$constructStructureMap[$nodeType],
                    $node->getAttribute('startLine')
            );
        }
    }

    // </editor-fold>

    // <editor-fold defaultstate="collapsed" desc="Operator node maps">

    private function parseOperatorNode(\PHPParser_Node_Expr $node, $nodeType)
    {
        switch (true) {

            case isset(self::$assignOperatorsMap[$nodeType]):
                return Expression::assign(
                        $this->parseNode($node->var),
                        self::$assignOperatorsMap[$nodeType],
                        $this->parseNode($node->expr)
                );

            case $node instanceof \PHPParser_Node_Expr_Instanceof:
                return Expression::binaryOperation(
                        $this->parseNode($node->expr),
                        Operators\Binary::IS_INSTANCE_OF,
                        $this->parseNameNode($node->class)
                );

            case isset(self::$binaryOperatorsMap[$nodeType]):
                return Expression::binaryOperation(
                        $this->parseNode($node->left),
                        self::$binaryOperatorsMap[$nodeType],
                        $this->parseNode($node->right)
                );

            case isset(self::$unaryOperatorsMap[$nodeType]):
                return Expression::unaryOperation(
                        self::$unaryOperatorsMap[$nodeType],
                        $this->parseNode($node->expr ? : $node->var)
                );

            case isset(self::$castOperatorMap[$nodeType]):
                return Expression::cast(
                        self::$castOperatorMap[$nodeType],
                        $this->parseNode($node->expr)
                );

            default:
                return null;
        }
    }

    private static $unaryOperatorsMap = [
            'BitwiseNot' => Operators\Unary::BITWISE_NOT,
            'BooleanNot' => Operators\Unary::NOT,
            'PostInc'    => Operators\Unary::INCREMENT,
            'PostDec'    => Operators\Unary::DECREMENT,
            'PreInc'     => Operators\Unary::PRE_INCREMENT,
            'PreDec'     => Operators\Unary::PRE_DECREMENT,
            'UnaryMinus' => Operators\Unary::NEGATION,
            'UnaryPlus'  => Operators\Unary::PLUS
    ];

    private static $castOperatorMap = [
            'Cast_Array'  => Operators\Cast::ARRAY_CAST,
            'Cast_Bool'   => Operators\Cast::BOOLEAN,
            'Cast_Double' => Operators\Cast::DOUBLE,
            'Cast_Int'    => Operators\Cast::INTEGER,
            'Cast_Object' => Operators\Cast::OBJECT,
            'Cast_String' => Operators\Cast::STRING
    ];

    private static $binaryOperatorsMap = [
            'BitwiseAnd'     => Operators\Binary::BITWISE_AND,
            'BitwiseOr'      => Operators\Binary::BITWISE_OR,
            'BitwiseXor'     => Operators\Binary::BITWISE_XOR,
            'ShiftLeft'      => Operators\Binary::SHIFT_LEFT,
            'ShiftRight'     => Operators\Binary::SHIFT_RIGHT,
            'BooleanAnd'     => Operators\Binary::LOGICAL_AND,
            'BooleanOr'      => Operators\Binary::LOGICAL_OR,
            'LogicalAnd'     => Operators\Binary::LOGICAL_AND,
            'LogicalOr'      => Operators\Binary::LOGICAL_OR,
            'Plus'           => Operators\Binary::ADDITION,
            'Minus'          => Operators\Binary::SUBTRACTION,
            'Mul'            => Operators\Binary::MULTIPLICATION,
            'Div'            => Operators\Binary::DIVISION,
            'Mod'            => Operators\Binary::MODULUS,
            'Concat'         => Operators\Binary::CONCATENATION,
            'Equal'          => Operators\Binary::EQUALITY,
            'Identical'      => Operators\Binary::IDENTITY,
            'NotEqual'       => Operators\Binary::INEQUALITY,
            'NotIdentical'   => Operators\Binary::NOT_IDENTICAL,
            'Smaller'        => Operators\Binary::LESS_THAN,
            'SmallerOrEqual' => Operators\Binary::LESS_THAN_OR_EQUAL_TO,
            'Greater'        => Operators\Binary::GREATER_THAN,
            'GreaterOrEqual' => Operators\Binary::GREATER_THAN_OR_EQUAL_TO
    ];

    private static $assignOperatorsMap = [
            'Assign'           => Operators\Assignment::EQUAL,
            'AssignBitwiseAnd' => Operators\Assignment::BITWISE_AND,
            'AssignBitwiseOr'  => Operators\Assignment::BITWISE_OR,
            'AssignBitwiseXor' => Operators\Assignment::BITWISE_XOR,
            'AssignConcat'     => Operators\Assignment::CONCATENATE,
            'AssignDiv'        => Operators\Assignment::DIVISION,
            'AssignMinus'      => Operators\Assignment::SUBTRACTION,
            'AssignMod'        => Operators\Assignment::MODULUS,
            'AssignMul'        => Operators\Assignment::MULTIPLICATION,
            'AssignPlus'       => Operators\Assignment::ADDITION,
            'AssignRef'        => Operators\Assignment::EQUAL_REFERENCE,
            'AssignShiftLeft'  => Operators\Assignment::SHIFT_LEFT,
            'AssignShiftRight' => Operators\Assignment::SHIFT_RIGHT
    ];

    // </editor-fold>
}
