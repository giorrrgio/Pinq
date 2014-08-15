<?php

namespace Pinq\Queries\Builders;

use Pinq\Expressions as O;

/**
 * Base class for expression interpreters.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
abstract class ExpressionInterpreter
{
    /**
     * @var string
     */
    protected $idPrefix;

    /**
     * @var O\IEvaluationContext|null
     */
    protected $evaluationContext;

    /**
     * @var string|null
     */
    protected $closureNamespace;

    public function __construct($idPrefix, O\IEvaluationContext $evaluationContext = null)
    {
        $this->idPrefix          = $idPrefix;
        $this->evaluationContext = $evaluationContext;
    }

    final protected function getId($id)
    {
        return "{$this->idPrefix}-{$id}";
    }

    final protected function getFunction($id, O\Expression $expression)
    {
        if ($expression instanceof O\ValueExpression) {
            return new Functions\CallableFunction($this->getId($id), $expression->getValue());
        } elseif ($expression instanceof O\ClosureExpression) {
            return new Functions\ClosureExpressionFunction(
                    $this->getId($id), $expression, $this->evaluationContext);
        } else {
            throw new \Pinq\PinqException(
                    'Cannot get function from expression: expecting %s, %s given',
                    O\ClosureExpression::getType() . ' or ' . O\ValueExpression::getType(),
                    $expression->getType());
        }
    }

    final protected function getOptionalFunctionAt($name, $index, O\MethodCallExpression $expression)
    {
        $argument = $this->getOptionalArgumentAt($index, $expression);

        if ($argument === null || ($argument instanceof O\ValueExpression && $argument->getValue() === null)) {
            return null;
        }

        return $this->getFunction("{$name}-{$index}", $argument);
    }

    final protected function getFunctionAt($name, $index, O\MethodCallExpression $expression)
    {
        return $this->getFunction("{$name}-{$index}", $this->getArgumentAt($index, $expression));
    }

    final protected function getArgumentValueAt($index, O\MethodCallExpression $methodExpression)
    {
        $instance = new \stdClass();

        $argument = $this->getOptionalArgumentValueAt($index, $methodExpression, $instance);

        if ($argument === $instance) {
            throw new \Pinq\PinqException(
                    'Could not get argument value of method %s at index %d: argument not supplied',
                    $methodExpression->getName()->compileDebug(),
                    $index);
        }

        return $argument;
    }

    final protected function getOptionalArgumentValueAt(
            $index,
            O\MethodCallExpression $methodExpression,
            $default = null
    ) {
        $argumentExpression = $this->getOptionalArgumentAt($index, $methodExpression);

        if ($argumentExpression === null) {
            return $default;
        }

        return $argumentExpression->simplifyToValue($this->evaluationContext);
    }

    final protected function getArgumentAt($index, O\MethodCallExpression $methodExpression)
    {
        $argumentExpression = $this->getOptionalArgumentAt($index, $methodExpression);

        if ($argumentExpression === null) {
            throw new \Pinq\PinqException(
                    'Could not get argument at index %d of method %s: argument not supplied',
                    $index,
                    $methodExpression->getName()->compileDebug());
        }

        return $argumentExpression;
    }

    final protected function getOptionalArgumentAt($index, O\MethodCallExpression $methodExpression)
    {
        $argumentExpressions = $methodExpression->getArguments();

        return isset($argumentExpressions[$index]) ? $argumentExpressions[$index] : null;
    }

    final protected function getMethodName(O\MethodCallExpression $methodExpression)
    {
        return $this->getValue(
                $methodExpression->getName(),
                'Cannot get method name: must be a constant value, %s given'
        );
    }

    final protected function getValue(
            O\Expression $expression,
            $invalidMessageFormat = 'Could not get values from type %s'
    ) {
        if (!($expression instanceof O\ValueExpression)) {
            throw new \Pinq\PinqException(
                    $invalidMessageFormat,
                    get_class($expression));
        }

        return $expression->getValue();
    }

    /**
     * @param O\MethodCallExpression $methodExpression
     *
     * @return O\MethodCallExpression
     * @throws \Pinq\PinqException
     */
    final protected function getSourceMethodCall(O\MethodCallExpression $methodExpression)
    {
        $sourceExpression = $methodExpression->getValue();

        if (!($sourceExpression instanceof O\MethodCallExpression)) {
            throw new \Pinq\PinqException(
                    'Cannot get source method call expression: source is not a method call, %s given',
                    get_class($methodExpression));
        }

        return $sourceExpression;
    }
}