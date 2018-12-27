<?php

namespace Dkd\PhpCmis;

/**
 * Temporary type cache used for one call.
 */
interface TypeCacheInterface
{
        /**
     * Gets the type definition by type ID.
     */
    public function getTypeDefinition($typeId);

    /**
     * Reloads the type definition by type ID.
     */
    public function reloadTypeDefinition($typeId);

    /**
     * Gets the type definition of an object.
     */
    public function getTypeDefinitionForObject($objectId);

    /**
     * Finds the property definition in all cached types.
     */
    public function getPropertyDefinition($propId);
}