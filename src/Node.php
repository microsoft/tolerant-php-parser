<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser;

use PhpParser\Node\Script;
use PhpParser\Token;

class Node implements \JsonSerializable {
    /** @var array[] Map from node class to array of child keys */
    private static $childNames = [];

    /** @var Node | null */
    public $parent;

    public function __construct(int $kind) {
    }

    public function getKind() {
        // TODO remove all references to getKind
        $constants = (new \ReflectionClass("PhpParser\\NodeKind"))->getConstants();
        $nameParts = explode("\\",get_class($this));
        return $constants[end($nameParts)];
    }

    /**
     * Gets start position of Node, not including leading comments and whitespace.
     * @return int
     * @throws \Exception
     */
    public function getStart() : int {
        $child = iterator_to_array($this->getChildNodesAndTokens())[0];
        if ($child instanceof Node) {
            return $child->getStart();
        } elseif ($child instanceof Token) {
            return $child->start;
        }
        throw new \Exception("Unknown type in AST");
    }

    /**
     * Gets start position of Node, including leading comments and whitespace
     * @return int
     * @throws \Exception
     */
    public function getFullStart() : int {
        $child = $this->getChildNodesAndTokens()->current();
        if ($child instanceof Node) {
            return $child->getFullStart();
        } elseif ($child instanceof Token) {
            return $child->fullStart;
        }
        throw new \Exception("Unknown type in AST: " . \gettype($child));
    }

    /**
     * Gets parent of current node (returns null if has no parent)
     * @return null|Node
     */
    public function getParent() {
        return $this->parent;
    }

    public function getAncestor($className) {
        $ancestor = $this;
        while (true) {
            $ancestor = $ancestor->parent;
            if ($ancestor == null || $ancestor instanceof $className) {
                return $ancestor;
            }
        }
    }

    /**
     * Gets root of the syntax tree (returns self if has no parents)
     * @return Node
     */
    public function & getRoot() : Node {
        $node = $this;
        while ($node->parent !== null) {
            $node = $node->parent;
        }
        return $node;
    }

    /**
     * Gets generator containing all descendant Nodes and Tokens.
     *
     * @param callable|null $shouldDescendIntoChildrenFn
     * @return \Generator|Node[]|Token[]
     */
    public function getDescendantNodesAndTokens(callable $shouldDescendIntoChildrenFn = null) {
        // TODO - write unit tests to prove invariants
        // (concatenating all descendant Tokens should produce document, concatenating all Nodes should produce document)

        foreach ($this->getChildNodesAndTokens() as $child) {
            if ($child instanceof Node) {
                yield $child;
                if ($shouldDescendIntoChildrenFn == null || $shouldDescendIntoChildrenFn($child)) {
                    foreach ($child->getDescendantNodesAndTokens($shouldDescendIntoChildrenFn) as $subChild) {
                        yield $subChild;
                    }
                }
            } elseif ($child instanceof Token) {
                yield $child;
            }
        }
    }

    /**
     * Gets a generator containing all descendant Nodes.
     * @param callable|null $shouldDescendIntoChildrenFn
     * @return \Generator|Node[]
     */
    public function getDescendantNodes(callable $shouldDescendIntoChildrenFn = null) {
        foreach ($this->getChildNodesAndTokens() as $child) {
            if ($child instanceof Node) {
                yield $child;
                if ($shouldDescendIntoChildrenFn == null || $shouldDescendIntoChildrenFn($child)) {
                    foreach ($child->getDescendantNodes() as $subChild) { // TODO validate invariant - only returns nodes
                        $subChild === null ?: yield $subChild;
                    }
                }
            }
        }
    }

    /**
     * Gets generator containing all descendant Tokens.
     * @param callable|null $shouldDescendIntoChildrenFn
     * @return \Generator | Token[]
     */
    public function getDescendantTokens(callable $shouldDescendIntoChildrenFn = null) {
        foreach ($this->getChildNodesAndTokens() as $child) {
            if ($child instanceof Node) {
                if ($shouldDescendIntoChildrenFn == null || $shouldDescendIntoChildrenFn($child)) {
                    foreach ($child->getDescendantTokens($shouldDescendIntoChildrenFn) as $subChild) {
                        yield $subChild;
                    }
                }
            } elseif ($child instanceof Token) {
                yield $child;
            }
        }
    }

    /**
     * Gets generator containing all child Nodes and Tokens (direct descendants)
     *
     * @return \Generator | Token[] | Node[]
     */
    public function getChildNodesAndTokens() : \Generator {
        foreach ($this->getChildNames() as $name) {
            $val = $this->$name;
            if (\is_string($val)) {
                continue;
            }
            if (\is_array($val)) {
                foreach ($val as $child) {
                    if ($child !== null) {
                        yield $name => $child;
                    }
                }
                continue;
            }
            if ($val !== null) {
                yield $name => $val;
            }
        }
    }

    /**
     * Gets generator containing all child Nodes (direct descendants)
     * @return \Generator | Node[]
     */
    public function getChildNodes() : \Generator {
        foreach ($this->getChildNames() as $name) {
            $val = $this->$name;
            if (\is_array($val)) {
                foreach ($val as $child) {
                    if ($child instanceof Node) {
                        yield $child;
                    }
                }
                continue;
            } elseif ($val instanceof Node) {
                yield $val;
            }
        }
    }

