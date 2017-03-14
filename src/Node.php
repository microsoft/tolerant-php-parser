<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser;

use Microsoft\PhpParser\Node\NamespaceUseGroupClause;
use Microsoft\PhpParser\Node\SourceFileNode;
use Microsoft\PhpParser\Node\Statement\NamespaceDefinition;
use Microsoft\PhpParser\Node\Statement\NamespaceUseDeclaration;

class Node implements \JsonSerializable {
    /** @var array[] Map from node class to array of child keys */
    private static $childNames = [];

    /** @var Node | null */
    public $parent;

    public function getNodeKindName() : string {
        // Use strrpos (rather than explode) to avoid creating a temporary array.
        return substr(static::class, strrpos(static::class, "\\") + 1);
    }

    /**
     * Gets start position of Node, not including leading comments and whitespace.
     * @return int
     * @throws \Exception
     */
    public function getStart() : int {
        $child = $this->getChildNodesAndTokens()->current();
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

    /**
     * Get's first ancestor that is an instance of one of the provided classes.
     * Returns null if there is no match.
     *
     * @param array ...$classNames
     * @return Node|null
     */
    public function getFirstAncestor(...$classNames) {
        $ancestor = $this;
        while (($ancestor = $ancestor->parent) !== null) {
            foreach ($classNames as $className) {
                if ($ancestor instanceof $className) {
                    return $ancestor;
                }
            }
        }
        return null;
    }

    /**
     * Get's first child that is an instance of one of the provided classes.
     * Returns null if there is no match.
     *
     * @param array ...$classNames
     * @return Node|null
     */
    public function getFirstChildNode(...$classNames) {
        foreach ($this->getChildNodes() as $child) {
            foreach ($classNames as $className) {
                if ($child instanceof $className) {
                    return $child;
                }
            }
        }
        return null;
    }

    /**
     * Get's first descendant node that is an instance of one of the provided classes.
     * Returns null if there is no match.
     *
     * @param array ...$classNames
     * @return Node|null
     */
    public function getFirstDescendantNode(...$classNames) {
        foreach ($this->getDescendantNodes() as $descendant) {
            foreach ($classNames as $className) {
                if ($descendant instanceof $className) {
                    return $descendant;
                }
            }
        }
        return null;
    }

    /**
     * Gets root of the syntax tree (returns self if has no parents)
     * @return Node
     */
    public function getRoot() : Node {
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
                    yield from $child->getDescendantNodesAndTokens($shouldDescendIntoChildrenFn);
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
                    yield from $child->getDescendantNodes();
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
                    yield from $child->getDescendantTokens($shouldDescendIntoChildrenFn);
                }
            } elseif ($child instanceof Token) {
                yield $child;
            }
        }
    }

    /**
     * Gets generator containing all child Nodes and Tokens (direct descendants).
     * Does not return null elements.
     *
     * @return \Generator | Token[] | Node[]
     */
    public function getChildNodesAndTokens() : \Generator {
        foreach ($this->getChildNames() as $name) {
            $val = $this->$name;

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
//        $width = 0;
        $first = $this->getStart();
        $last = $this->getEndPosition();

        return $last - $first;
//        foreach ($this->getChildNodesAndTokens() as $child) {
//            if ($first) {
//                $width += $child->getWidth();
//                $first = false;
//            } else {
//                $width += $child->getFullWidth();
//            }
//        }
//        return $width;
    }

    /**
     * Gets width of a Node (including comment / whitespace trivia)
     *
     * @return int
     */
    public function getFullWidth() : int {
        $first = $this->getFullStart();
        $last = $this->getEndPosition();

        return $last - $first;
//        $fullWidth = 0;
//        foreach ($this->getChildNodesAndTokens() as $idx=>$child) {
//            $fullWidth += $child->getFullWidth();
//        }
//        return $fullWidth;
    }

    /**
     * Gets string representing Node text (not including leading comment + whitespace trivia)
     * @return string
     */
    public function getText() : string {
        $start = $this->getStart();
        $end = $this->getEndPosition();

//       $fullText = "";
        $fileContents = $this->getFileContents();
        return \substr($fileContents, $start, $end - $start);
        // $start = $this->getStart();
        // $end = $this->getEndPosition();

        // $fullText = \substr($fileContents, $start, $end - $start);
        /*$first = true;
        foreach ($this->getDescendantTokens() as $child) {
            if ($first) {
                $fullText .= $child->getText($fileContents);
                $first = false;
            } else {
                $fullText .= $child->getFullText($fileContents);
            }
        }
        return $fullText;*/
    }

    /**
     * Gets full text of Node (including leading comment + whitespace trivia)
     * @return string
     */
    public function getFullText() : string {
        $start = $this->getFullStart();
        $end = $this->getEndPosition();

//       $fullText = "";
        $fileContents = $this->getFileContents();
        return \substr($fileContents, $start, $end - $start);

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
            $result[$name] = $this->$name;
        }
        return $result;
    }

    public function jsonSerialize() {
        $kindName = $this->getNodeKindName();
        return ["$kindName" => $this->getChildrenKvPairs()];
    }

    /**
     * Get the end index of a Node.
     * @return int
     * @throws \Exception
     */
    public function getEndPosition() {
        // TODO test invariant - start of next node is end of previous node
        if ($this instanceof SourceFileNode) {
            return $this->endOfFileToken->getEndPosition();
        } else {
            for ($i = \count($childKeys = $this->getChildNames()) - 1; $i >= 0; $i--) {
                $lastChildKey = $childKeys[$i];
 //                var_dump($lastChildKey);
                $lastChild = $this->$lastChildKey;

                if (\is_array($lastChild)) {
                    $lastChild = \end($lastChild);
                    if ($lastChild === null) {
                        var_dump($lastChild);
                    }
                }

                if ($lastChild instanceof Token) {
                    return $lastChild->getEndPosition();
                } elseif ($lastChild instanceof Node) {
                    return $lastChild->getEndPosition();
                }
            }
//            $childKeys = $this->getChildNames();
//            $children = iterator_to_array($this->getChildNodesAndTokens(), false);
//            $lastChild = \end($children);
//            if ($lastChild instanceof Token) {
//                return $lastChild->getEndPosition();
//            } elseif ($lastChild instanceof Node) {
//                return $lastChild->getEndPosition();
//            }
        }

        throw new \Exception("Unhandled node type");
    }

    public function getFileContents() : string {
        // TODO consider renaming to getSourceText
        return $this->getRoot()->fileContents;
    }

    public function getUri() : string {
        return $this->getRoot()->uri;
    }

    /**
     * Searches descendants to find a Node at the given position.
     *
     * @param $pos
     * @return Node|null
     */
    public function getDescendantNodeAtPosition(int $pos) {
        $descendants = iterator_to_array($this->getDescendantNodes(), false);
        for ($i = \count($descendants) - 1; $i >= 0; $i--) {
            $childNode = $descendants[$i];
            $start = $childNode->getStart();
            if ($pos >= $start && $pos <= $childNode->getEndPosition()) {
                return $childNode;
            }
        }
        return null;
    }

    /**
     * Gets leading PHP Doc Comment text corresponding to the current Node.
     * Returns last doc comment in leading comment / whitespace trivia,
     * and returns null if there is no preceding doc comment.
     *
     * @return string | null
     */
    public function getDocCommentText() {
        $leadingTriviaText = $this->getLeadingCommentAndWhitespaceText();
        $leadingTriviaTokens = PhpTokenizer::getTokensArrayFromContent(
            $leadingTriviaText, ParseContext::SourceElements, $this->getFullStart(), false
        );
        for ($i = \count($leadingTriviaTokens) - 1; $i >= 0; $i--) {
            $token = $leadingTriviaTokens[$i];
            if ($token->kind === TokenKind::DocCommentToken) {
                return $token->getText($this->getFileContents());
            }
        }
        return null;
    }

    public function __toString() {
        return $this->getText();
    }

    /**
     * @return array | ResolvedName[][]
     * @throws \Exception
     */
    public function getImportTablesForCurrentScope($namespaceDefinition = null) {
        $namespaceDefinition = $namespaceDefinition ?? $this->getNamespaceDefinition();

        // Use declarations can exist in either the global scope, or inside namespace declarations.
        // http://php.net/manual/en/language.namespaces.importing.php#language.namespaces.importing.scope
        //
        // The only code allowed before a namespace declaration is a declare statement, and sub-namespaces are
        // additionally unaffected by by import rules of higher-level namespaces. Therefore, we can make the assumption
        // that we need not travel up the spine any further once we've found the current namespace.
        // http://php.net/manual/en/language.namespaces.definition.php
        if ($namespaceDefinition instanceof NamespaceDefinition) {
            $topLevelNamespaceStatements = $namespaceDefinition->compoundStatementOrSemicolon instanceof Token
                ? $namespaceDefinition->parent->statementList // we need to start from the namespace definition.
                : $namespaceDefinition->compoundStatementOrSemicolon->statements;
        } else {
            $topLevelNamespaceStatements = $this->getRoot()->statementList;
        }

        // TODO optimize performance
        // Currently we rebuild the import tables on every call (and therefore every name resolution operation)
        // It is likely that a consumer will attempt many consecutive name resolution requests within the same file.
        // Therefore, we can consider optimizing on the basis of the "most recently used" import table set.
        // The idea: Keep a single set of import tables cached based on a unique root node id, and invalidate
        // cache whenever we attempt to resolve a qualified name with a different root node.
        //
        // In order to make this work, it will probably make sense to change the way we parse namespace definitions.
        // https://github.com/Microsoft/tolerant-php-parser/issues/81
        //
        // Currently the namespace definition only includes a compound statement or semicolon token as one if it's children.
        // Instead, we should move to a model where we parse future statements as a child rather than as a separate
        // statement. This would enable us to retrieve all the information we would need to find the fully qualified
        // name by simply traveling up the spine to find the first ancestor of type NamespaceDefinition.
        $namespaceImportTable = $functionImportTable = $constImportTable = [];
        $contents = $this->getFileContents();

        foreach ($topLevelNamespaceStatements as $useDeclaration) {
            if ($useDeclaration instanceof NamespaceDefinition) {
                // TODO - another reason to always parse namespace definitions as the parent of subsequent statements.
                break;
            } elseif (!($useDeclaration instanceof NamespaceUseDeclaration)) {
                continue;
            }

            // TODO - also handle NamespaceUseDeclarationList
            if ($useDeclaration->groupClauses !== null) {
                // use A\B{C, D} => multiple imports
                $imports = \array_filter($useDeclaration->groupClauses->children, function($value) {
                    return $value instanceof NamespaceUseGroupClause;
                });
            } else {
                // use A\B\C; => single import
                $imports = [$useDeclaration];
            }

            $namespaceNamePartsPrefix =
                $useDeclaration->namespaceName !== null ? $useDeclaration->namespaceName->nameParts : [];

            foreach ($imports as $import) {
                if ($useDeclaration->groupClauses !== null) {
                    // use A\B\C\{D\E};                 namespace import: ["E" => [A,B,C,D,E]]
                    // use A\B\C\{D\E as F};            namespace import: ["F" => [A,B,C,D,E]]
                    // use function A\B\C\{A, B}        function import: ["A" => [A,B,C,A], "B" => [A,B,C]]
                    // use function A\B\C\{const A}     const import: ["A" => [A,B,C,A]]
                    $alias = $import->namespaceName->getLastNamePart()->getText($contents);
                    $namespaceNameParts = \array_merge($namespaceNamePartsPrefix, $import->namespaceName->nameParts);
                    $functionOrConst = $import->functionOrConst ?? $useDeclaration->functionOrConst;
                } else {
                    // use A\B\C;               namespace import: ["C" => [A,B,C]]
                    // use A\B\C as D;          namespace import: ["D" => [A,B,C]]
                    // use function A\B\C as D  function import: ["D" => [A,B,C]]
                    // use A\B, C\D;            namespace import: ["B" => [A,B], "D" => [C,D]]
                    $alias = $useDeclaration->namespaceAliasingClause === null
                        ? $useDeclaration->namespaceName->getLastNamePart()->getText($contents)
                        : $useDeclaration->namespaceAliasingClause->name->getText($contents);

                    $namespaceNameParts = $namespaceNamePartsPrefix;
                    $functionOrConst = $useDeclaration->functionOrConst;
                }

                // Add the alias and resolved name to the corresponding namespace, function, or const import table.
                // If the alias already exists, it will get replaced by the most recent using.
                // TODO - worth throwing an error here instead?
                if ($alias !== null) {
                    if ($functionOrConst === null) {
                        // namespaces are case-insensitive
                        $alias = \strtolower($alias);
                        $namespaceImportTable[$alias] = ResolvedName::buildName($namespaceNameParts, $contents);
                    } elseif ($functionOrConst->kind === TokenKind::FunctionKeyword) {
                        // functions are case-insensitive
                        $alias = \strtolower($alias);
                        $functionImportTable[$alias] = ResolvedName::buildName($namespaceNameParts, $contents);
                    } elseif ($functionOrConst->kind === TokenKind::ConstKeyword) {
                        // constants are case-sensitive
                        $constImportTable[$alias] = ResolvedName::buildName($namespaceNameParts, $contents);
                    }
                }
            }
        }

        return [$namespaceImportTable, $functionImportTable, $constImportTable];
    }

    /**
     * Gets corresponding NamespaceDefinition for Node. Returns null if in global namespace.
     *
     * @return NamespaceDefinition | null
     */
    public function getNamespaceDefinition() {
        $namespaceDefinition = $this instanceof NamespaceDefinition
            ? $this
            : $this->getFirstAncestor(NamespaceDefinition::class, SourceFileNode::class);

        if ($namespaceDefinition instanceof NamespaceDefinition && !($namespaceDefinition->parent instanceof SourceFileNode)) {
            $namespaceDefinition = $namespaceDefinition->getFirstAncestor(SourceFileNode::class);
        }

        if ($namespaceDefinition === null) {
            // TODO provide a way to throw errors without crashing consumer
            throw new \Exception("Invalid tree - SourceFileNode must always exist at root of tree.");
        }

        if ($namespaceDefinition instanceof SourceFileNode) {
            $namespaceDefinition = $namespaceDefinition->getFirstChildNode(NamespaceDefinition::class);
            if ($namespaceDefinition !== null && $namespaceDefinition->getFullStart() > $this->getFullStart()) {
                $namespaceDefinition = null;
            }
        }

        return $namespaceDefinition;
    }

    public function getPreviousSibling() {
        // TODO make more efficient
        $parent = $this->parent;
        if ($parent === null) {
            return null;
        }
        $siblingEnd = $this->getFullStart() - 1;
        $siblings = iterator_to_array($parent->getChildNodes());
        for ($i = \count($siblings) - 1; $i >= 0; $i--) {
            $sibling = $siblings[$i];
            if ($siblingEnd <= $sibling->getEndPosition() && $siblingEnd >= $sibling->getFullStart()) {
                return $sibling;
            }
        }
        return null;
    }
}
