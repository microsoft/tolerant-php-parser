<?php
declare(strict_types = 1);

namespace Microsoft\PhpParser\Iterator;

use Microsoft\PhpParser\{Node, Token};

/**
 * An Iterator to the descendants of a Node
 */
class NodeIterator implements \RecursiveIterator {

    /**
     * The Node being iterated
     *
     * @var Node
     */
    private $node;

    /**
     * The current index in the CHILD_NAMES array
     *
     * @var int
     */
    private $childNamesIndex;

    /**
     * The length of the CHILD_NAMES array for the current node
     *
     * @var int
     */
    private $childNamesLength;

    /**
     * The current index of value at the current child name, if the value is an array.
     * Otherwise null.
     *
     * @var int|null
     */
    private $valueIndex;

    /**
     * The length of the array at the current child name, if the value is an array.
     * Otherwise null.
     *
     * @var int|null
     */
    private $valueLength;

    private $childNames;

    private $childName;

    /**
     * @param Node $node The node that should be iterated
     */
    public function __construct(Node $node) {
        $this->node = $node;
        $this->childNames = $node::CHILD_NAMES;
        $this->childNamesLength = \count($node::CHILD_NAMES);
    }

    /**
     * Rewinds the Iterator to the beginning
     *
     * @return void
     */
    public function rewind() {
        $this->childNamesIndex = -1;
        $this->next();
    }

    /**
     * Returns `true` if `current()` can be called to get the current child.
     * Returns `false` if this Node has no more children (direct descendants).
     *
     * @return bool
     */
    public function valid() {
        return $this->childNamesIndex < $this->childNamesLength;
    }

    /**
     * Returns the current child name being iterated.
     * Multiple values may have the same key.
     *
     * @return string
     */
    public function key() {
        return $this->childName;
    }

    /**
     * Returns the current child (direct descendant)
     *
     * @return Node|Token
     */
    public function current() {
        if ($this->valueIndex === null) {
            return $this->node->{$this->childName};
        } else {
            return $this->node->{$this->childName}[$this->valueIndex];
        }
    }

    /**
     * Advances the Iterator to the next child (direct descendant)
     *
     * @return void
     */
    public function next() {
        if ($this->valueIndex === $this->valueLength) {
            // If not iterating value array or finished with it, go to next child name
            $this->childNamesIndex++;
            if ($this->childNamesIndex === $this->childNamesLength) {
                // If child names index is invalid, become invalid
                return;
            }
            $this->childName = $this->childNames[$this->childNamesIndex];
            $value = $this->node->{$this->childName};
            // If new value is null or empty array, skip it
            if (empty($value)) {
                $this->next();
            } else if (\is_array($value)) {
                // If new value is an array, start index at 0
                $this->valueIndex = 0;
                $this->valueLength = \count($value);
            } else {
                // Else reset everything to null
                $this->valueIndex = null;
                $this->valueLength = null;
            }
        } else {
            // Else go to next item in value array
            $this->valueIndex++;
            // If new value is null or empty array, skip it
            if (empty($this->node->{$this->childName}[$this->valueIndex])) {
                $this->next();
            }
        }
    }

    /**
     * Returns true if the current child is another Node (not a Token)
     * and can be used to create another NodeIterator
     *
     * @return bool
     */
    public function hasChildren(): bool {
        return $this->current() instanceof Node;
    }

    /**
     * Returns a NodeIterator for the children of the current child Node
     *
     * @return NodeIterator
     */
    public function getChildren() {
        return new NodeIterator($this->current());
    }
}
