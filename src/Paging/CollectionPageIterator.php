<?php

namespace Dkd\PhpCmis\Paging;

/**
 * https://github.com/apache/chemistry-opencmis/blob/trunk/chemistry-opencmis-client/chemistry-opencmis-client-impl/src/main/java/org/apache/chemistry/opencmis/client/runtime/util/CollectionPageIterator.java
 */
class CollectionPageIterator extends AbstractIterator
{
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
        if (empty($items) || $this->getSkipOffset() === count($items)) {
            return null;
        }

        return $items[$this->getSkipOffset()];
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
    public function key()
    {
        $this->getSkipOffset();
    }

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
        if (null === $items || $this->getSkipOffset() >= count($items)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        // TODO: Implement rewind() method.
    }
}