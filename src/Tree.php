<?php
/**
 * Ce fichier fait partie du package Tms.
 *
 *  Pour les informations complètes de copyright et de licence,
 *  veuillez vous référer au fichier LICENSE distribué avec ce code source.
 */

namespace Dkd\PhpCmis;

/**
 * Basic tree structure.
 */
class Tree implements TreeInterface
{
    /**
     * @var mixed
     */
    private $item;

    /**
     * @var array
     */
    private $children;

    /**
     * Tree constructor.
     *
     * @param mixed $item
     * @param array $children
     */
    public function __construct($item, array $children)
    {
        $this->item = $item;
        $this->children = $children;
    }

    /**
     * Returns the children.
     *
     * @return TreeInterface[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Returns the item on this level.
     *
     * @return mixed
     */
    public function getItem()
    {
        return $this->item;
    }
}
