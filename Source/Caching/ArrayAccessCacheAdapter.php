<?php

namespace Pinq\Caching;

/**
 * Adapter class for a cache that implements \ArrayAccess
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class ArrayAccessCacheAdapter extends CacheAdapter
{
    /**
     * The cache object implementing array access
     *
     * @var \ArrayAccess
     */
    private $arrayAccess;

    public function __construct(\ArrayAccess $innerCache)
    {
        $this->arrayAccess = $innerCache;
    }

    public function save($key, $value)
    {
        $this->arrayAccess[$key] = $value;
    }

    public function contains($key)
    {
        return isset($this->arrayAccess[$key]);
    }

    public function tryGet($key)
    {
        return isset($this->arrayAccess[$key]) ? $this->arrayAccess[$key] : null;
    }

    public function remove($key)
    {
        unset($this->arrayAccess[$key]);
    }

    public function clear($namespace = null)
    {
        if (method_exists($this->arrayAccess, 'clear')) {
            $this->arrayAccess->clear();
        } elseif ($this->arrayAccess instanceof \Traversable) {
            $keys = array_keys(iterator_to_array($this->arrayAccess, true));

            foreach ($keys as $key) {
                if ($namespace === null || strpos($key, $namespace) === 0) {
                    unset($this->arrayAccess[$key]);
                }
            }
        } else {
            throw new \Pinq\PinqException(
                    'Cannot clear cache %s: does not support clearing',
                    get_class($this->arrayAccess));
        }
    }
}
