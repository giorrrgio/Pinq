<?php

namespace Pinq\Parsing;

use Pinq\Expressions as O;

/**
 * Interface containing the reflection data of a function.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
interface IFunctionLocation
{
    /**
     * Gets the file path where the function was declared.
     *
     * @return string
     */
    public function getFilePath();

    /**
     * Gets the start line of the function.
     *
     * @return int
     */
    public function getStartLine();

    /**
     * Gets the end line of the function.
     *
     * @return int
     */
    public function getEndLine();

    /**
     * Gets a hash of the function location.
     *
     * @return string
     */
    public function getHash();
}
