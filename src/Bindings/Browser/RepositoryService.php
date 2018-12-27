<?php
namespace Dkd\PhpCmis\Bindings\Browser;

/*
 * This file is part of php-cmis-client.
 *
 * (c) Sascha Egerer <sascha.egerer@dkd.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Dkd\PhpCmis\Bindings\TypeDefinitionCacheInterface;
use Dkd\PhpCmis\Constants;
use Dkd\PhpCmis\Data\ExtensionDataInterface;
use Dkd\PhpCmis\Data\RepositoryInfoInterface;
use Dkd\PhpCmis\Definitions\TypeDefinitionContainerInterface;
use Dkd\PhpCmis\Definitions\TypeDefinitionInterface;
use Dkd\PhpCmis\Definitions\TypeDefinitionListInterface;
use Dkd\PhpCmis\Exception\CmisObjectNotFoundException;
use Dkd\PhpCmis\RepositoryServiceInterface;

/**
 * Repository Service Browser Binding client.
 */
class RepositoryService extends AbstractBrowserBindingService implements RepositoryServiceInterface
{
    /**
     * Creates a new type.
     *
     * @param string $repositoryId The identifier for the repository.
     * @param TypeDefinitionInterface $type A fully populated type definition including all new property definitions.
     * @param ExtensionDataInterface|null $extension
     * @return TypeDefinitionInterface
     */
    public function createType($repositoryId, TypeDefinitionInterface $type, ExtensionDataInterface $extension = null)
    {
        $url = $this->getRepositoryUrl($repositoryId);

        $url->getQuery()->modify(
            [
                Constants::CONTROL_CMISACTION => Constants::CMISACTION_CREATE_TYPE,
                Constants::CONTROL_TYPE => $this->getJsonConverter()->convertFromTypeDefinition($type)
            ]
        );

        $typeDefinition = $this->getJsonConverter()->convertTypeDefinition($this->postJson($url));

        // add the type to cache
        $this->cmisBindingsHelper->getTypeDefinitionCache($this->session)->put($repositoryId, $typeDefinition);

        return $typeDefinition;
    }

    /**
     * Deletes a type.
     *
     * @param string $repositoryId The identifier for the repository.
     * @param string $typeId The typeId of an object-type specified in the repository.
     * @param ExtensionDataInterface|null $extension
     */
    public function deleteType($repositoryId, $typeId, ExtensionDataInterface $extension = null)
    {
        $url = $this->getRepositoryUrl($repositoryId);

        $url->getQuery()->modify(
            [
                Constants::CONTROL_CMISACTION => Constants::CMISACTION_DELETE_TYPE,
                Constants::CONTROL_TYPE_ID => $typeId
            ]
        );

        $this->post($url);

        // remove the type from cache
        $this->cmisBindingsHelper->getTypeDefinitionCache($this->session)->remove($repositoryId, $typeId);
    }

    /**
     * Returns information about the CMIS repository, the optional capabilities it
     * supports and its access control information if applicable.
     *
     * @param string $repositoryId The identifier for the repository.
     * @param ExtensionDataInterface|null $extension
     * @throws CmisObjectNotFoundException
     * @return RepositoryInfoInterface
     */
    public function getRepositoryInfo($repositoryId, ExtensionDataInterface $extension = null)
    {
        $hasExtension = $extension && !empty($extension->getExtensions());
        $cache = $this->cmisBindingsHelper->getRepositoryInfoCache($this->session);

        if (!$hasExtension) {
            if (null !== $repositoryInfo = $cache->get($repositoryId)) {
                return $repositoryInfo;
            }
        }

        foreach ($this->getRepositoriesInternal($repositoryId) as $repositoryInfo) {
            if ($repositoryInfo->getId() === $repositoryId) {
                if (!$hasExtension) {
                    $cache->put($repositoryInfo);
                }
                return $repositoryInfo;
            }
        }

        throw new CmisObjectNotFoundException(sprintf('Repository "%s" not found!', $repositoryId));
    }

    /**
     * Returns a list of CMIS repository information available from this CMIS service endpoint.
     * In contrast to the CMIS specification this method returns repository infos not only repository ids.
     *
     * @param ExtensionDataInterface|null $extension
     * @return RepositoryInfoInterface[]
     */
    public function getRepositoryInfos(ExtensionDataInterface $extension = null)
    {
        $hasExtension = $extension && !empty($extension->getExtensions());
        $repositoryInfos = $this->getRepositoriesInternal();

        if (!$hasExtension && $repositoryInfos) {
            $cache = $this->cmisBindingsHelper->getRepositoryInfoCache($this->session);
            foreach($repositoryInfos as $repositoryInfo) {
                $cache->put($repositoryInfo);
            }
        }

        return $repositoryInfos;
    }

