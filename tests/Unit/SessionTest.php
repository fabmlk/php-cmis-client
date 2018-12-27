<?php
namespace Dkd\PhpCmis\Test\Unit;

/*
 * This file is part of php-cmis-client
 *
 * (c) Sascha Egerer <sascha.egerer@dkd.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Dkd\PhpCmis;
use Dkd\PhpCmis\Bindings\CmisBindingsHelper;
use Dkd\PhpCmis\Cache\Cache;
use Dkd\PhpCmis\ObjectFactoryInterface;
use Dkd\PhpCmis\Session;
use Dkd\PhpCmis\SessionParameter;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Class SessionTest
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    use SessionHelperTrait;

    public function testConstructorThrowsExceptionIfNoParametersGiven()
    {
        $this->setExpectedException(
            '\\Dkd\\PhpCmis\\Exception\\CmisInvalidArgumentException',
            'No parameters provided!',
            1408115280
        );
        new Session([]);
    }

    public function testObjectFactoryIsSetToDefaultObjectFactoryWhenNoObjectFactoryIsGivenOrDefined()
    {
        $session = new Session(
            [SessionParameter::REPOSITORY_ID => 'foo'],
            null,
            null,
            null,
            $this->getBindingsHelperMock('foo')
        );
        $this->assertInstanceOf('\\Dkd\\PhpCmis\\ObjectFactory', $session->getObjectFactory());
    }

    public function testObjectFactoryIsSetToObjectFactoryInstanceGivenAsMethodParameter()
    {
        /** @var ObjectFactoryInterface $dummyObjectFactory */
        $dummyObjectFactory = $this->getMock('\\Dkd\\PhpCmis\\ObjectFactoryInterface');
        $session = new Session(
            [SessionParameter::REPOSITORY_ID => 'foo'],
            $dummyObjectFactory,
            null,
            null,
            $this->getBindingsHelperMock('foo')
        );

        $this->assertSame($dummyObjectFactory, $session->getObjectFactory());
    }

    public function testObjectFactoryIsSetToObjectFactoryDefinedInParametersArray()
    {
        $objectFactory = $this->getMock('\\Dkd\\PhpCmis\\ObjectFactory');
        $session = new Session(
            [
                SessionParameter::REPOSITORY_ID => 'foo',
                SessionParameter::OBJECT_FACTORY_CLASS => get_class($objectFactory)
            ],
            null,
            null,
            null,
            $this->getBindingsHelperMock('foo')
        );

        $this->assertEquals($objectFactory, $session->getObjectFactory());
    }

    public function testExceptionIsThrownIfConfiguredObjectFactoryDoesNotImplementObjectFactoryInterface()
    {
        $this->setExpectedException(
            '\\RuntimeException',
            '',
            1408354120
        );

        $object = $this->getMock('\\stdClass');
        new Session(
            [SessionParameter::OBJECT_FACTORY_CLASS => get_class($object)]
        );
    }

    public function testCreatedObjectFactoryInstanceWillBeInitialized()
    {
        // dummy object factory with a spy on initialize
        $objectFactory = $this->getMock('\\Dkd\\PhpCmis\\ObjectFactory');
        $objectFactory->expects($this->once())->method('initialize');

        $sessionClassName = '\\Dkd\\PhpCmis\\Session';

        // Get mock, without the constructor being called
        $mock = $this->getMockBuilder($sessionClassName)
                     ->disableOriginalConstructor()
                     ->setMethods(['createDefaultObjectFactoryInstance'])
                     ->getMock();

        // set createDefaultObjectFactoryInstance to return our object factory spy
        $mock->expects($this->once())
             ->method('createDefaultObjectFactoryInstance')
             ->willReturn($objectFactory);

        // now call the constructor
        $reflectedClass = new \ReflectionClass(get_class($mock));
        $constructor = $reflectedClass->getConstructor();
        $constructor->invoke(
            $mock,
            [SessionParameter::REPOSITORY_ID => 'foo'],
            null,
            null,
            null,
            $this->getBindingsHelperMock('foo')
        );
    }

    public function testCreateQueryStatementThrowsErrorOnEmptyProperties()
    {
        $this->setExpectedException('\\Dkd\\PhpCmis\\Exception\\CmisInvalidArgumentException');
        $mock = $this->getMockBuilder('\\Dkd\\PhpCmis\\Session')
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->createQueryStatement([], ['foobar']);
    }

    public function testCreateQueryStatementThrowsErrorOnEmptyTypes()
    {
        $this->setExpectedException('\\Dkd\\PhpCmis\\Exception\\CmisInvalidArgumentException');
        $mock = $this->getMockBuilder('\\Dkd\\PhpCmis\\Session')
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->createQueryStatement(['foobar'], []);
    }

    public function testCacheIsSetToDefaultCacheWhenNoCacheIsGivenOrDefined()
    {
        $session = new Session(
            [SessionParameter::REPOSITORY_ID => 'foo'],
            null,
            null,
            null,
            $this->getBindingsHelperMock('foo')
        );
        $this->assertInstanceOf('\\Dkd\\PhpCmis\\Cache\\Cache', $session->getCache());
    }

    public function testCacheIsSetToCacheInstanceGivenAsMethodParameter()
    {
        /** @var \Dkd\PhpCmis\Cache\Cache $dummyCache */
        $dummyCache = $this->getMockForAbstractClass('\\Dkd\\PhpCmis\\Cache\\CacheInterface');
        $session = new Session(
            [SessionParameter::REPOSITORY_ID => 'foo'],
            null,
            $dummyCache,
            null,
            $this->getBindingsHelperMock('foo')
        );
        $this->assertSame($dummyCache, $session->getCache());
    }

    public function testCacheIsSetToCacheDefinedInParametersArray()
    {
        $pool = $this->getMockForAbstractClass('\\Psr\\Cache\\CacheItemPoolInterface');
        $cache = new Cache($pool);
        $session = new Session(
            [SessionParameter::REPOSITORY_ID => 'foo', SessionParameter::PSR6_CACHE_OBJECT => $pool],
            null,
            null,
            null,
            $this->getBindingsHelperMock('foo')
        );
        $cache->initialize($session, array());
        $this->assertEquals($cache, $session->getCache());
    }

    // No longer applicable
