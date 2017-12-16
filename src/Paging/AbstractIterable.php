<?php


namespace Dkd\PhpCmis\Paging;

/**
 * https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-impl/src/main/java/org/apache/chemistry/opencmis/client/runtime/util/AbstractIterable.java
 */
abstract class AbstractIterable implements \IteratorAggregate
{
    private $pageFetcher;

    private $skipCount;

    private $iterator;

    public function __construct($position, AbstractPageFetcher $pageFetcher)
    {
        $this->pageFetcher = $pageFetcher;
        $this->skipCount = $position;
    }

    public function skipTo($position)
    {
        return new CollectionIterable($position, $this->pageFetcher);
    }

    public function getPage($maxNumItems = null)
    {
        if ($maxNumItems) {
            $this->pageFetcher->setMaxNumItems($maxNumItems);
        }

        return new CollectionPageIterable($this->skipCount, $this->pageFetcher);
    }

    public function getPageNumItems()
    {
        return $this->getIterator()->getPageNumItems();
    }

    public function getHasMoreItems()
    {
        return $this->getIterator()->getHasMoreItems();
    }

    public function getTotalNumItems()
    {
        return $this->getIterator()->getTotalNumItems();
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        if (null === $this->iterator) {
            $this->iterator = $this->createIterator();
        }

        return $this->iterator;
    }

    /**
     * Gets the skip count
     *
     * @return  skip count
     */
    protected function getSkipCount()
    {
        return $this->skipCount;
    }

    /**
     * Gets the page fetcher
     *
     * @return  page fetcher
     */
    protected function getPageFetcher()
    {
        return $this->pageFetcher;
    }

    /**
     * Construct the iterator
     *
     * @return  iterator
     */
    protected abstract function createIterator();
}