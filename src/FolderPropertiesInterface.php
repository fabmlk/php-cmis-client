<?php
namespace Dkd\PhpCmis;

/**
 * Accessors to CMIS folder properties.
 */
interface FolderPropertiesInterface
{
    /**
     * Returns the list of the allowed object types in this folder (CMIS property cmis:allowedChildObjectTypeIds).
     * If the list is empty or null all object types are allowed.
     *
     * @return ObjectTypeInterface[]|null the property value or null if the property hasn't been requested,
     * hasn't been provided by the repository, or the property value isn't set
     */
    public function getAllowedChildObjectTypes();

    /**
     * Returns the parent id or null if the folder is the root folder (CMIS property cmis:parentId).
     *
     * @return string|null the property value or null if the property hasn't been requested, hasn't been provided
     * by the repository, or the folder is the root folder
     */
    public function getParentId();
}
