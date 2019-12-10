<?php
namespace Dkd\PhpCmis\Bindings;

/*
 * This file is part of php-cmis-client.
 *
 * (c) Sascha Egerer <sascha.egerer@dkd.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Dkd\PhpCmis\Bindings\Browser\CmisBrowserBinding;
use Dkd\PhpCmis\Converter\JsonConverter;
use Dkd\PhpCmis\Exception\CmisInvalidArgumentException;
use Dkd\PhpCmis\SessionParameter;
use GuzzleHttp\Client;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Default factory for a CMIS binding instance.
 */
class CmisBindingFactory
{
    /**
     * Create a browser binding
     *
     * @param array $sessionParameters
     * @param TypeDefinitionCacheInterface|null $typeDefinitionCache
     * @return CmisBinding
     */
    public function createCmisBrowserBinding(array $sessionParameters, TypeDefinitionCacheInterface $typeDefinitionCache = null)
    {
        $this->validateCmisBrowserBindingParameters($sessionParameters);

        return new CmisBinding(new Session(), $sessionParameters, $typeDefinitionCache);
    }

    protected function validateCmisBrowserBindingParameters(array &$sessionParameters)
    {
        $sessionParameters[SessionParameter::BINDING_CLASS] = $sessionParameters[SessionParameter::BINDING_CLASS] ?? CmisBrowserBinding::class;
        $sessionParameters[SessionParameter::BROWSER_SUCCINCT] = $sessionParameters[SessionParameter::BROWSER_SUCCINCT] ?? true;
        $this->addDefaultSessionParameters($sessionParameters);
        $this->check($sessionParameters, SessionParameter::BROWSER_URL);
    }

    /**
     * Sets some parameters to a default value if they are not already set
     *
     * @param array $sessionParameters
     */
    protected function addDefaultSessionParameters(array &$sessionParameters)
    {
//        $sessionParameters[SessionParameter::CACHE_SIZE_REPOSITORIES] = $sessionParameters[SessionParameter::CACHE_SIZE_REPOSITORIES] ?? SessionParameterDefaults::CACHE_SIZE_REPOSITORIES;
//        $sessionParameters[SessionParameter::CACHE_SIZE_TYPES] = $sessionParameters[SessionParameter::CACHE_SIZE_TYPES] ?? SessionParameterDefaults::CACHE_SIZE_TYPES;
//        $sessionParameters[SessionParameter::CACHE_SIZE_LINKS] = $sessionParameters[SessionParameter::CACHE_SIZE_LINKS] ?? SessionParameterDefaults::CACHE_SIZE_LINKS;
        $sessionParameters[SessionParameter::HTTP_INVOKER_CLASS] = $sessionParameters[SessionParameter::HTTP_INVOKER_CLASS] ?? Client::class;
        $sessionParameters[SessionParameter::JSON_CONVERTER_CLASS] = $sessionParameters[SessionParameter::JSON_CONVERTER_CLASS] ?? JsonConverter::class;

        $sessionParameters[SessionParameter::PSR6_CACHE_OBJECT] = $sessionParameters[SessionParameter::PSR6_CACHE_OBJECT] ?? new ArrayAdapter();
        $sessionParameters[SessionParameter::PSR6_REPOSITORY_INFO_CACHE_OBJECT] = $sessionParameters[SessionParameter::PSR6_REPOSITORY_INFO_CACHE_OBJECT] ?? new ArrayAdapter();
        $sessionParameters[SessionParameter::PSR6_TYPE_DEFINITION_CACHE_OBJECT] = $sessionParameters[SessionParameter::PSR6_TYPE_DEFINITION_CACHE_OBJECT] ?? new ArrayAdapter();
    }

    /**
     * Checks if the given parameter is present. If not, throw an
     * <code>IllegalArgumentException</code>.
     *
     * @param array $sessionParameters
     * @param string $parameter
     * @throws CmisInvalidArgumentException
     * @return boolean
     */
    protected function check(array $sessionParameters, $parameter)
    {
        if (empty($sessionParameters[$parameter])) {
            throw new CmisInvalidArgumentException(sprintf('Parameter "%s" is missing!', $parameter));
        }

        return true;
    }
}
