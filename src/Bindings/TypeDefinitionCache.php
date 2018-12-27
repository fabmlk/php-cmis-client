<?php

namespace Dkd\PhpCmis\Bindings;

use Dkd\PhpCmis\Definitions\TypeDefinitionInterface;
use Dkd\PhpCmis\SessionParameter;
use Dkd\PhpCmis\SessionParameterDefaults;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-bindings/src/main/java/org/apache/chemistry/opencmis/client/bindings/impl/TypeDefinitionCacheImpl.java
 *
 * Delegates most of the implementation to an instance of PSR-6 CacheItemPoolInterface.
 * The cache key is a combination of the repository ID and the type definition ID.
 * The value is a an instance of TypeDefinitionInterface.
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
class TypeDefinitionCache implements TypeDefinitionCacheInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $pool;

    /**
     * @var int
     */
    private $cacheTtl;

    /**
     * TypeDefinitionCache constructor.
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
    public function initialize(BindingSessionInterface $session)
    {
        $this->cacheTtl = $session->get(SessionParameter::CACHE_TTL_TYPES) ?? SessionParameterDefaults::CACHE_TTL_TYPES;
    }

    /**
     * {@inheritdoc}
     */
    public function put($repositoryId, TypeDefinitionInterface $typeDefinition)
    {
        $item = $this->pool->getItem($this->generateKey($repositoryId, $typeDefinition->getId()));
        $item->set($typeDefinition);
        $item->expiresAfter($this->cacheTtl);
        $this->pool->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function get($repositoryId, $typeId)
    {
        return $this->pool->getItem($this->generateKey($repositoryId, $typeId))->get();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($repositoryId, $typeId = null)
    {
        $key = $this->generateKey($repositoryId, $typeId ?? '');
        if ($this->pool->hasItem($key)) {
            $this->pool->deleteItem($key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeAll()
    {
        // NO OP
    }

    /**
     * @param $repositoryId
     * @param $typeId|null
     * @return string
     */
    protected function generateKey($repositoryId, $typeId)
    {
        // replace reserved PSR6 char ':'
        // tdc = type definition cache
        return $repositoryId . '|tdc|' . str_replace(':', '.', $typeId);
    }
}