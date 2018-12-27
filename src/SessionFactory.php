<?php
namespace Dkd\PhpCmis;

/*
 * This file is part of php-cmis-client.
 *
 * (c) Sascha Egerer <sascha.egerer@dkd.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Dkd\PhpCmis\Bindings\CmisBindingsHelper;
use Dkd\PhpCmis\Bindings\TypeDefinitionCacheInterface;
use Dkd\PhpCmis\Cache\CacheInterface;

/**
 * Class SessionFactory
 */
class SessionFactory implements SessionFactoryInterface
{
    /**
     * @param array                             $parameters
     * @param ObjectFactoryInterface|null       $objectFactory
     * @param CacheInterface|null               $cache
     * @param TypeDefinitionCacheInterface|null $typeDefinitionCache
     * @return Session
     */
    public function createSession(
        array $parameters,
        ObjectFactoryInterface $objectFactory = null,
        CacheInterface $cache = null,
        TypeDefinitionCacheInterface $typeDefinitionCache = null
    ) {
        $session = new Session($parameters, $objectFactory, $cache, $typeDefinitionCache);
        return $session;
    }

    /**
     * @param array $parameters
     * @param ObjectFactoryInterface|null $objectFactory
     * @param CacheInterface|null $cache
     * @param TypeDefinitionCacheInterface|null $typeDefinitionCache
     * @return Data\RepositoryInfoInterface[]
     */
    public function getRepositories(
        array $parameters,
        ObjectFactoryInterface $objectFactory = null,
        CacheInterface $cache = null,
        TypeDefinitionCacheInterface $typeDefinitionCache = null
    ) {
        $cmisBindingsHelper = new CmisBindingsHelper();
        $binding = $cmisBindingsHelper->createBinding(
            $parameters,
            $typeDefinitionCache
        );

        return $binding->getRepositoryService()->getRepositoryInfos();
    }
}
