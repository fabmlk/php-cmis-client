<?php

namespace Dkd\PhpCmis\Cache;

use Dkd\PhpCmis\CmisObject\CmisObjectInterface;
use Dkd\PhpCmis\DataObjects\AbstractCmisObject;
use Dkd\PhpCmis\PropertyIds;
use Dkd\PhpCmis\Session;
use Dkd\PhpCmis\SessionParameter;
use Dkd\PhpCmis\SessionParameterDefaults;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-impl/src/main/java/org/apache/chemistry/opencmis/client/runtime/cache/CacheImpl.java
 *
 * Delegates most of the implementation to an instance of PSR-6 CacheItemPoolInterface.
 * With respect to the Java implementation, the cache will store 2 kinds of items:
 *    - the objects
 *    - the paths to an already cached object
 *
 * As the amount of information associated with an object depends on the operation context used when it was queried
 * (with acls?, with allowable actions?, with policies ?, etc...), we have to cache each of its variants also.
 * To be able to remove an object and all of its variants by its id from the cache, they must all be linked somehow.
 * A simple solution for this, without making use of non-PSR6 features like tagging, is to store a pure PHP associative
 * array directly into the cache, where each key represents a variant (see OperationContext::getCacheKey()).
 * The cache key itself will remain the object ID.
 * As with Java, paths are linked to an object by storing the path itself as key and the object ID as value.
 * The downside is that the whole array is unserialized for each retrieval.
 * This means all objects variants stored under an object ID are unserialized together.
 * TODO: Check if this is incurs a significant cost on performance or memory in practice.
 *
 * To make room for a possible hierarchical cache implementation from a PSR6 pool, all the caches have their
 * keys prefixed by the repository ID (which would represent the root level), separated by '|' and a namespace
 * to represent cache-specific keys (which could represent the cache levels).
 * For an example of a PSR6 hierarchical implementation, see http://www.php-cache.com/en/latest/hierarchy/.
 *
 * Clearing the whole cache is a noop by default to avoid clearing a shared cache that would be used to store extra
 * data. If clearing is to be supported, the client should extend this class and override the method.
 *
 * As with other cache implementations, this differs from Java by the absence of hard limit on the number of
 * items to store. Cache size should be managed client-side by setting appropriate TTL values.
 */
class Cache implements CacheInterface
{
    /**
     * @var CacheItemPoolInterface|null
     */
    private $pool;

    /**
     * @var int
     */
    private $cacheTtl;

    /**
     * @var int
     */
    private $pathToIdTtl;

    /**
     * @var Session
     */
    private $session;

    /**
     * Cache constructor.
     *
     * @param CacheItemPoolInterface $pool PSR-6 pool
     */
    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(Session $session, array $parameters = [])
    {
        $this->cacheTtl = $parameters[SessionParameter::CACHE_TTL_OBJECTS] ?? SessionParameterDefaults::CACHE_TTL_OBJECTS;
        $this->pathToIdTtl = $parameters[SessionParameter::CACHE_TTL_PATHTOID] ?? SessionParameterDefaults::CACHE_TTL_PATHTOID;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function containsId($objectId, $cacheKey)
    {
        return $this->pool->hasItem($this->generateKey($objectId));
    }

    /**
     * {@inheritdoc}
     */
    public function containsPath($path, $cacheKey)
    {
        return $this->pool->hasItem($this->generateKey($path));
    }

    /**
     * {@inheritdoc}
     */
    public function put(CmisObjectInterface $object, $cacheKey)
    {
        if(!$object || null === $cacheKey) {
            return;
        }
        $id = $object->getId();
        if (!is_string($id) || $id === '') {
            return;
        }

        try {
            $itemObject = $this->pool->getItem(
                $this->generateKey($id)
            );
            $cacheKeyMap = $itemObject->get();
        } catch (\Exception $e) {
            // failed unserialization ? discard this map
            $cacheKeyMap = [];
        }

        if (false === $itemObject->isHit()) {
            $cacheKeyMap = [];
        }

        $cacheKeyMap[$cacheKey] = $object;

        // put into cache
        $itemObject->set($cacheKeyMap);
        $itemObject->expiresAfter($this->cacheTtl);
        try {
            $this->pool->save($itemObject);
        } catch (\Exception $e) {
            // failed serialization ? too bad...
            return;
        }

        // folders may have a path, use it!
        $path = ($object->getPropertyValue(PropertyIds::PATH) ?? null);
        if (is_string($path) && $path !== '') {
            $itemPath = $this->pool->getItem($this->generateKey($path));
            $itemPath->set($id);
            $itemPath->expiresAfter($this->pathToIdTtl);
            $this->pool->save($itemPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function putPath($path, CmisObjectInterface $object, $cacheKey)
    {
        if (!is_string($path) || $path === '') {
            return;
        }

        $this->put($object, $cacheKey);
        $id = $object->getId();

        if (is_string($id) && $id !== '' && null !== $cacheKey) {
            $itemPath = $this->pool->getItem($this->generateKey($path));
            $itemPath->set($object->getId());
            $itemPath->expiresAfter($this->pathToIdTtl);
            $this->pool->save($itemPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getById($objectId, $cacheKey)
    {
        if (!$this->containsId($objectId, $cacheKey)) {
            return null;
        }

        try {
            $cacheKeyMap = $this->pool->getItem($this->generateKey($objectId))->get();
        } catch (\Exception $e) {
            return null;
        }

        /** @var $object AbstractCmisObject */
        if (null !== $object = ($cacheKeyMap[$cacheKey] ?? null)) {
            // inject session
            $object->refreshSession($this->session);
        }

        return $object ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getByPath($path, $cacheKey)
    {
        if (!$this->containsPath($path, $cacheKey)) {
            return null;
        }

        $objectId = $this->pool->getItem($this->generateKey($path))->get();
        if (null === $object = $this->getById($objectId, $cacheKey)) {
            // Java implementation does not remove the path from the cache here
            // because this is performed by *byPath() methods in the Session.
            // As those methods are not implemented yet, we remove the path
            // as soon as there is not associated object
            $this->removePath($path);
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdByPath($path)
    {
        return $this->pool->getItem($this->generateKey($path))->get();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($objectId)
    {
        $this->pool->deleteItem($this->generateKey($objectId));
    }

    /**
     * {@inheritdoc}
     */
    public function removePath($path)
    {
        $this->pool->deleteItem($this->generateKey($path));
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        // NO OP
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheSize()
    {
        // never used
        return \PHP_INT_MAX;
    }

    /**
     * @param $objectIdOrPath
     * @return string
     */
    protected function generateKey($objectIdOrPath)
    {
        // oc = object cache
        return $this->session->getRepositoryId() . '|oc|' . $this->escapeKey($objectIdOrPath);
    }

    /**
     * PSR6 safe range for a key is: [A-Za-z0-9._]
     * PSR6 reserved chars for a key are: {}()/\@:
     * This makes sure reserved chars will not appear
     * but does not ensure safe range.
     *
     * @param $key
     * @return string
     */
    private function escapeKey($key)
    {
        return rawurlencode($key);
    }
}