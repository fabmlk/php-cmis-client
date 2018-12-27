<?php

namespace Dkd\PhpCmis\Test\Integration;


use Dkd\PhpCmis\Bindings\CmisBindingsHelper;
use Dkd\PhpCmis\Test\Unit\FixtureHelperTrait;

/**
 * Class RepositoryInfoCacheTest.
 */
class RepositoryInfoCacheTest extends \PHPUnit_Framework_TestCase
{
    use FixtureHelperTrait;
    use MockClientTrait;

    /**
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    protected static $sharedPool;

    /**
     * We will keep the PSR6 pool instance across tests
     */
    public static function setUpBeforeClass()
    {
        self::resetSharedPool();
    }

    protected static function resetSharedPool()
    {
        self::$sharedPool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
    }

    public function testRepositoryInfoIsNotQueriedAfterFirstSessionCreation()
    {
        $bindingHelper = new CmisBindingsHelper();
        $this->assertCount(0, $this->guzzleContainer);

        $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json'
        ], null, self::$sharedPool, null, $bindingHelper);

        $this->assertCount(1, $this->guzzleContainer);

        $this->createSessionWithMockResponses([], null, self::$sharedPool, null, $bindingHelper);

        $this->assertCount(1, $this->guzzleContainer);
    }
}