<?php

namespace Dkd\PhpCmis\Test\Integration;


use Dkd\PhpCmis\Session;

/**
 * Class AbstractSessionHelper.
 */
class SessionHelperFactory
{
    /**
     * @param Session $session
     * @return \Dkd\PhpCmis\Data\ObjectIdInterface|null
     */
    static function createDocument(Session $session)
    {
        $properties = [
            \Dkd\PhpCmis\PropertyIds::OBJECT_TYPE_ID => 'cmis:document',
            \Dkd\PhpCmis\PropertyIds::NAME => 'test document'
        ];

        return $session->createDocument(
            $properties,
            $session->createObjectId(
                $session->getRepositoryInfo()->getRootFolderId()
            ),
            \GuzzleHttp\Stream\Stream::factory("WHATEVER")
        );
    }

    /**
     * @param Session $session
     * @return \Dkd\PhpCmis\Data\ObjectIdInterface
     */
    static function createFolder(Session $session)
    {
        $properties = [
            \Dkd\PhpCmis\PropertyIds::OBJECT_TYPE_ID => 'cmis:folder',
            \Dkd\PhpCmis\PropertyIds::NAME => 'test folder'
        ];

        return $session->createFolder(
            $properties,
            $session->createObjectId($session->getRepositoryInfo()->getRootFolderId()));
    }
}