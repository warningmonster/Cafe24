<?php

/*
 * PHP script for downloading videos from youtube
 * Copyright (C) 2012-2018  John Eckman
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, see <http://www.gnu.org/licenses/>.
 */

namespace YoutubeDownloader\Tests\Fixture\Cache;

use Psr\SimpleCache\CacheInterface;
use YoutubeDownloader\Cache\Cache;
use YoutubeDownloader\Cache\CacheException;
use YoutubeDownloader\Cache\FileCache;
use YoutubeDownloader\Cache\InvalidArgumentException;

/**
 * A simple PSR-16 cache as a compatibility proof for Cache
 */
class Psr16CacheAdapter implements CacheInterface, Cache
{
    /**
     * @var YoutubeDownloader\Cache\FileCache
     */
    private $cache;

    /**
     * @param YoutubeDownloader\Cache\FileCache $cache
     */
    public function __construct(FileCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key     the unique key of this item in the cache
     * @param mixed  $default default value to return if the key does not exist
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if the $key string is not a legal value
     *
     * @return mixed the value of the item from the cache, or $default in case of cache miss
     */
    public function get($key, $default = null)
    {
        try {
            return $this->cache->get($key, $default);
        } catch (InvalidArgumentException $e) {
            throw new Psr16InvalidArgumentException($e->getMessage(), $e->getCode, $e);
        } catch (CacheException $e) {
            throw new Psr16CacheException($e->getMessage(), $e->getCode, $e);
        }
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                $key   the key of the item to store
     * @param mixed                 $value the value of the item to store, must be serializable
     * @param null|int|DateInterval $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                     the driver supports TTL then the library may set a default value
     *                                     for it or let the driver take care of that.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if the $key string is not a legal value
     *
     * @return bool true on success and false on failure
     */
    public function set($key, $value, $ttl = null)
    {
        try {
            return $this->cache->set($key, $value, $ttl);
        } catch (InvalidArgumentException $e) {
            throw new Psr16InvalidArgumentException($e->getMessage(), $e->getCode, $e);
        } catch (CacheException $e) {
            throw new Psr16CacheException($e->getMessage(), $e->getCode, $e);
        }
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key the unique cache key of the item to delete
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if the $key string is not a legal value
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     */
    public function delete($key)
    {
        try {
            return $this->cache->delete($key);
        } catch (InvalidArgumentException $e) {
            throw new Psr16InvalidArgumentException($e->getMessage(), $e->getCode, $e);
        } catch (CacheException $e) {
            throw new Psr16CacheException($e->getMessage(), $e->getCode, $e);
        }
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool true on success and false on failure
     */
    public function clear()
    {
        // Not implemented
        return true;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    a list of keys that can obtained in a single operation
     * @param mixed    $default default value to return for keys that do not exist
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if $keys is neither an array nor a Traversable,
     *                                                   or if any of the $keys are not a legal value
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     */
    public function getMultiple($keys, $default = null)
    {
        $return = [];

        if (! is_array($keys) and ! $keys instanceof \Traversable) {
            throw new Psr16InvalidArgumentException(
                '$keys is neither an array nor Traversable'
            );
        }

        foreach ($keys as $key => $value) {
            $return[$key] = $this->get($key, $default);
        }

        return $return;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable              $values a list of key => value pairs for a multiple-set operation
     * @param null|int|DateInterval $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if $values is neither an array nor a Traversable,
     *                                                   or if any of the $values are not a legal value
     *
     * @return bool true on success and false on failure
     */
    public function setMultiple($values, $ttl = null)
    {
        if (! is_array($values) and ! $values instanceof \Traversable) {
            throw new Psr16InvalidArgumentException(
                '$keys is neither an array nor Traversable'
            );
        }

        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return $true;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys a list of string-based keys to be deleted
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if $keys is neither an array nor a Traversable,
     *                                                   or if any of the $keys are not a legal value
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     */
    public function deleteMultiple($keys)
    {
        if (! is_array($values) and ! $values instanceof \Traversable) {
            throw new Psr16InvalidArgumentException(
                '$keys is neither an array nor Traversable'
            );
        }

        foreach ($values as $key => $value) {
            $this->delete($key);
        }

        return $true;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key the cache item key
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if the $key string is not a legal value
     *
     * @return bool
     */
    public function has($key)
    {
        // @see https://stackoverflow.com/a/4356295
        $randomString = function ($length = 10) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }

            return $randomString;
        };

        $default = $randomString(30);

        $value = $this->get($key, $default);

        return ($value !== $default);
    }
}
