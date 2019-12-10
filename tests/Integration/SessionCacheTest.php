<?php

namespace Dkd\PhpCmis\Test\Unit\Cache;

use Dkd\PhpCmis\CmisObject\CmisObjectInterface;
use Dkd\PhpCmis\DataObjects\Document;
use Dkd\PhpCmis\DataObjects\Folder;
use Dkd\PhpCmis\Test\Integration\SessionHelperFactory;
use Dkd\PhpCmis\Test\Integration\MockClientTrait;
use Dkd\PhpCmis\Test\Unit\FixtureHelperTrait;
use Dkd\PhpCmis\Test\Unit\ReflectionHelperTrait;


/**
 * Class SessionCacheTest
 */
class SessionCacheTest extends \PHPUnit_Framework_TestCase
{
    use ReflectionHelperTrait;
    use FixtureHelperTrait;
    use MockClientTrait;

    /**
     * We will create a different session for each test,
     * so we need to remove the session references before comparing.
     *
     * @param $expected
     * @param $actual
     */
    protected function assertEqualsWithoutSession($expected, $actual)
    {
        $this->unsetProtectedPropertyObjectRecursive($expected, 'Dkd\PhpCmis\Session');
        $this->unsetProtectedPropertyObjectRecursive($actual, 'Dkd\PhpCmis\Session');

        $this->assertEquals($expected, $actual);
    }

