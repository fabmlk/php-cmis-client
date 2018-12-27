<?php
/**
 * This example will get the latest document version.
 */

require_once(__DIR__ . '/../vendor/autoload.php');
if (!is_file(__DIR__ . '/conf/Configuration.php')) {
    die("Please add your connection credentials to the file \"" . __DIR__ . "/conf/Configuration.php\".\n");
} else {
    require_once(__DIR__ . '/conf/Configuration.php');
}

require_once 'CreateDocument.php';

$doc = $session->getObjectByPath('/Demo Object');
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
