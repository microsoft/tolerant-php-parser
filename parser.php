<?php

namespace PhpParser;

class Parser {

    private $lexer;

    private $tokensArray;
    private $pos;
    private $currentParseContext;

    public function __construct() {
        $this->lexer = new Lexer();
    }

    public function parseSourceFile($filename) : SourceFileNode {
        $this->reset();

        // TODO parser should drive scanner one token at a time, but
        // for now it's easier to debug if we're just passing around the array
        $this->tokensArray = $this->lexer->getTokensArray($filename);

        $sourceFile = new SourceFileNode();
        $sourceFile->children = $this->parseList($sourceFile, ParseContext::SourceElements, $this->parseStatement());
        $sourceFile->parent = null;

        return $sourceFile;
    }

    function reset() {
        $this->pos = 0;

        // Stores the current parse context, which includes the current and enclosing lists.
        $this->currentParseContext = 0;

    }

    function parseList($parentNode, int $nextParseContext, $parseElementFn) {
        $savedParseContext = $this->currentParseContext;
        $this->updateCurrentParseContext($nextParseContext);

        $nodeArray = array();
        while (!$this->isListTerminator($nextParseContext)) {
            if ($this->isListElement($nextParseContext)) {
                $element = $this->parseListElement($parseElementFn);
                if ($element instanceof Node) {
                    $element->parent = $parentNode;
                }
                array_push($nodeArray, $element);
                $this->advanceToken();
                continue;
            }

            if ($this->isCurrentTokenValidInEnclosingContexts()) {
                break;
            }
            $token = $this->getCurrentToken();
            $token->kind = TokenKind::SkippedToken;
            array_push($nodeArray, $token);
            $this->advanceToken();
        }

        array_push($nodeArray, $this->getCurrentToken());

        $this->currentParseContext = $savedParseContext;

        return $nodeArray;
    }

    function parseStatement() {
        return function() {
            switch($this->getCurrentToken()->kind) {
                case TokenKind::ClassKeyword:
                    return $this->parseClassDeclaration();
                default:
                    return $this->getCurrentToken();
            }
        };
    }

    function parseClassDeclaration() {
        $node = new ClassNode();
        $node->children = array($this->getCurrentToken());
        return $node;
    }

    /**
     * Aborts parsing list when one of the parent contexts understands something
     * @param ParseContext $context
     * @return bool
     */
    function isCurrentTokenValidInEnclosingContexts() {
        for ($contextKind = 0; $contextKind < ParseContext::Count; $contextKind++) {
            if ($this->isInParseContext($contextKind)) {
                if ($this->isListElement($contextKind) || $this->isListTerminator($contextKind)) {
                    return true;
                }
            }
        }
        return false;
    }

    function isInParseContext($contextToCheck) {
        $this->currentParseContext;
        return ($this->currentParseContext & (1 << $contextToCheck));
    }

    function eat(TokenKind $kind) {
        if ($this->getCurrentToken() === $kind) {
            return true;
        }
        return false;
    }

    function getCurrentToken() : Token {
        return $this->tokensArray[$this->pos];
    }

    function advanceToken() {
        $this->pos++;
    }

    function getCurrentParseContext() {
        global $currentParseContext;
        return $currentParseContext;
    }

    function updateCurrentParseContext($context) {
        $this->currentParseContext |= 1 << $context;
    }

    function parseListElement($parseElementFn) {
        // TODO
        return $parseElementFn();
    }

    function isListElement($context) {
        // TODO
        switch ($context) {
            case ParseContext::SourceElements:
                return $this->getCurrentToken()->kind === TokenKind::ClassKeyword;
        }
        return false;
    }

    function isListTerminator(int $parseContext) {
        if ($this->getCurrentToken()->kind === TokenKind::EndOfFileToken) {
            // Being at the end of the file ends all lists.
            return true;
        }

        switch ($parseContext) {
            default:
                return false;
        }
    }
}

class SourceFileNode extends Node {
    public function __construct() {
        $this->kind = NodeKind::SourceFileNode;
    }
}

class ClassNode extends Node {

}

class BlockNode extends Node {

}

class StatementNode extends Node {
}

class MethodNode extends Node {

}

class Node {
    public $kind;
    public $parent;
    public $children;

    public function getLength() {
        $length = 0;

        foreach ($this->children as $child) {
            if ($child instanceof Node) {
                $length += $child->getLength();
            } else if ($child instanceof Token) {
                $length += $child->length;
            }
        }
        return $length;
    }

    public function getAllChildren() {
        $allChildren = array();

        foreach ($this->children as $child) {
            if ($child instanceof Node) {
                array_push($allChildren, $child);
                foreach ($child->getAllChildren() as $subChild) {
                    array_push($allChildren, $subChild);
                }
            } else if ($child instanceof Token) {
                array_push($allChildren, $child);
            }
        }
        return $allChildren;
    }

    public function getStart() {
        $child = $this->children[0];
        if ($child instanceof Node) {
            return $child->getStart();
        } else if ($child instanceof Token) {
            return $child->start;
        }
        throw new \Exception("Unknown type in AST");
    }
}

class NodeKind {
    const SourceFileNode = 0;
    const ClassNode = 1;
    const BlockNode = 2;
}


class ParseContext {
    const SourceElements = 0;
    const BlockStatements = 1;
    const Count = 2;
}

/*
 * Expected tree
NODE Class
- TOKEN ClassKeyword
- TOKEN Name
- TOKEN OpenBrace
- NODE BLOCK
    - NODE Method
      - TOKEN Function
      - TOKEN Name
      - TOKEN OpenParen
      - TOKEN NameVar
      - TOKEN CloseParen
      - TOKEN OpenBrace
      - NODE Block

 */