//    public function testExceptionIsThrownIfConfiguredCacheDoesNotImplementCacheInterface()
//    {
//        $this->setExpectedException(
//            '\\Dkd\\PhpCmis\\Exception\\CmisInvalidArgumentException',
//            '',
//            1408354123
//        );
//        $object = $this->getMock('\\stdClass');
//        new Session(
//            [SessionParameter::CACHE_CLASS => get_class($object)]
//        );
//    }

    public function testCacheUsesDefaultPsr6PoolIfNoneProvided()
    {
        $session = new Session(
            [
                SessionParameter::REPOSITORY_ID => 'foo'
            ],
            null,
            null,
            null,
            $this->getBindingsHelperMock('foo')
        );
        $poolProperty = (new \ReflectionClass($session->getCache()))->getProperty('pool');
        $poolProperty->setAccessible(true);
        $this->assertEquals(new ArrayAdapter(), $poolProperty->getValue($session->getCache()));
    }

    public function testGetRelationships()
    {
        $bindingsMock = $this->getBindingsHelperMock('foo');
        $session = new Session(
            [SessionParameter::REPOSITORY_ID => 'foo'],
            null,
            null,
            null,
            $bindingsMock
        );
        $repositoryInfo = $this->getMockBuilder('\\Dkd\\PhpCmis\\DataObjects\\RepositoryInfo')
            ->setMethods(['getId'])
            ->getMock();
        $repositoryInfo->expects($this->once())->method('getId');
        $objectType = $this->getMockBuilder('\\Dkd\\PhpCmis\\Data\\ObjectTypeInterface')
            ->setMethods(['getId', '__toString'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $property = new \ReflectionProperty($session, 'repositoryInfo');
        $property->setAccessible(true);
        $property->setValue($session, $repositoryInfo);
        $relationships = $session->getRelationships(
            new PhpCmis\DataObjects\ObjectId('foobar-object-id'),
            true,
            PhpCmis\Enum\RelationshipDirection::cast(PhpCmis\Enum\RelationshipDirection::TARGET),
            $objectType
        );
    }
}
