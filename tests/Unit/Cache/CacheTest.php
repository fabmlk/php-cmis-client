<?php

namespace Dkd\PhpCmis\Test\Unit\Cache;


use Dkd\PhpCmis\Cache\Cache;
use Dkd\PhpCmis\DataObjects\AbstractCmisObject;
use Dkd\PhpCmis\OperationContext;
use Dkd\PhpCmis\Session;
use Dkd\PhpCmis\SessionParameter;
use Dkd\PhpCmis\Test\Unit\ReflectionHelperTrait;
use Dkd\PhpCmis\Test\Unit\SessionHelperTrait;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Class SessionCacheTest
 */
class CacheTest extends \PHPUnit_Framework_TestCase
{
    use SessionHelperTrait;
    use ReflectionHelperTrait;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var OperationContext
     */
    protected $operationContext;

    public function setUp()
    {
        $session = new Session(
            [SessionParameter::REPOSITORY_ID => 'foo'],
            null,
            null,
            null,
            $this->getBindingsHelperMock('foo')
        );

        $this->cache = new Cache(new ArrayAdapter());
        $this->cache->initialize($session);
        $this->operationContext = $session->getDefaultContext();
    }

    public function testContainsIdIsTrueForSavedObjects()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        $cacheKey = $this->operationContext->getCacheKey();
        $this->cache->put($object, $cacheKey);