    public function testGetObjectSavesDocumentToCacheByDefault()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisdocument.json',
            'Cmis/v1.1/Cache/createDocument.json',
            'Cmis/v1.1/Cache/getObjectDocument.json'
        ], $pool);

        $context = $session->getDefaultContext();
        $cache = $session->getCache();

        $objectId = SessionHelperFactory::createDocument($session);

        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $document = $session->getObject($objectId);
        $this->assertTrue($cache->containsId($objectId->getId(), $context->getCacheKey()));
        $this->assertCount(4, $this->guzzleContainer); // makes sure all mock responses got fetched

        return array($document, $pool);
    }

    public function testGetObjectDoesNotSaveDocumentToCacheIfDisabled()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisdocument.json',
            'Cmis/v1.1/Cache/createDocument.json',
            'Cmis/v1.1/Cache/getObjectDocument.json'
        ], $pool);

        $context = $session->getDefaultContext()->setCacheEnabled(false);
        $cache = $session->getCache();

        $objectId = SessionHelperFactory::createDocument($session);

        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $document = $session->getObject($objectId);
        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));
        $this->assertCount(4, $this->guzzleContainer); // makes sure all mock responses got fetched

        return array($document, $pool);
    }

    public function testGetObjectSavesFolderToCacheByDefault()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisfolder.json',
            'Cmis/v1.1/Cache/createFolder.json',
            'Cmis/v1.1/Cache/getObjectFolder.json'
        ], $pool);

        $context = $session->getDefaultContext();
        $cache = $session->getCache();

        $objectId = SessionHelperFactory::createFolder($session);

        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $folder = $session->getObject($objectId);
        $this->assertTrue($cache->containsId($objectId->getId(), $context->getCacheKey()));
        $this->assertCount(4, $this->guzzleContainer); // makes sure all mock responses got fetched

        return array($folder, $pool);
    }

    public function testGetObjectDoesNotSaveFolderToCacheIfDisabled()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisfolder.json',
            'Cmis/v1.1/Cache/createFolder.json',
            'Cmis/v1.1/Cache/getObjectFolder.json'
        ], $pool);

        $context = $session->getDefaultContext()->setCacheEnabled(false);
        $cache = $session->getCache();

        $objectId = SessionHelperFactory::createFolder($session);

        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $folder = $session->getObject($objectId);
        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));
        $this->assertCount(4, $this->guzzleContainer); // makes sure all mock responses got fetched

        return array($folder, $pool);
    }

    protected function assertGetObjectRetrievesObjectFromCacheByDefault(CmisObjectInterface $object, \Psr\Cache\CacheItemPoolInterface $pool)
    {
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
        ], $pool);

        $countRequestsBefore = count($this->guzzleContainer);

        $context = $session->getDefaultContext();
        $cache = $session->getCache();
        $objectId = $session->createObjectId($object->getId());

        $this->assertEqualsWithoutSession($object, $cache->getById($objectId, $context->getCacheKey()));
        $this->assertEqualsWithoutSession($object, $session->getObject($objectId));

        $countRequestsAfter = count($this->guzzleContainer);

        $this->assertEquals($countRequestsBefore, $countRequestsAfter);
        $this->assertCount(1, $this->guzzleContainer); // makes sure all mock responses got fetched
    }

    protected function assertGetObjectDoesNotRetrieveObjectFromCacheIfDisabled(CmisObjectInterface $object, \Psr\Cache\CacheItemPoolInterface $pool)
    {
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            $object instanceof Folder ? 'Cmis/v1.1/Cache/getObjectFolder.json' : 'Cmis/v1.1/Cache/getObjectDocument.json',
            $object instanceof Folder ? 'Cmis/v1.1/Cache/getTypeDefinition-cmisfolder.json' : 'Cmis/v1.1/Cache/getTypeDefinition-cmisdocument.json'
        ], $pool);

        $context = $session->getDefaultContext()->setCacheEnabled(false);
        $cache = $session->getCache();
        $objectId = $session->createObjectId($object->getId());

        $session->getObject($objectId);
        $this->assertNull($cache->getById($objectId, $context->getCacheKey()));

        $this->assertCount(3, $this->guzzleContainer);
    }

    /**
     * @depends testGetObjectSavesDocumentToCacheByDefault
     */
    public function testGetObjectRetrievesDocumentFromCacheByDefault(array $dependencies)
    {
        list($document, $pool) = $dependencies;
        $this->assertGetObjectRetrievesObjectFromCacheByDefault($document, $pool);
    }

    /**
     * @depends testGetObjectDoesNotSaveDocumentToCacheIfDisabled
     */
    public function testGetObjectDoesNotRetrieveDocumentFromCacheIfDisabled(array $dependencies)
    {
        list($document, $pool) = $dependencies;
        $this->assertGetObjectDoesNotRetrieveObjectFromCacheIfDisabled($document, $pool);
    }


    /**
     * @depends testGetObjectSavesFolderToCacheByDefault
     */
    public function testGetObjectRetrievesFolderFromCacheByDefault(array $dependencies)
    {
        list($folder, $pool) = $dependencies;
        $this->assertGetObjectRetrievesObjectFromCacheByDefault($folder, $pool);
    }

    /**
     * @depends testGetObjectDoesNotSaveFolderToCacheIfDisabled
     */
    public function testGetObjectDoesNotRetrieveFolderFromCacheIfDisabled(array $dependencies)
    {
        list($folder, $pool) = $dependencies;
        $this->assertGetObjectDoesNotRetrieveObjectFromCacheIfDisabled($folder, $pool);
    }


    public function testGetLatestDocumentVersionRetrievesObjectFromCacheByDefault()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisdocument.json',
            'Cmis/v1.1/Cache/createDocument.json',
            'Cmis/v1.1/Cache/getObjectDocument.json',
            'Cmis/v1.1/Cache/setDocumentContent.json',
        ], $pool);

        $context = $session->getDefaultContext();
        $cache = $session->getCache();

        $objectId = SessionHelperFactory::createDocument($session);
        $document = $session->getObject($objectId);

        $objectId = $document->setContentStream(
            \GuzzleHttp\Psr7\stream_for("Updated!!"),
            true,
            false
        );

        $countRequestsBefore = count($this->guzzleContainer);
        $session->getLatestDocumentVersion($objectId);

        $countRequestsAfter = count($this->guzzleContainer);
        $this->assertEquals($countRequestsBefore, $countRequestsAfter);
        $this->assertTrue($cache->containsId($objectId->getId(), $context->getCacheKey()));
        $this->assertCount(5, $this->guzzleContainer); // makes sure all mock responses got fetched
    }

    public function testGetLatestDocumentVersionDoesNotRetrieveObjectFromCacheIfDisabled()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisdocument.json',
            'Cmis/v1.1/Cache/createDocument.json',
            'Cmis/v1.1/Cache/getObjectDocument.json',
            'Cmis/v1.1/Cache/setDocumentContent.json',
            'Cmis/v1.1/Cache/getObjectDocumentVersions.json',
        ], $pool);

        $context = $session->getDefaultContext()->setCacheEnabled(false);
        $cache = $session->getCache();

        $objectId = SessionHelperFactory::createDocument($session);
        $document = $session->getObject($objectId);

        $objectId = $document->setContentStream(
            \GuzzleHttp\Psr7\stream_for("Updated!!"),
            true,
            false
        );

        $session->getLatestDocumentVersion($objectId);

        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));
        $this->assertCount(6, $this->guzzleContainer); // makes sure all mock responses got fetched
    }

    public function testGetObjectByPathSavesDocumentToCacheByDefault()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisdocument.json',
            'Cmis/v1.1/Cache/createDocument.json',
            'Cmis/v1.1/Cache/getObjectDocument.json'
        ], $pool);

        $context = $session->getDefaultContext();
        $cache = $session->getCache();

        $objectId = SessionHelperFactory::createDocument($session);

        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $document = $session->getObjectByPath('/test document');
        $this->assertTrue($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $this->assertCount(4, $this->guzzleContainer); // makes sure all mock responses got fetched

        return array($document, $pool);
    }

    public function testGetObjectByPathDoesNotSaveDocumentToCacheIfDisabled()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisdocument.json',
            'Cmis/v1.1/Cache/createDocument.json',
            'Cmis/v1.1/Cache/getObjectDocument.json'
        ], $pool);

        $context = $session->getDefaultContext()->setCacheEnabled(false);
        $cache = $session->getCache();

        $objectId = SessionHelperFactory::createDocument($session);

        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $document = $session->getObjectByPath('/test document');
        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $this->assertCount(4, $this->guzzleContainer); // makes sure all mock responses got fetched

        return array($document, $pool);
    }

    public function testGetObjectByPathSavesFolderToCacheByDefault()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisfolder.json',
            'Cmis/v1.1/Cache/createFolder.json',
            'Cmis/v1.1/Cache/getObjectFolder.json'
        ], $pool);

        $context = $session->getDefaultContext();
        $cache = $session->getCache();

        $objectId = SessionHelperFactory::createFolder($session);

        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $folder = $session->getObjectByPath('/test folder');
        $this->assertTrue($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $this->assertCount(4, $this->guzzleContainer); // makes sure all mock responses got fetched

        return array($folder, $pool);
    }

    public function testGetObjectByPathDoesNotSaveFolderToCacheIfDisabled()
    {
        $pool = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            'Cmis/v1.1/Cache/getTypeDefinition-cmisfolder.json',
            'Cmis/v1.1/Cache/createFolder.json',
            'Cmis/v1.1/Cache/getObjectFolder.json'
        ], $pool);

        $context = $session->getDefaultContext()->setCacheEnabled(false);
        $cache = $session->getCache();

        $objectId = SessionHelperFactory::createFolder($session);

        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $folder = $session->getObjectByPath('/test folder');
        $this->assertFalse($cache->containsId($objectId->getId(), $context->getCacheKey()));

        $this->assertCount(4, $this->guzzleContainer); // makes sure all mock responses got fetched

        return array($folder, $pool);
    }

    protected function assertGetObjectByPathRetrievesObjectFromCacheByDefault(CmisObjectInterface $object, $path, \Psr\Cache\CacheItemPoolInterface $pool)
    {
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
        ], $pool);

        $countRequestsBefore = count($this->guzzleContainer);

        $context = $session->getDefaultContext();
        $cache = $session->getCache();
        $objectId = $session->createObjectId($object->getId());

        $this->assertEqualsWithoutSession($object, $cache->getById($objectId, $context->getCacheKey()));
        $this->assertEqualsWithoutSession($object, $session->getObjectByPath($path));

        $countRequestsAfter = count($this->guzzleContainer);

        $this->assertEquals($countRequestsBefore, $countRequestsAfter);
        $this->assertCount(1, $this->guzzleContainer); // makes sure all mock responses got fetched
    }

    protected function assertGetObjectByPathDoesNotRetrieveObjectFromCacheIfDisabled(CmisObjectInterface $object, $path, \Psr\Cache\CacheItemPoolInterface $pool)
    {
        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json',
            $object instanceof Folder ? 'Cmis/v1.1/Cache/getObjectFolder.json' : 'Cmis/v1.1/Cache/getObjectDocument.json',
            $object instanceof Folder ? 'Cmis/v1.1/Cache/getTypeDefinition-cmisfolder.json' : 'Cmis/v1.1/Cache/getTypeDefinition-cmisdocument.json'
        ], $pool);

        $context = $session->getDefaultContext()->setCacheEnabled(false);
        $cache = $session->getCache();
        $objectId = $session->createObjectId($object->getId());

        $session->getObjectByPath($path);

        $this->assertNull($cache->getByPath($path, $context->getCacheKey()));
        $this->assertNull($cache->getById($objectId, $context->getCacheKey()));

        $this->assertCount(3, $this->guzzleContainer);
    }

    /**
     * @depends testGetObjectByPathSavesDocumentToCacheByDefault
     */
    public function testGetObjectByPathRetrievesDocumentFromCacheByDefault(array $dependencies)
    {
        list($document, $pool) = $dependencies;
        $this->assertGetObjectByPathRetrievesObjectFromCacheByDefault($document, '/test document', $pool);
    }

    /**
     * @depends testGetObjectByPathDoesNotSaveDocumentToCacheIfDisabled
     */
    public function testGetObjectByPathDoesNotRetrieveDocumentFromCacheIfDisabled(array $dependencies)
    {
        list($document, $pool) = $dependencies;
        $this->assertGetObjectByPathDoesNotRetrieveObjectFromCacheIfDisabled($document, '/test document', $pool);
    }

    /**
     * @depends testGetObjectByPathSavesFolderToCacheByDefault
     */
    public function testGetObjectByPathRetrievesFolderFromCacheByDefault(array $dependencies)
    {
        list($folder, $pool) = $dependencies;
        $this->assertGetObjectByPathRetrievesObjectFromCacheByDefault($folder, '/test folder', $pool);
    }

    /**
     * @depends testGetObjectByPathDoesNotSaveFolderToCacheIfDisabled
     */
    public function testGetObjectByPathDoesNotRetrieveFolderFromCacheIfDisabled(array $dependencies)
    {
        list($folder, $pool) = $dependencies;
        $this->assertGetObjectByPathDoesNotRetrieveObjectFromCacheIfDisabled($folder, '/test folder', $pool);
    }

    /**
     * @depends testGetObjectByPathSavesDocumentToCacheByDefault
     */
    public function testRemoveObjectFromCacheRemovesDocumentsSavedToCache(array $dependencies)
    {
        list($document, $pool) = $dependencies;

        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json'
        ], $pool);

        $cache = $session->getCache();
        $context = $session->getDefaultContext();

        $this->assertTrue($cache->containsId($document->getId(), $context->getCacheKey()));
        $session->removeObjectFromCache($document);
        $this->assertFalse($cache->containsId($document->getId(), $context->getCacheKey()));
        $this->assertCount(1, $this->guzzleContainer); // makes sure all mock responses got fetched
    }

    /**
     * @depends testGetObjectByPathSavesFolderToCacheByDefault
     */
    public function testRemoveObjectFromCacheRemovesFoldersSavedToCache(array $dependencies)
    {
        list($folder, $pool) = $dependencies;

        $session = $this->createSessionWithMockResponses([
            'Cmis/v1.1/Cache/getRepositoryInfo.json'
        ], $pool);

        $cache = $session->getCache();
        $context = $session->getDefaultContext();

        $this->assertTrue($cache->containsId($folder->getId(), $context->getCacheKey()));
        $session->removeObjectFromCache($folder);
        $this->assertFalse($cache->containsId($folder->getId(), $context->getCacheKey()));
        $this->assertCount(1, $this->guzzleContainer); // makes sure all mock responses got fetched
    }
}