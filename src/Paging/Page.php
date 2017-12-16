<?php

namespace Dkd\PhpCmis\Paging;

/**
 * A fetched page.
 *
 * https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-impl/src/main/java/org/apache/chemistry/opencmis/client/runtime/util/AbstractPageFetcher.java
 * (inner class in Java)
 */
class Page
{
    private $items;
    private $totalNumItems;
    private $hasMoreItems;

    public function __construct(array $items, $totalNumItems, $hasMoreItems)
    {
        $this->items = $items;
        $this->totalNumItems = $totalNumItems;
        $this->hasMoreItems = $hasMoreItems;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getTotalNumItems()
    {
        return $this->totalNumItems;
    }

    public function getHasMoreItems()
    {
        return $this->hasMoreItems;
    }
}