        $this->assertTrue($this->cache->containsId('1234', $cacheKey));
        $this->assertTrue($this->cache->containsId('1234', strtr($cacheKey, '01', '10')));
        $this->assertFalse($this->cache->containsId('4321', $cacheKey));
    }

    public function testContainsPathIsTrueForSavedPaths()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        $cacheKey = $this->operationContext->getCacheKey();
        $this->cache->putPath('/a/b/c', $object, $cacheKey);

        $this->assertTrue($this->cache->containsPath('/a/b/c', $cacheKey));
        $this->assertTrue($this->cache->containsPath('/a/b/c', strtr($cacheKey, '01', '10')));
        $this->assertFalse($this->cache->containsPath('/c/b/a', $cacheKey));
    }

    public function testContainsIdIsTrueForSavedPaths()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        $cacheKey = $this->operationContext->getCacheKey();
        // path is irrelevant
        $this->cache->putPath(random_bytes(10), $object, $cacheKey);

        $this->assertTrue($this->cache->containsId('1234', $cacheKey));
        $this->assertTrue($this->cache->containsId('1234', strtr($cacheKey, '01', '10')));
        $this->assertFalse($this->cache->containsId('4321', $cacheKey));
    }

    public function testContainsPathIsTrueForSavedObjectsWithPath()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        $object->expects($this->once())->method('getPropertyValue')->willReturn('/a/b/c');
        $cacheKey = $this->operationContext->getCacheKey();
        $this->cache->put($object, $cacheKey);

        $this->assertTrue($this->cache->containsPath('/a/b/c', $cacheKey));
        $this->assertTrue($this->cache->containsPath('/a/b/c', strtr($cacheKey, '01', '10')));
        $this->assertFalse($this->cache->containsPath('/c/b/a', $cacheKey));
    }

    public function testGetByIdReturnsSavedObjectsByPut()
    {
        // we want serialize()/unserialize() to actually be called here
        $objectVariant1 = $this->getMockBuilder(AbstractCmisObject::class)
            ->setMethods(['getId', 'refreshSession'])->getMock();
        $objectVariant1->method('getId')->willReturn('1234');
        $objectVariant2 = clone $objectVariant1;
        // makes sure variants have different properties so assertEquals() cannot be true for both
        $this->setProtectedProperty($objectVariant1, 'refreshTimestamp', 1);
        $this->setProtectedProperty($objectVariant2, 'refreshTimestamp', 2);

        $this->cache->put($objectVariant1, '0');
        $this->cache->put($objectVariant2, '1');

        $this->assertEquals($objectVariant1, $this->cache->getById('1234', '0'));
        $this->assertEquals($objectVariant2, $this->cache->getById('1234', '1'));
        $this->assertNull($this->cache->getById('1234', '2'));
    }

    public function testGetByIdReturnsSavedObjectsByPutPath()
    {
        // we want serialize()/unserialize() to actually be called here
        $objectVariant1 = $this->getMockBuilder(AbstractCmisObject::class)
            ->setMethods(['getId', 'refreshSession'])->getMock();
        $objectVariant1->method('getId')->willReturn('1234');
        $objectVariant2 = clone $objectVariant1;
        // makes sure variants have different properties so assertEquals() cannot be true for both
        $this->setProtectedProperty($objectVariant1, 'refreshTimestamp', 1);
        $this->setProtectedProperty($objectVariant2, 'refreshTimestamp', 2);

        // paths are irrelevant
        $this->cache->putPath(random_bytes(10), $objectVariant1, '0');
        $this->cache->putPath(random_bytes(10), $objectVariant2, '1');

        $this->assertEquals($objectVariant1, $this->cache->getById('1234', '0'));
        $this->assertEquals($objectVariant2, $this->cache->getById('1234', '1'));
        $this->assertNull($this->cache->getById('1234', '2'));
    }

    public function testGetByPathReturnsSavedObjectsByPut()
    {
        // we want serialize()/unserialize() to actually be called here
        $objectVariant1 = $this->getMockBuilder(AbstractCmisObject::class)
            ->setMethods(['getId', 'refreshSession', 'getPropertyValue'])->getMock();
        $objectVariant1->method('getId')->willReturn('1234');
        $objectVariant1->expects($this->exactly(2))->method('getPropertyValue')->willReturn('/a/b/c');
        $objectVariant2 = clone $objectVariant1;
        // makes sure variants have different properties so assertEquals() cannot be true for both
        $this->setProtectedProperty($objectVariant1, 'refreshTimestamp', 1);
        $this->setProtectedProperty($objectVariant2, 'refreshTimestamp', 2);

        $this->cache->put($objectVariant1, '0');
        $this->cache->put($objectVariant2, '1');

        $this->assertEquals($objectVariant1, $this->cache->getByPath('/a/b/c', '0'));
        $this->assertEquals($objectVariant2, $this->cache->getByPath('/a/b/c', '1'));
        $this->assertNull($this->cache->getByPath('/a/b/c', '2'));
    }

    public function testGetByPathReturnSavedObjectsByPutPath()
    {
        // we want serialize()/unserialize() to actually be called here
        $objectVariant1 = $this->getMockBuilder(AbstractCmisObject::class)
            ->setMethods(['getId', 'refreshSession'])->getMock();
        $objectVariant1->method('getId')->willReturn('1234');
        $objectVariant2 = clone $objectVariant1;
        // makes sure variants have different properties so assertEquals() cannot be true for both
        $this->setProtectedProperty($objectVariant1, 'refreshTimestamp', 1);
        $this->setProtectedProperty($objectVariant2, 'refreshTimestamp', 2);

        $this->cache->putPath('/a/b/c', $objectVariant1, '0');
        $this->cache->putPath('/a/b/c', $objectVariant2, '1');

        $this->assertEquals($objectVariant1, $this->cache->getByPath('/a/b/c', '0'));
        $this->assertEquals($objectVariant2, $this->cache->getByPath('/a/b/c', '1'));
        $this->assertNull($this->cache->getByPath('/a/b/c', '2'));
    }

    public function testGetObjectIdByPathReturnsSavedIdsByPut()
    {
        // we don't care about calling serialie()/unserialize() here
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        $object->expects($this->once())->method('getPropertyValue')->willReturn('/a/b/c');
        // cacheKey is irrelevant
        $this->cache->put($object, random_bytes(10));

        $this->assertEquals('1234', $this->cache->getObjectIdByPath('/a/b/c'));
        $this->assertNull($this->cache->getObjectIdByPath('/c/b/a'));
    }

    public function testGetObjectIdByPathReturnsSavedIdsByPutPath()
    {
        // we don't care about calling serialie()/unserialize() here
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        // cacheKey is irrelevant
        $this->cache->putPath('/a/b/c', $object, random_bytes(10));

        $this->assertEquals('1234', $this->cache->getObjectIdByPath('/a/b/c'));
        $this->assertNull($this->cache->getObjectIdByPath('/c/b/a'));
    }

    public function testRemoveDeletesSavedObjectd()
    {
        $object1 = $this->getMock(AbstractCmisObject::class);
        $object1->method('getId')->willReturn('1234');
        $object1->expects($this->once())->method('getPropertyValue')->willReturn('/a/b/c');
        $object2 = $this->getMock(AbstractCmisObject::class);
        $object2->method('getId')->willReturn('4321');
        $object2->expects($this->once())->method('getPropertyValue')->willReturn('/c/b/a');

        $cacheKey = $this->operationContext->getCacheKey();
        $this->cache->put($object1, $cacheKey);
        $this->cache->put($object2, $cacheKey);

        $this->cache->remove('1234');

        $this->assertFalse($this->cache->containsId('1234', $cacheKey));
        $this->assertNull($this->cache->getById('1234', $cacheKey));
        $this->assertTrue($this->cache->containsId('4321', $cacheKey));
        $this->assertEquals($object2, $this->cache->getById('4321', $cacheKey));
        $this->assertNull($this->cache->getByPath('/a/b/c', $cacheKey));
        $this->assertTrue($this->cache->containsPath('/c/b/a', $cacheKey));
        $this->assertEquals($object2, $this->cache->getByPath('/c/b/a', $cacheKey));
    }

    public function testRemoveDoesNotDeletePaths()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        $object->expects($this->once())->method('getPropertyValue')->willReturn('/a/b/c');
        $cacheKey = $this->operationContext->getCacheKey();

        $this->cache->put($object, $cacheKey);

        $this->cache->remove('1234');

        $this->assertTrue($this->cache->containsPath('/a/b/c', $cacheKey));
        $this->assertEquals('1234', $this->cache->getObjectIdByPath('/a/b/c'));
    }

    public function testRemoveDeletesAllVariants()
    {
        $objectVariant1 = $this->getMock(AbstractCmisObject::class);
        $objectVariant1->method('getId')->willReturn('1234');
        $objectVariant1->expects($this->exactly(2))->method('getPropertyValue')->willReturn('/a/b/c');

        $objectVariant2 = clone $objectVariant1;

        $this->cache->put($objectVariant1, '0');
        $this->cache->put($objectVariant2, '1');

        $this->cache->remove('1234');

        $this->assertFalse($this->cache->containsId('1234', '0'));
        $this->assertFalse($this->cache->containsId('1234', '1'));
        $this->assertNull($this->cache->getById('1234', '0'));
        $this->assertNull($this->cache->getById('1234', '1'));
        $this->assertNull($this->cache->getByPath('/a/b/c', '0'));
        $this->assertNull($this->cache->getByPath('/a/b/c', '1'));
    }

    public function testRemovePathDeleteSavedPath()
    {
        $object1 = $this->getMock(AbstractCmisObject::class);
        $object1->method('getId')->willReturn('1234');
        $object1->expects($this->once())->method('getPropertyValue')->willReturn('/a/b/c');
        $object2 = $this->getMock(AbstractCmisObject::class);
        $object2->method('getId')->willReturn('4321');
        $object2->expects($this->once())->method('getPropertyValue')->willReturn('/c/b/a');

        $cacheKey = $this->operationContext->getCacheKey();
        $this->cache->putPath('/a/b/c', $object1, $cacheKey);
        $this->cache->putPath('/c/b/a', $object2, $cacheKey);

        $this->cache->removePath('/a/b/c');

        $this->assertFalse($this->cache->containsPath('/a/b/c', $cacheKey));
        $this->assertNull($this->cache->getByPath('/a/b/c', $cacheKey));
        $this->assertNull($this->cache->getObjectIdByPath('/a/b/c'));
        $this->assertTrue($this->cache->containsPath('/c/b/a', $cacheKey));
        $this->assertEquals($object2, $this->cache->getByPath('/c/b/a', $cacheKey));
    }

    public function testRemovePathDoesNotDeleteObjects()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        $object->expects($this->once())->method('getPropertyValue')->willReturn('/a/b/c');
        $cacheKey = $this->operationContext->getCacheKey();

        $this->cache->put($object, $cacheKey);

        $this->cache->removePath('/a/b/c');

        $this->assertTrue($this->cache->containsId('1234', $cacheKey));
        $this->assertEquals($object, $this->cache->getById('1234', $cacheKey));
    }

    public function testGetByPathRemovesPathOnFailure()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        $cacheKey = $this->operationContext->getCacheKey();

        $this->cache->putPath('/a/b/c', $object, $cacheKey);
        $this->cache->remove('1234');
        $this->cache->getByPath('/a/b/c', $cacheKey);

        $this->assertFalse($this->cache->containsPath('/a/b/c', $cacheKey));
        $this->assertNull($this->cache->getObjectIdByPath('/a/b/c'));
        $this->assertNull($this->cache->getByPath('/a/b/c', $cacheKey));
    }

    public function testPutSupportsObjectIdsAsKeyWithPsr6ReservedChars()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('{}()/\@:');

        // cacheKey irrelevant
        $this->cache->put($object, random_bytes(10));
    }

    public function testPutPathSupportsPathsAsKeyWithPsr6ReservedChars()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        // ID is irrelevant
        $object->method('getId')->willReturn(random_bytes(10));

        // cacheKey irrelevant
        $this->cache->putPath('{}()/\@:', $object, random_bytes(10));
    }

    public function testPutPathSupportsPathsAsKeyWithUnsafePsr6Chars()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        // ID is irrelevant
        $object->method('getId')->willReturn(random_bytes(10));

        // cacheKey irrelevant
        // PSR6 safe range: [A-Za-z0-9._]
        // generated by https://onlineutf8tools.com/generate-random-utf8
        $this->cache->putPath('ڊ䰻󵼬ߒa왚֠΋͌󵟆r𧘠՘󠂶4쫼fϗ9󓴽օ7-\Ꚇ􍁾̈́朰񧡵4', $object, random_bytes(10));
    }

    public function invalidKeyProvider()
    {
        return [
          [null], [''], [1]
        ];
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testPutDoesNothingIfObjectIdIsNotANonEmptyString($objectId)
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->expects($this->never())->method('serialize');
        $object->method('getId')->willReturn($objectId);
        $cacheKey = $this->operationContext->getCacheKey();

        $this->cache->put($object, $cacheKey);

        $this->assertNull($this->cache->getById($objectId, $cacheKey));
        $this->assertFalse($this->cache->containsId($objectId, $cacheKey));
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testPutDoesNotSaveAPathIfIsNotANonEmptyString($path)
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        $object->expects($this->once())->method('getPropertyValue')->willReturn($path);
        $cacheKey = $this->operationContext->getCacheKey();

        $this->cache->put($object, $cacheKey);

        $this->assertNull($this->cache->getObjectIdByPath($path));
        $this->assertFalse($this->cache->containsPath($path, $cacheKey));
    }

    /**
     * @dataProvider invalidKeyProvider
     */
    public function testPutPathDoesNothingIfPathIsNotANonEmptyString($path)
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->expects($this->never())->method($this->anything());
        $cacheKey = $this->operationContext->getCacheKey();

        $this->cache->putPath($path, $object, $cacheKey);

        $this->assertNull($this->cache->getObjectIdByPath($path));
        $this->assertFalse($this->cache->containsPath($path, $cacheKey));
    }

    public function testSessionIsRefreshedOnRetrievedObject()
    {
        $object = $this->getMock(AbstractCmisObject::class);
        $object->method('getId')->willReturn('1234');
        $object->method('getPropertyValue')->willReturn('/a/b/c');
        $object->expects($this->exactly(2))->method('refreshSession');
        $cacheKey = $this->operationContext->getCacheKey();

        $this->cache->put($object, $cacheKey);
        $this->cache->getById('1234', $cacheKey);
        $this->cache->getByPath('/a/b/c', $cacheKey);
    }
}