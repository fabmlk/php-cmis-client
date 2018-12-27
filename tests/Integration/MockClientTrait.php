<?php

namespace Dkd\PhpCmis\Test\Integration;

use Dkd\PhpCmis\Bindings\CmisBindingsHelper;
use Dkd\PhpCmis\Session;
use Psr\Cache\CacheItemPoolInterface;

trait MockClientTrait
{
    private $guzzleContainer = [];

    private $defaultRepositoryId = "A1";
    private $defaultRepositoryUrl = "http://192.168.99.100:8888/inmemory/browser";

    private function createSessionWithMockResponses(
        array $fixtures,
        CacheItemPoolInterface $objectPool = null,
        CacheItemPoolInterface $repoPool = null,
        CacheItemPoolInterface $typePool = null,
        CmisBindingsHelper $bindingsHelper = null
    ) {
        $client = $this->createMockClient($fixtures);

        return $this->createSession(
            $client,
            $this->defaultRepositoryId,
            $this->defaultRepositoryUrl,
            $objectPool,
            $repoPool,
            $typePool,
            $bindingsHelper
        );
    }

    private function createMockClient(array $fixtures)
    {
        $responses = [];
        foreach ($fixtures as $fixture) {
            $responses[] = $this->getResponseFixture($fixture);
        }

        // Create a mock and queue two responses.
        $mock = new \GuzzleHttp\Handler\MockHandler($responses);

        $handler = \GuzzleHttp\HandlerStack::create($mock);
        $history = \GuzzleHttp\Middleware::history($this->guzzleContainer);
        // Add the history middleware to the handler stack.
        $handler->push($history);

        return new \GuzzleHttp\Client(['handler' => $handler]);
    }

    abstract function getResponseFixture($fixture);

    private function createSession(
        \GuzzleHttp\Client $httpInvoker,
        $repositoryId,
        $url,
        CacheItemPoolInterface $objectPool = null,
        CacheItemPoolInterface $repoPool = null,
        CacheItemPoolInterface $typePool = null,
        CmisBindingsHelper $bindingsHelper = null
    ) {
        $parameters = [
            \Dkd\PhpCmis\SessionParameter::REPOSITORY_ID => $repositoryId,
            \Dkd\PhpCmis\SessionParameter::BINDING_TYPE => \Dkd\PhpCmis\Enum\BindingType::BROWSER,
            \Dkd\PhpCmis\SessionParameter::BROWSER_URL => $url,
            \Dkd\PhpCmis\SessionParameter::BROWSER_SUCCINCT => false,
            \Dkd\PhpCmis\SessionParameter::HTTP_INVOKER_OBJECT => $httpInvoker,
            \Dkd\PhpCmis\SessionParameter::PSR6_CACHE_OBJECT => $objectPool,
            \Dkd\PhpCmis\SessionParameter::PSR6_REPOSITORY_INFO_CACHE_OBJECT => $repoPool,
            \Dkd\PhpCmis\SessionParameter::PSR6_TYPE_DEFINITION_CACHE_OBJECT => $typePool
        ];

        return new Session($parameters, null, null, null, $bindingsHelper);
    }
}