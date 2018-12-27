<?php


namespace Dkd\PhpCmis\Paging;

/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-impl/src/main/java/org/apache/chemistry/opencmis/client/runtime/util/CollectionIterable.java
 */
class CollectionIterable extends AbstractIterable
{
    protected function createIterator()
    {
        return new CollectionIterator($this->getSkipCount(), $this->getPageFetcher());
    }
}