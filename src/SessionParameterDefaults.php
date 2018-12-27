<?php

declare(strict_types=1);

namespace Dkd\PhpCmis;

/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-commons/chemistry-opencmis-commons-api/src/main/java/org/apache/chemistry/opencmis/commons/SessionParameterDefaults.java
 *
 * Our default implementation will rely exclusively on TTL-based expiration.
 * Thus, all count-based limit constants will remain unused.
 */
final class SessionParameterDefaults
{
    // -- our cache implementations do not have a hard limit --
//    const CACHE_SIZE_OBJECTS = 1000;
//    const CACHE_SIZE_PATHTOID = 1000;
//    const CACHE_SIZE_REPOSITORIES = 10;
//    const CACHE_SIZE_TYPES = 100;
//    const CACHE_SIZE_LINKS = 400;  // AtomPub binding only
    // -- we use TTL values instead --
    const CACHE_TTL_OBJECTS = 2 * 60 * 60;             // 2H - same as Java
    const CACHE_TTL_PATHTOID = 30 * 60;                // 30m - same as Java
    const CACHE_TTL_REPOSITORIES = 24 * 60 * 60;       // 24H - new
    const CACHE_TTL_TYPES = 4 * 60 * 60;               // 4H - new
}