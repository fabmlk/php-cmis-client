<?php

namespace Dkd\PhpCmis\Cache;

use Dkd\PhpCmis\CmisObject\CmisObjectInterface;
use Dkd\PhpCmis\Session;

/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-impl/src/main/java/org/apache/chemistry/opencmis/client/runtime/cache/CacheImpl.java
 */
interface CacheInterface
{
    public function initialize(Session $session, array $parameters);

    public function containsId($objectId, $cacheKey);

    public function containsPath($path, $cacheKey);

    public function put(CmisObjectInterface $object, $cacheKey);

    public function putPath($path, CmisObjectInterface $object, $cacheKey);

    public function getById($objectId, $cacheKey);

    public function getByPath($path, $cacheKey);

    public function getObjectIdByPath($path);

    public function remove($objectId);

    public function removePath($path);

    public function clear();

    public function getCacheSize();
}