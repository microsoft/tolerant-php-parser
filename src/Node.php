<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser;

//require_once (__DIR__ . "/../NodeKind.php");

use PhpParser\Node\Script;
use PhpParser\Token;

class Node implements \JsonSerializable {
    /** @var int */
    public $kind;
    /** @var Node | null */
    public $parent;

    public function __construct(int $kind) {
        $this->kind = $kind;
    }

    /**
     * Gets the root of the syntax tree.
     */
    public function getRoot() {
        $node = $this;
        while ($node->parent !== null) {
            $node = $node->parent;
        }
        return $node;
    }

    /**
     * Gets a list of all descendant Nodes and Tokens.
     *
     * TODO - write unit tests to prove invariants
     * (concatenating all descendant Tokens should produce document, concatenating all Nodes should produce document)
     *
     * @param callable|null $shouldDescendIntoChildrenFn
     * @return \Generator|Node[]|Token[]
     */
    public function getDescendantNodesAndTokens(callable $shouldDescendIntoChildrenFn = null) {
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
     * Returns all descendant Nodes.
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
     * Returns all descendant Tokens.
     * @param callable|null $shouldDescendIntoChildrenFn
     * @return \Generator | Token[]
     */
    public function & getDescendantTokens(callable $shouldDescendIntoChildrenFn = null) {
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
     * Gets a list of child Nodes and Tokens (direct descendants)
     */
    public function getChildNodesAndTokens() {
        foreach (\call_user_func('get_object_vars', $this) as $i=>$val) {
            if ($i === "parent" || $i == "kind" || \is_string($val)) {
                continue;
            }
            if (\is_array($val)) {
                foreach ($val as $child) {
                    $child === null ?: yield $child;
                }
                continue;
            }
            $val === null ?: yield $val;
        }
    }

    /**
     * Gets a list of child Nodes (direct descendants)
     */
    public function & getChildNodes() {
        foreach (\call_user_func('get_object_vars', $this) as $i=>$val) {
            if ($i === "parent" || $i == "kind") {
                continue;
            }
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
     * Gets a list of child Tokens (direct descendants)
     *
     * @return \Generator|Token[]
     */
    public function getChildTokens() {
        foreach (\call_user_func('get_object_vars', $this) as $i=>$val) {
            if ($i === "parent" || $i == "kind") {
                continue;
            }
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
     * Returns the length of a Node (including trivia)
     */
    public function getLength() {
        $length = 0;

        foreach ($this->getChildNodesAndTokens() as $child) {
            if ($child instanceof Node) {
                $length += $child->getLength();
            } elseif ($child instanceof Token) {
                $length += $child->length;
            }
        }
        return $length;
    }

    protected function getChildrenKvPairs() {
        $result = array();
        foreach (\call_user_func('get_object_vars', $this) as $i=>$val) {
            if ($i === "parent" || $i == "kind" || \is_string($val)) {
                continue;
            }

            $result[$i] = $val;
        }
        return $result;
    }

    public function getStart() {
        $child = $this->getChildNodesAndTokens()[0];
        if ($child instanceof Node) {
            return $child->getStart();
        } elseif ($child instanceof Token) {
            return $child->start;
        }
        throw new \Exception("Unknown type in AST");
    }

    public function getFullStart() {
        $child = $this->getChildNodesAndTokens()->current();
        if ($child instanceof Node) {
            return $child->getFullStart();
        } elseif ($child instanceof Token) {
            return $child->fullStart;
        }
        throw new \Exception("Unknown type in AST: " . \gettype($child));
    }

    public function jsonSerialize() {
        $kindName = self::getNodeKindFromValue($this->kind);
        return ["$kindName" => $this->getChildrenKvPairs()];
    }

    public static function getNodeKindFromValue(int $value) {
        $constants = (new \ReflectionClass("PhpParser\\NodeKind"))->getConstants();
        foreach ($constants as $name=>$val) {
            if ($val == $value) {
                return $name;
            }
        }
        return -1;
    }

    public function getNodeKindName() {
        return self::getNodeKindFromValue($this->kind);
    }

    public function getEnd() {
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
            return $this->endOfFileToken->getEnd();
        }
        throw new \Exception("Unhandled node: " );
    }

    public function & getFileContents() : string {
        return $this->getRoot()->fileContents;
    }

    /**
     *
     * @param $pos
     * @return Node|null
     */
    public function getNodeAtPosition($pos) {
        $descendants = iterator_to_array($this->getDescendantNodes());
        for ($i = \count($descendants) - 1; $i >= 0; $i--) {
            $childNode = $descendants[$i];
            if ($pos >= $childNode->getFullStart() && $pos < $childNode->getEnd()) {
                return $childNode;
            }
        }
        return null;
    }

    public function getFullTextForNode() {
        $fullText = "";
        $fileContents = $this->getFileContents();
        foreach ($this->getDescendantTokens() as & $child) {
            $fullText .= $child->getFullTextForToken($fileContents);
        }
        return $fullText;
    }

    public function getTextForNode() {
        $fullText = "";
        $fileContents = $this->getFileContents();
        foreach ($this->getDescendantTokens() as $idx=> & $child) {
            $fullText .= $idx === 0 ? $child->getTextForToken($fileContents) : $child->getFullTextForToken($fileContents);
        }
        return $fullText;
    }

    public function getTriviaForNode() {
        $fileContents = $this->getFileContents();
        foreach ($this->getDescendantTokens() as $token) {
            return $token->getTriviaForToken($fileContents);
        }
    }
}