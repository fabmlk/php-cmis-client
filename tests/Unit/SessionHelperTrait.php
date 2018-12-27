<?php
namespace Dkd\PhpCmis\Test\Unit;

use Dkd\PhpCmis\Bindings\CmisBindingsHelper;
use PHPUnit_Framework_MockObject_MockObject;

trait SessionHelperTrait
{
    /**
     * @return CmisBindingsHelper|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBindingsHelperMock($repositoryId)
    {
        $repositoryInfoMock = self::getMockBuilder(
            '\\Dkd\\PhpCmis\\Data\\RepositoryInfoInterface'
        )->setMethods(['getId'])->getMockForAbstractClass();
        $repositoryInfoMock->expects(self::any())->method('getId')->willReturn($repositoryId);
        $repositoryServiceMock = self::getMockBuilder(
            '\\Dkd\\PhpCmis\\RepositoryServiceInterface'
        )->setMethods(['getRepositoryInfo'])->getMockForAbstractClass();
        $repositoryServiceMock->expects(self::any())->method('getRepositoryInfo')->willReturn($repositoryInfoMock);
        $relationshipServiceMock = self::getMockBuilder(
            '\\Dkd\\PhpCmis\\RelationshipServiceInterface'
        )->getMockForAbstractClass();
        $bindingMock = self::getMockBuilder('\\Dkd\\PhpCmis\\Bindings\\CmisBindingInterface')->setMethods(
            ['getRepositoryService', 'getRelationshipService']
        )->getMockForAbstractClass();
        $bindingMock->expects(self::any())->method('getRepositoryService')->willReturn($repositoryServiceMock);
        $bindingMock->expects(self::any())->method('getRelationshipService')->willReturn($relationshipServiceMock);
        /** @var CmisBindingsHelper|PHPUnit_Framework_MockObject_MockObject $bindingsHelperMock */
        $bindingsHelperMock = self::getMockBuilder('\\Dkd\\PhpCmis\\Bindings\\CmisBindingsHelper')->setMethods(
            ['createBinding']
        )->getMockForAbstractClass();
        $bindingsHelperMock->expects(self::any())->method('createBinding')->willReturn($bindingMock);

        return $bindingsHelperMock;
    }
}
