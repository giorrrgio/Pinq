<?php

namespace Pinq\Expressions;

/**
 * <code>
 * (string)$I
 * </code>
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class CastExpression extends Expression
{
    /**
     * @var int
     */
    private $castType;

    /**
     * @var Expression
     */
    private $castValue;

    public function __construct($castType, Expression $castValue)
    {
        $this->castType  = $castType;
        $this->castValue = $castValue;
    }

    /**
     * @return string The cast operator
     */
    public function getCastType()
    {
        return $this->castType;
    }

    /**
     * @return Expression The expression which is cast
     */
    public function getCastValue()
    {
        return $this->castValue;
    }

    public function traverse(ExpressionWalker $walker)
    {
        return $walker->walkCast($this);
    }

    /**
     * @param int        $castType
     * @param Expression $castValue
     *
     * @return self
     */
    public function update($castType, Expression $castValue)
    {
        if ($this->castType === $castType && $this->castValue === $castValue) {
            return $this;
        }

        return new self($castType, $castValue);
    }

    protected function compileCode(&$code)
    {
        $code .= $this->castType;
        $this->castValue->compileCode($code);
    }

    public function serialize()
    {
        return serialize([$this->castType, $this->castValue]);
    }

    public function unserialize($serialized)
    {
        list($this->castType, $this->castValue) = unserialize($serialized);
    }

    public function __clone()
    {
        $this->castValue = clone $this->castValue;
    }
}