    /**
     * Returns the list of object types defined for the repository that are children of the specified type.
     *
     * @param string $repositoryId the identifier for the repository
     * @param string|null $typeId the typeId of an object type specified in the repository
     *      (if not specified the repository MUST return all base object types)
     * @param boolean $includePropertyDefinitions if <code>true</code> the repository MUST return the property
     *      definitions for each object type returned (default is <code>false</code>)
     * @param integer|null $maxItems the maximum number of items to return in a response
     *      (default is repository specific)
     * @param integer $skipCount number of potential results that the repository MUST skip/page over before
     *      returning any results (default is 0)
     * @param ExtensionDataInterface|null $extension
     * @return TypeDefinitionListInterface
     */
    public function getTypeChildren(
        $repositoryId,
        $typeId = null,
        $includePropertyDefinitions = false,
        $maxItems = null,
        $skipCount = 0,
        ExtensionDataInterface $extension = null
    ) {
        $hasExtension = $extension && !empty($extension->getExtensions());
        $url = $this->getRepositoryUrl($repositoryId, Constants::SELECTOR_TYPE_CHILDREN);
        $url->getQuery()->modify(
            [
                Constants::PARAM_PROPERTY_DEFINITIONS => $includePropertyDefinitions ? 'true' : 'false',
                Constants::PARAM_SKIP_COUNT => $skipCount,
                Constants::PARAM_DATETIME_FORMAT => (string) $this->getDateTimeFormat()
            ]
        );

        if ($typeId !== null) {
            $url->getQuery()->modify([Constants::PARAM_TYPE_ID => $typeId]);
        }

        if ($maxItems !== null) {
            $url->getQuery()->modify([Constants::PARAM_MAX_ITEMS => $maxItems]);
        }

        $responseData = (array) $this->readJson($url);

        $typeDefinitionList = $this->getJsonConverter()->convertTypeChildren($responseData);

        if (!$hasExtension && $includePropertyDefinitions && $typeDefinitionList) {
            $cache = $this->cmisBindingsHelper->getTypeDefinitionCache($this->session);
            foreach($typeDefinitionList->getList() as $typeDefinition) {
                $cache->put($repositoryId, $typeDefinition);
            }
        }

        return $typeDefinitionList;
    }

    /**
     * Gets the definition of the specified object type.
     *
     * @param string $repositoryId the identifier for the repository
     * @param string $typeId he type definition
     * @param ExtensionDataInterface|null $extension
     * @param boolean $useCache
     * @return TypeDefinitionInterface|null the newly created type
     */
    public function getTypeDefinition(
        $repositoryId,
        $typeId,
        ExtensionDataInterface $extension = null,
        $useCache = true
    ) {
        $cache = $this->cmisBindingsHelper->getTypeDefinitionCache($this->getSession());

        // if the cache should be used and the extension is not set, check the cache first
        $hasExtension = $extension && !empty($extension->getExtensions());
        if ($useCache === true && !$hasExtension) {
            if (null !== $typeDefinition = $cache->get($repositoryId, $typeId)) {
                return $typeDefinition;
            }
        }
        $typeDefinition = $this->getTypeDefinitionInternal($repositoryId, $typeId);

        if ($useCache === true && $typeDefinition) {
            $cache->put($repositoryId, $typeDefinition);
        }

        return $typeDefinition;
    }

    /**
     * Returns the set of descendant object type defined for the repository under the specified type.
     *
     * @param string $repositoryId repositoryId - the identifier for the repository
     * @param string|null $typeId the typeId of an object type specified in the repository
     * (if not specified the repository MUST return all types and MUST ignore the value of the depth parameter)
     * @param integer|null $depth the number of levels of depth in the type hierarchy from which
     * to return results (default is repository specific)
     * @param boolean $includePropertyDefinitions if <code>true</code> the repository MUST return the property
     * definitions for each object type returned (default is <code>false</code>)
     * @param ExtensionDataInterface|null $extension
     * @return TypeDefinitionContainerInterface[]
     */
    public function getTypeDescendants(
        $repositoryId,
        $typeId = null,
        $depth = null,
        $includePropertyDefinitions = false,
        ExtensionDataInterface $extension = null
    ) {
        $hasExtension = $extension && !empty($extension->getExtensions());
        $url = $this->getRepositoryUrl($repositoryId, Constants::SELECTOR_TYPE_DESCENDANTS);
        $url->getQuery()->modify(
            [
                Constants::PARAM_PROPERTY_DEFINITIONS => $includePropertyDefinitions ? 'true' : 'false',
                Constants::PARAM_DATETIME_FORMAT => (string) $this->getDateTimeFormat()
            ]
        );

        if ($typeId !== null) {
            $url->getQuery()->modify([Constants::PARAM_TYPE_ID => $typeId]);
        }

        if ($depth !== null) {
            $url->getQuery()->modify([Constants::PARAM_DEPTH => $depth]);
        }

        $responseData = (array) $this->readJson($url);

        $typeDefinitionContainers = $this->getJsonConverter()->convertTypeDescendants($responseData);

        if (!$hasExtension && $includePropertyDefinitions && $typeDefinitionContainers) {
            $cache = $this->cmisBindingsHelper->getTypeDefinitionCache($this->session);
            $this->addToTypeCache($cache, $repositoryId, $typeDefinitionContainers);
        }

        return $typeDefinitionContainers;
    }

    /**
     * Updates a type.
     *
     * @param string $repositoryId the identifier for the repository
     * @param TypeDefinitionInterface $type the type definition
     * @param ExtensionDataInterface|null $extension
     * @return TypeDefinitionInterface the updated type
     */
    public function updateType($repositoryId, TypeDefinitionInterface $type, ExtensionDataInterface $extension = null)
    {
        $url = $this->getRepositoryUrl($repositoryId);

        $url->getQuery()->modify(
            [
                Constants::CONTROL_CMISACTION => Constants::CMISACTION_UPDATE_TYPE,
                Constants::CONTROL_TYPE => json_encode($this->getJsonConverter()->convertFromTypeDefinition($type))
            ]
        );

        $typeDefinition = $this->getJsonConverter()->convertTypeDefinition($this->postJson($url));

        // update the type in cache
        $this->cmisBindingsHelper->getTypeDefinitionCache($this->session)->put($repositoryId, $typeDefinition);

        return $typeDefinition;
    }

    /**
     * @param TypeDefinitionCacheInterface $cache
     * @param                              $repositoryId
     * @param array                        $containers
     */
    private function addToTypeCache(TypeDefinitionCacheInterface $cache, $repositoryId, array $containers)
    {
        foreach($containers as $container) {
            $cache->put($repositoryId, $container->getTypeDefinition());
            $this->addToTypeCache($cache, $repositoryId, $container->getChildren());
        }
    }
}
