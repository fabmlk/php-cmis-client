<?php

namespace Dkd\PhpCmis\Paging;

/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-impl/src/main/java/org/apache/chemistry/opencmis/client/runtime/util/AbstractPageFetcher.java
 */
abstract class AbstractPageFetcher
{
    protected $maxNumItems;

    public function __construct($maxNumItems)
    {
        $this->maxNumItems = $maxNumItems;
    }

    public abstract function fetchPage($skipCount);

    public function setMaxNumItems($maxNumItems)
    {
        $this->maxNumItems = $maxNumItems;
    }
}
