<?php

namespace Dkd\PhpCmis\Bindings\Browser;


use Dkd\PhpCmis\Bindings\CmisBindingsHelper;
use Dkd\PhpCmis\TypeCacheInterface;

/**
 * Class ClientTypeCache.
 * TODO: this cache is actually never read from right now.
 *       It should be used by non-implemented JsonConverter::convertFrom*() methods
 *       and JsonConverter::convertSuccintProperties().
 */
class ClientTypeCache implements TypeCacheInterface
{
    private $repositoryId;
    /**
     * @var AbstractBrowserBindingService
     */
    private $service;

    /**
     * ClientTypeCache constructor.
     * @param                               $repositoryId
     * @param AbstractBrowserBindingService $service
     */
    public function __construct($repositoryId, AbstractBrowserBindingService $service)
    {
        $this->repositoryId = $repositoryId;
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeDefinition($typeId)
    {
        $cache = $this->service->getSession()->get(CmisBindingsHelper::TYPE_DEFINITION_CACHE);
        if (null === $type = $cache->get($this->repositoryId, $typeId)) {
            if (null !== $type = $this->service->getTypeDefinitionInternal($this->repositoryId, $typeId)) {
                $cache->put($this->repositoryId, $type);
            }
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function reloadTypeDefinition($typeId)
    {
        $cache = $this->service->getSession()->get(CmisBindingsHelper::TYPE_DEFINITION_CACHE);
        if (null !== $type = $this->service->getTypeDefinitionInternal($this->repositoryId, $typeId)) {
            $cache->put($this->repositoryId, $type);
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeDefinitionForObject($objectId)
    {
        // not used
        assert(false);
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyDefinition($propId)
    {
        // not used
        assert(false);
        return null;
    }
}