    /**
     * Gets generator containing all child Tokens (direct descendants)
     *
     * @return \Generator|Token[]
     */
    public function getChildTokens() {
        foreach ($this->getChildNames() as $name) {
            $val = $this->$name;
            if (\is_array($val)) {
                foreach ($val as $child) {
                    if ($child instanceof Token) {
                        yield $child;
                    }
                }
                continue;
            } elseif ($val instanceof Token) {
                yield $val;
            }
        }
    }

    /**
     * Gets array of declared child names (cached).
     *
     * This is used as an optimization when iterating over nodes: For direct iteration
     * PHP will create a properties hashtable on the object, thus doubling memory usage.
     * We avoid this by iterating over just the names instead.
     *
     * @return string[]
     */
    public function getChildNames() {
        $class = \get_class($this);
        if (!isset(self::$childNames[$class])) {
            $names = [];
            $reflectionClass = new \ReflectionClass($class);
            foreach ($reflectionClass->getProperties() as $property) {
                if ($property->name === "parent") {
                    continue;
                }

                $names[] = $property->name;
            }
            self::$childNames[$class] = $names;
        }

        return self::$childNames[$class];
    }

    /**
     * Gets width of a Node (not including comment / whitespace trivia)
     *
     * @return int
     */
    public function getWidth() : int {
        $width = 0;
        foreach ($this->getChildNodesAndTokens() as $idx=>$child) {
            $width += $idx === 0 ? $child->getWidth() : $child->getFullWidth();
        }
        return $width;
    }

    /**
     * Gets width of a Node (including comment / whitespace trivia)
     *
     * @return int
     */
    public function getFullWidth() : int {
        $fullWidth = 0;
        foreach ($this->getChildNodesAndTokens() as $idx=>$child) {
            $fullWidth += $child->getFullWidth();
        }
        return $fullWidth;
    }

    /**
     * Gets string representing Node text (not including leading comment + whitespace trivia)
     * @return string
     */
    public function getText() : string {
        $fullText = "";
        $fileContents = $this->getFileContents();
        foreach ($this->getDescendantTokens() as $idx => $child) {
            $fullText .= $idx === 0 ? $child->getText($fileContents) : $child->getFullText($fileContents);
        }
        return $fullText;
    }

    /**
     * Gets full text of Node (including leading comment + whitespace trivia)
     * @return string
     */
    public function getFullText() : string {
        $fullText = "";
        $fileContents = $this->getFileContents();
        foreach ($this->getDescendantTokens() as $child) {
            $fullText .= $child->getFullText($fileContents);
        }
        return $fullText;
    }

    /**
     * Gets string representing Node's leading comment and whitespace text.
     * @return string
     */
    public function getLeadingCommentAndWhitespaceText() : string {
        // TODO re-tokenize comments and whitespace
        $fileContents = $this->getFileContents();
        foreach ($this->getDescendantTokens() as $token) {
            return $token->getLeadingCommentsAndWhitespaceText($fileContents);
        }
    }

    protected function getChildrenKvPairs() {
        $result = array();
        foreach ($this->getChildNames() as $name) {
            $val = $this->$name;
            if (\is_string($val)) {
                continue;
            }

            $result[$name] = $val;
        }
        return $result;
    }

    public function jsonSerialize() {
        $kindName = self::getNodeKindNameFromValue($this->getKind());
        return ["$kindName" => $this->getChildrenKvPairs()];
    }

    /**
     * Gets name of a Node from its raw kind value.
     * @param int $value
     * @return string
     */
    public static function getNodeKindNameFromValue(int $value) : string {
//        return end(explode("\\", get_class($node)));
        $constants = (new \ReflectionClass("PhpParser\\NodeKind"))->getConstants();
        foreach ($constants as $name=>$val) {
            if ($val == $value) {
                return $name;
            }
        }
        return "Unknown Node Kind";
    }

    /**
     * Gets the name of a Node kind.
     * @return string
     */
    public function getNodeKindName() : string {
        return self::getNodeKindNameFromValue($this->getKind());
    }

    /**
     * Get the end index of a Node.
     * @return int
     * @throws \Exception
     */
    public function getEndPosition() {
        // TODO test invariant - start of next node is end of previous node
        if (isset($this->parent)) {
            $parent = $this->parent;
            $siblings = $parent->getChildNodes();
            foreach ($siblings as $idx=>$nextSibling) {
                if (spl_object_hash($nextSibling) === spl_object_hash($this)) {
                    $siblings->next();
                    $nextSibling = $siblings->current();
                    return $nextSibling !== null
                        ? $nextSibling->getFullStart()
                        : $this->getRoot()->endOfFileToken->fullStart;
                }
            }
        } elseif ($this instanceof Script) {
            return $this->endOfFileToken->getEndPosition();
        }
        throw new \Exception("Unhandled node: " );
    }

    public function & getFileContents() : string {
        return $this->getRoot()->fileContents;
    }

    /**
     * Searches descendants to find a Node at the given position.
     *
     * @param $pos
     * @return Node|null
     */
    public function getDescendantNodeAtPosition(int $pos) {
        $descendants = iterator_to_array($this->getDescendantNodes());
        for ($i = \count($descendants) - 1; $i >= 0; $i--) {
            $childNode = $descendants[$i];
            if ($pos >= $childNode->getFullStart() && $pos < $childNode->getEndPosition()) {
                return $childNode;
            }
        }
        return null;
    }

    public function __toString() {
        return $this->getText();
    }
}