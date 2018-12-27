<?php

namespace Dkd\PhpCmis\Test\Integration;

use Dkd\PhpCmis\Test\Unit\FixtureHelperTrait;

/**
 * Class TypeDefinitionCacheTest.
 */
class TypeDefinitionCacheTest extends \PHPUnit_Framework_TestCase
{
    use FixtureHelperTrait;
    use MockClientTrait;

    public function testTypeDefinitionIsNotQueriedAfterItsFirstSaveToCache()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();

        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisfolder.json',
            'Cmis/v1.1/Cache/createFolder.json',
            'Cmis/v1.1/Cache/createFolder.json',
            'Cmis/v1.1/Cache/createFolder.json',
        ], null, null, $pool);

        SessionHelperFactory::createFolder($session);
        SessionHelperFactory::createFolder($session);
        SessionHelperFactory::createFolder($session);

        $this->assertCount(5, $this->guzzleContainer); // makes sure all mock responses got fetched
    }
}