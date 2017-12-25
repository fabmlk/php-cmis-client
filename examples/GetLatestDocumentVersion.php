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

$doc = $session->getObjectByPath('/User Homes/mjackson/toto');
echo 'Current Id: ' . $doc->getId() . PHP_EOL;

$objectIdReturned = $doc->setContentStream(
    \GuzzleHttp\Stream\Stream::factory("Hello Toto!"),
    true,
    false
);

if ($objectIdReturned) {
    echo 'Id returned by update content stream: ' . $objectIdReturned->getId() . PHP_EOL;
}

$latestDoc = $doc->getObjectOfLatestVersion(false);
echo 'Latest Id: ' . $latestDoc->getId() . PHP_EOL;
