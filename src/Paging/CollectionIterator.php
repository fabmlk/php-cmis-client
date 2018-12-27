<?php

namespace Dkd\PhpCmis\Paging;

/**
 * @link https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-impl/src/main/java/org/apache/chemistry/opencmis/client/runtime/util/CollectionIterator.java
 */
class CollectionIterator extends AbstractIterator
{
    /**
     * @inheritdoc
     */
    public function valid()
    {
        $page = $this->getCurrentPage();
        if (null === $page) {
            return false;
        }

        $items = $page->getItems();
        if (empty($items)) {
            return false;
        }

        if ($this->getSkipOffset() < count($items)) {
            return true;
        }

        if (!$this->getHasMoreItems()) {
            return false;
        }

        $totalItems = $this->getTotalNumItems();
        if ($totalItems < 0) {
            // "Some repositories always return this number, some repositories never return it,
            // and some repositories return it sometimes. If the repository didn’t provide this number,
            // getTotalNumItems returns -1"
            return true; // we don't know better
        }

        return ($this->getSkipCount() + $this->getSkipOffset()) < $totalItems;
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->incrementSkipOffset();
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        $page = $this->getCurrentPage();
        if (null === $page) {
            return null;
        }
        $items = $page->getItems();
        if (empty($items)) {
            return null;
        }
        if ($this->getSkipOffset() === count($items)) {
            $page = $this->incrementPage();
            $items = $page ? $page->getItems() : null;
        }
        if (empty($items) || $this->getSkipOffset() === count($items)) {
            return null;
        }

        return $items[$this->getSkipOffset()];
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->getSkipOffset();
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        // TODO: Implement rewind() method.
    }
}