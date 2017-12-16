<?php
/**
 * This example will list the children of the CMIS root folder.
 * The list is created recursively but is limited to 5 items per level.
 */

require_once(__DIR__ . '/../vendor/autoload.php');
if (!is_file(__DIR__ . '/conf/Configuration.php')) {
    die("Please add your connection credentials to the file \"" . __DIR__ . "/conf/Configuration.php\".\n");
} else {
    require_once(__DIR__ . '/conf/Configuration.php');
}

$httpInvoker = new \GuzzleHttp\Client(
    [
        'auth' => [
            CMIS_BROWSER_USER,
            CMIS_BROWSER_PASSWORD
        ]
    ]
);

$parameters = [
    \Dkd\PhpCmis\SessionParameter::BINDING_TYPE => \Dkd\PhpCmis\Enum\BindingType::BROWSER,
    \Dkd\PhpCmis\SessionParameter::BROWSER_URL => CMIS_BROWSER_URL,
    \Dkd\PhpCmis\SessionParameter::BROWSER_SUCCINCT => false,
    \Dkd\PhpCmis\SessionParameter::HTTP_INVOKER_OBJECT => $httpInvoker,
];

$sessionFactory = new \Dkd\PhpCmis\SessionFactory();

// If no repository id is defined use the first repository
if (CMIS_REPOSITORY_ID === null) {
    $repositories = $sessionFactory->getRepositories($parameters);
    $parameters[\Dkd\PhpCmis\SessionParameter::REPOSITORY_ID] = $repositories[0]->getId();
} else {
    $parameters[\Dkd\PhpCmis\SessionParameter::REPOSITORY_ID] = CMIS_REPOSITORY_ID;
}

$session = $sessionFactory->createSession($parameters);
$context = $session->createOperationContext(
    [],
    false,
    true,
    false,
    null,
    [],
    true,
    "cmis:name",
    false,
    5
);



// Get the root folder of the repository
$rootFolder = $session->getRootFolder();

echo '+ [ROOT FOLDER]: ' . $rootFolder->getName() . "\n";

printFolderContent($rootFolder, $context);

function printFolderContent(\Dkd\PhpCmis\Data\FolderInterface $folder, $context, $levelIndention = '  ')
{
    $i = 0;
    $children = $folder->getChildren($context);
    $page = $children->skipTo(0)->getPage(2);
    foreach ($page as $child) {
        echo $levelIndention;
        $i++;
        if ($i > 10) {
            echo "| ...\n";
            break;
        }

        if ($child instanceof \Dkd\PhpCmis\Data\FolderInterface) {
            echo '+ [FOLDER]: ' . $child->getName() . "\n";
            printFolderContent($child, $context, $levelIndention . '  ');
        } elseif ($child instanceof \Dkd\PhpCmis\Data\DocumentInterface) {
            echo '- [DOCUMENT]: ' . $child->getName() . "\n";
        } else {
            echo '- [ITEM]: ' . $child->getName() . "\n";
        }
    }
}

/*
Groovy Script

import org.apache.chemistry.opencmis.commons.*
import org.apache.chemistry.opencmis.commons.data.*
import org.apache.chemistry.opencmis.commons.enums.*
import org.apache.chemistry.opencmis.client.api.*
import org.apache.chemistry.opencmis.client.util.*
import org.apache.chemistry.opencmis.commons.definitions.*

def rootFolder = session.getRootFolder();

def childrenOpCtx = session.createOperationContext();
childrenOpCtx.setFilterString(
    "cmis:objectId,cmis:baseTypeId," +
    "cmis:name,cmis:contentStreamLength," +
    "cmis:contentStreamMimeType");
childrenOpCtx.setIncludeAcls(false);
childrenOpCtx.setIncludeAllowableActions(true);
childrenOpCtx.setIncludePolicies(false);
childrenOpCtx.setIncludeRelationships(IncludeRelationships.NONE);
childrenOpCtx.setRenditionFilterString("cmis:none");
childrenOpCtx.setIncludePathSegments(false);
childrenOpCtx.setOrderBy("cmis:name");
childrenOpCtx.setCacheEnabled(false);
childrenOpCtx.setMaxItemsPerPage(5);

def children = rootFolder.getChildren(childrenOpCtx);

System.out.println('+ [ROOT FOLDER]: ' + rootFolder.getName());

printFolderContent(rootFolder, childrenOpCtx);

def printFolderContent(folder, context, levelIndention = '  ')
{
i = 0;
    children = folder.getChildren(context);
    ItemIterable<CmisObject> page = children.skipTo(0).getPage(2);
    for (CmisObject child: page) {
        System.out.print(levelIndention);
        i++;
        if (i > 10) {
            System.out.println("| ...");
            break;
        }

        if (child instanceof Folder) {
            System.out.println('+ [FOLDER]: ' + child.getName());
            printFolderContent(child, context, levelIndention + '  ');
        } else if (child instanceof Document) {
            System.out.println('- [DOCUMENT]: ' + child.getName());
        } else {
            System.out.println('- [ITEM]: ' + child.getName());
        }
    }
}
*/