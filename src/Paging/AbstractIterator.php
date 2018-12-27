<?php

namespace Dkd\PhpCmis\Paging;


/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-impl/src/main/java/org/apache/chemistry/opencmis/client/runtime/util/AbstractIterator.java
 */
abstract class AbstractIterator implements \Iterator
{
    private $skipCount;
    private $pageFetcher;
    private $totalNumItems;
    private $hasMoreItems;
    private $skipOffset = 0;
    private $page;

    public function __construct($skipCount, AbstractPageFetcher $pageFetcher)
    {
        $this->skipCount = $skipCount;
        $this->pageFetcher = $pageFetcher;
    }

    public function getPosition()
    {
        return $this->skipCount + $this->skipOffset;
    }

    public function getPageNumItems()
    {
        $currentPage = $this->getCurrentPage();
        if (null !== $currentPage) {
            $items = $currentPage->getItems();
            if (null !== $items) {
                return count($items);
            }
        }
        return 0;
    }

    public function getTotalNumItems()
    {
        if (null === $this->totalNumItems) {
            $this->totalNumItems = -1;
            $currentPage = $this->getCurrentPage();
            if (null !== $currentPage) {
                // set number of items
                if (null !== $currentPage->getTotalNumItems()) {
                    $this->totalNumItems = $currentPage->getTotalNumItems();
                }
            }
        }
        return $this->totalNumItems;
    }

    public function getHasMoreItems()
    {
        if (null === $this->hasMoreItems) {
            $this->hasMoreItems = false;
            $currentPage = $this->getCurrentPage();
            if (null !== $currentPage) {
                if (null !== $currentPage->getHasMoreItems()) {
                    $this->hasMoreItems = $currentPage->getHasMoreItems();
                }
            }
        }
        return $this->hasMoreItems;
    }

    /**
     * Gets current skip count
     *
     * @return skip count
     */
    protected function getSkipCount()
    {
        return $this->skipCount;
    }

    /**
     * Gets current skip offset (from skip count)
     *
     * @return skip offset
     */
    protected function getSkipOffset()
    {
        return $this->skipOffset;
    }

    /**
     * Increment the skip offset by one
     *
     * @return incremented skip offset
     */
    protected function incrementSkipOffset()
    {
        return $this->skipOffset++;
    }

    /**
     * Gets the current page of items within collection
     *
     * @return current page
     */
    protected function getCurrentPage()
    {
        if (null === $this->page) {
            $this->page = $this->pageFetcher->fetchPage($this->skipCount);
        }
        return $this->page;
    }

    /**
     * Skip to the next page of items within collection
     *
     * @return next page
     */
    protected function incrementPage()
    {
        $this->skipCount += $this->skipOffset;
        $this->skipOffset = 0;
        $this->totalNumItems = null;
        $this->hasMoreItems = null;
        $this->page = $this->pageFetcher->fetchPage($this->skipCount);

        return $this->page;
    }
}