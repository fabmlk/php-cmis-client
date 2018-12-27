<?php

namespace Dkd\PhpCmis\Bindings;

use Dkd\PhpCmis\Definitions\TypeDefinitionInterface;

/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-bindings/src/main/java/org/apache/chemistry/opencmis/client/bindings/cache/TypeDefinitionCache.java
 */
interface TypeDefinitionCacheInterface
{
    /**
     * Initializes the cache.
     */
    public function initialize(BindingSessionInterface $session);

    /**
     * Adds a type definition object to the cache.
     *
     * @param string $repositoryId the repository id
     * @param string $typeDefinition the type definition object
     */
    public function put($repositoryId, TypeDefinitionInterface $typeDefinition);

    /**
     * Retrieves a type definition object from the cache.
     *
     * @param string $repositoryId the repository id
     * @param string $typeId the type id
     *
     * @return TypeDefinitionInterface the type definition object or <code>null</code> if the object is
     *                                 not in the cache
     */
    public function get($repositoryId, $typeId);

    /**
     * Removes a type definition object from the cache.
     *
     * @param string      $repositoryId the repository id
     * @param string|null $typeId the type id
     */
    public function remove($repositoryId, $typeId = null);

    /**
     * Removes all cache entries.
     */
    public function removeAll();
}