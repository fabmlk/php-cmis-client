<?php

namespace Dkd\PhpCmis\Bindings;

use Dkd\PhpCmis\Data\RepositoryInfoInterface;
use Dkd\PhpCmis\SessionParameter;
use Dkd\PhpCmis\SessionParameterDefaults;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-bindings/src/main/java/org/apache/chemistry/opencmis/client/bindings/impl/RepositoryInfoCache.java
 *
 * Delegates all of the implementation to an instance of PSR-6 CacheItemPoolInterface.
 * The cache key is the repository ID and the value is an instance of RepositoryInfoInterface.
 *
 * To make room for a possible hierarchical cache implementation from a PSR6 pool, all the caches have their
 * keys prefixed by the repository ID. This cache does not have sub-key prefixes as the others as it would
 * represent the root level.
 * For an example of a PSR6 hierarchical implementation, see http://www.php-cache.com/en/latest/hierarchy/.
 *
 * As with other cache implementations, this differs from Java by the absence of hard limit on the number of
 * items to store. Cache size should be managed client-side by setting appropriate TTL values.
 */
class RepositoryInfoCache
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
     * RepositoryInfoCache constructor.
     *
     * @param BindingSessionInterface $session the session object
     * @param CacheItemPoolInterface  $pool PSR-6 pool
     */
    public function __construct(BindingSessionInterface $session, CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
        $this->cacheTtl = $parameters[SessionParameter::CACHE_TTL_REPOSITORIES] ?? SessionParameterDefaults::CACHE_TTL_REPOSITORIES;
    }

    /**
     * Adds a repository info object to the cache.
     *
     * @param RepositoryInfoInterface $repositoryInfo the repository info object
     */
    public function put(RepositoryInfoInterface $repositoryInfo)
    {
        $item = $this->pool->getItem($repositoryInfo->getId());
        $item->expiresAfter($this->cacheTtl);
        $item->set($repositoryInfo);
        $this->pool->save($item);
    }

    /**
     * Retrieves a repository info object from the cache.
     *
     * @param string $repositoryId the repository id
     *
     * @return RepositoryInfoInterface the repository info object or <code>null</code> if the object is
     *                                 not in the cache
     */
    public function get($repositoryId)
    {
        return $this->pool->getItem($repositoryId)->get();
    }

    /**
     * Removes a repository info object from the cache.
     *
     * @param string $repositoryId the repository id
     */
    public function remove($repositoryId)
    {
        $this->pool->deleteItem($repositoryId);
    }
}