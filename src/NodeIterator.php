<?php
declare(strict_types = 1);

namespace Microsoft\PhpParser;

/**
 * An Iterator to walk a Node and its descendants
 */
class NodeIterator implements \RecursiveIterator {

    /**
     * Iterator used to iterate the child names of a Node
     *
     * @var Iterator
     */
    private $childNamesIterator;

    /**
     * Iterator used to iterate the child nodes at the current child name
     *
     * @var Iterator|null
     */
    private $valueIterator;

    /**
     * @param Node $node The node that should be iterated
     */
    public function __construct(Node $node) {
        $this->node = $node;
        $this->childNamesIterator = new \ArrayIterator($node::CHILD_NAMES);
        $this->valueIterator = new \EmptyIterator();
    }

    /**
     * Rewinds the Iterator to the beginning
     *
     * @return void
     */
    public function rewind() {
        // Start child names from beginning
        $this->childNamesIterator->rewind();
        // Begin new children until found a valid one
        while ($this->childNamesIterator->valid()) {
            $this->beginChild();
            if ($this->valueIterator->valid()) {
                break;
            }
            $this->childNamesIterator->next();
        }
    }

    /**
     * Returns `true` if `current()` can be called to get the current child.
     * Returns `false` if this Node has no more children (direct descendants).
     */
    public function valid() {
        return $this->childNamesIterator->valid() && $this->valueIterator->valid();
    }

    /**
     * Returns the current child name being iterated.
     * Multiple values may have the same key.
     *
     * @return string
     */
    public function key() {
        return $this->childNamesIterator->current();
    }

    /**
     * Returns the current child (direct descendant)
     *
     * @return Node|Token
     */
    public function current() {
        return $this->valueIterator->current();
    }

    /**
     * Advances the Iterator to the next child (direct descendant)
     *
     * @return void
     */
    public function next() {
        // Go to next value of current child name
        $this->valueIterator->next();
        // Begin new children until found a valid one
        while (!$this->valueIterator->valid() && $this->childNamesIterator->valid()) {
            $this->childNamesIterator->next();
            if (!$this->childNamesIterator->valid()) {
                return;
            }
            $this->beginChild();
        }
    }

    /**
     * Initializes the Iterator for iterating the values of the current child name
     *
     * @return void
     */
    private function beginChild() {
        $value = $this->node->{$this->childNamesIterator->current()};
        // Skip null values
        if ($value === null) {
            $this->valueIterator = new \EmptyIterator();
            return;
        }
        if (!is_array($value)) {
            $value = [$value];
        }
        $this->valueIterator = new \ArrayIterator($value);
    }

    /**
     * Returns true if the current child is another Node (not a Token)
     * and can be used to create another NodeIterator
     *
     * @return bool
     */
    public function hasChildren(): bool {
        return $this->valueIterator->current() instanceof Node;
    }

    /**
     * Returns a NodeIterator for the children of the current Node
     *
     * @return NodeIterator
     */
    public function getChildren() {
        return new NodeIterator($this->valueIterator->current());
    }
}
