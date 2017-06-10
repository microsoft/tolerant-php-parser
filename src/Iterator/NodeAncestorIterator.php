<?php
declare(strict_types = 1);

namespace Microsoft\PhpParser\Iterator;

use Microsoft\PhpParser\Node;

/**
 * An Iterator to walk the ancestors of a Node up to the root
 */
class NodeAncestorIterator implements \Iterator {

    /**
     * @var Node
     */
    private $start;

    /**
     * @var Node
     */
    private $current;

    /**
     * @param Node $node The node to start with
     */
    public function __construct(Node $node) {
        $this->start = $node;
    }

    /**
     * Rewinds the Iterator to the beginning
     *
     * @return void
     */
    public function rewind() {
        $this->current = $this->start;
    }

    /**
     * Returns `true` if `current()` can be called to get the current node.
     * Returns `false` if the last Node was the root node.
     *
     * @return bool
     */
    public function valid() {
        return $this->current !== null;
    }

    /**
     * Always returns null.
     *
     * @return null
     */
    public function key() {
        return null;
    }

    /**
     * Returns the current Node
     *
     * @return Node
     */
    public function current() {
        return $this->current;
    }

    /**
     * Advances the Iterator to the parent of the current Node
     *
     * @return void
     */
    public function next() {
        $this->current = $this->current->parent;
    }
}
