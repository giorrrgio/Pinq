<?php

namespace Pinq;

/**
 * Base exception for all exception in the Pinq library
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class PinqException extends \Exception
{
    /**
     * @param string $messageFormat
     * @param mixed  $_ The values to interpolate the message with
     */
    public function __construct($messageFormat = '', $_ = null)
    {
        if (func_num_args() === 1) {
            $message = $messageFormat;
        } else {
            $message = call_user_func_array('sprintf', func_get_args());
        }

        parent::__construct($message, null, null);
    }

    public static function construct(array $parameters)
    {
        if ($parameters === 1) {
            $messageFormat = array_shift($messageFormat);
            $message       = $messageFormat;
        } else {
            $message = call_user_func_array('sprintf', $parameters);
        }

        return new static($message);
    }

    public static function invalidIterable($method, $value)
    {
        return new self(
                'Invalid argument for %s: expecting array or \\Traversable, %s given',
                $method,
                \Pinq\Utilities::getTypeOrClass($value));
    }

    public static function notSupported($method)
    {
        return new self('Invalid call to %s: Method is not supported', $method);
    }
}
