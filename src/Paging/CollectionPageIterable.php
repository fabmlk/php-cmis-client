<?php

namespace Dkd\PhpCmis\Paging;

/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-impl/src/main/java/org/apache/chemistry/opencmis/client/runtime/util/CollectionPageIterable.java
 */
class CollectionPageIterable extends AbstractIterable
{

    public function skipTo($position)
    {
        return new self($position, $this->getPageFetcher());
    }

    /**
     * Construct the iterator
     *
     * @return  iterator
     */
    protected function createIterator()
    {
        return new CollectionPageIterator($this->getSkipCount(), $this->getPageFetcher());
    }
}