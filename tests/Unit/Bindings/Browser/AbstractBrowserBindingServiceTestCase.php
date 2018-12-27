<?php
namespace Dkd\PhpCmis\Test\Unit\Bindings\Browser;

/*
 * This file is part of php-cmis-client
 *
 * (c) Sascha Egerer <sascha.egerer@dkd.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Dkd\PhpCmis\Bindings\BindingSessionInterface;
use Dkd\PhpCmis\Bindings\CmisBindingsHelper;
use Dkd\PhpCmis\SessionParameter;
use Dkd\PhpCmis\Test\Unit\FixtureHelperTrait;
use Dkd\PhpCmis\Test\Unit\ReflectionHelperTrait;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class AbstractBrowserBindingServiceTestCase
 */
abstract class AbstractBrowserBindingServiceTestCase extends \PHPUnit_Framework_TestCase
{
    use ReflectionHelperTrait;
    use FixtureHelperTrait;

    const BROWSER_URL_TEST = 'http://foo.bar.baz';

    /**
     * Returns a mock of a BindingSessionInterface
     *
     * @param array $sessionParameterMap
     * @return BindingSessionInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSessionMock($sessionParameterMap = [])
    {
        $map = [
            [SessionParameter::BROWSER_SUCCINCT, null, false],
            [SessionParameter::BROWSER_URL, null, self::BROWSER_URL_TEST],
            [CmisBindingsHelper::REPOSITORY_INFO_CACHE, null, $this->getRepositoryInfoCacheMock()],
            [CmisBindingsHelper::TYPE_DEFINITION_CACHE, null, $this->getTypeDefinitionCacheMock()]
        ];

        $map = array_merge($sessionParameterMap, $map);

        $sessionMock = $this->getMockBuilder(
            '\\Dkd\\PhpCmis\\Bindings\\BindingSessionInterface'
        )->setMethods(['get'])->getMockForAbstractClass();

        $sessionMock->expects($this->any())->method('get')->will($this->returnValueMap($map));

        return $sessionMock;
    }

    protected function getRepositoryInfoCacheMock()
    {
        return $this->getMockBuilder('\\Dkd\\PhpCmis\\Bindings\\RepositoryInfoCache')
            ->disableOriginalConstructor()->getMock();
    }

    protected function getTypeDefinitionCacheMock()
    {
        return $this->getMockBuilder('\\Dkd\\PhpCmis\\Bindings\\TypeDefinitionCache')
            ->disableOriginalConstructor()->getMock();
    }
}
