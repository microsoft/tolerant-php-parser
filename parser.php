<?php

namespace PhpParser;

class Parser {

    private $lexer;

    private $tokensArray;
    private $pos;
    private $currentParseContext;
    public $sourceFile;


    public function __construct() {
        $this->lexer = new Lexer();
    }

    public function parseSourceFile($filename) : SourceFileNode {
        $this->reset();

        // TODO parser should drive scanner one token at a time, but
        // for now it's easier to debug if we're just passing around the array
        $this->tokensArray = $this->lexer->getTokensArray($filename);

        $sourceFile = new SourceFileNode(file_get_contents($filename));
        $this->sourceFile = & $sourceFile;
        $sourceFile->children = $this->parseList($sourceFile, ParseContext::SourceElements, $this->parseStatement());
        array_push($sourceFile->children, $this->getCurrentToken());
        $this->advanceToken();

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
            if ($this->isListElement($nextParseContext, $this->getCurrentToken())) {
                $element = $this->parseListElement($parseElementFn, $parentNode);
                if ($element instanceof Node) {
                    $element->parent = $parentNode;
                }
                array_push($nodeArray, $element);
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

        $this->currentParseContext = $savedParseContext;

        return $nodeArray;
    }

    function parseStatement() {
        return function($parentNode) {
            switch($this->getCurrentToken()->kind) {
                case TokenKind::ClassKeyword:
                    return $this->parseClassDeclaration($parentNode);
                default:
                    $token = $this->getCurrentToken();
                    $this->advanceToken();
                    return $token;
            }
        };
    }

    function parseClassDeclaration($parentNode) : ClassNode {
        $node = new ClassNode();
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::ClassKeyword));
        array_push($node->children, $this->eat(TokenKind::Name));
        array_push($node->children, $this->parseClassMembers($node));
        $node->parent = $parentNode;
        return $node;
    }

    function parseClassMembers($parentNode) : ClassMembersNode {
        $node = new ClassMembersNode();
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::OpenBraceToken));
        $this->array_push_list($node->children, $this->parseList($node, ParseContext::ClassMembers, $this->parseClassElement()));
        array_push($node->children, $this->eat(TokenKind::CloseBraceToken));
        $node->parent = $parentNode;
        return $node;
    }

    function array_push_list(& $array, $list) {
        foreach ($list as $item) {
            array_push($array, $item);
        }
    }

    function parseBlockElement() {
        return function() {
            switch($this->getCurrentToken()->kind) {
                default:
                    $token = $this->getCurrentToken();
                    $this->advanceToken();
                    return $token;
            }
        };
    }

    function parseClassElement() {
        return function($parentNode) {
            switch($this->getCurrentToken()->kind) {
                case (TokenKind::FunctionKeyword):
                    return $this->parseMethodDeclaration($parentNode);
                default:
                    $token = $this->getCurrentToken();
                    $this->advanceToken();
                    return $token;
            }
        };
    }

    function parseMethodDeclaration($parentNode) {
        $node = new MethodNode();
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::FunctionKeyword));
        array_push($node->children, $this->eat(TokenKind::Name));
        array_push($node->children, $this->eat(TokenKind::OpenParenToken));
        array_push($node->children, $this->eat(TokenKind::VariableName));
        array_push($node->children, $this->eat(TokenKind::CloseParenToken));
        array_push($node->children, $this->parseMethodBlock($node));
        $node->parent = $parentNode;
        return $node;
    }

    function parseMethodBlock($parentNode) {
        $node = new MethodBlockNode();
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::OpenBraceToken));
        $this->array_push_list($node->children, $this->parseList($node, ParseContext::BlockStatements, $this->parseBlockElement()));
        array_push($node->children, $this->eat(TokenKind::CloseBraceToken));
        $node->parent = $parentNode;
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
                if ($this->isListElement($contextKind, $this->getCurrentToken()) || $this->isListTerminator($contextKind)) {
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

    function eat(int $kind) {
        $token = $this->getCurrentToken();
        if ($token->kind === $kind) {
            $this->advanceToken();
            return $token;
        }
        return new Token(TokenKind::MissingToken, $token->fullStart, $token->fullStart, 0);
    }

    function getCurrentToken() : Token {
        return $this->tokensArray[$this->pos];
    }

    function advanceToken() {
        $this->pos++;
    }
    
    function updateCurrentParseContext($context) {
        $this->currentParseContext |= 1 << $context;
    }

    function parseListElement($parseElementFn, $parentNode) {
        // TODO
        return $parseElementFn($parentNode);
    }

    function isListElement($context, $token) {
        // TODO
        switch ($context) {
            case ParseContext::SourceElements:
            case ParseContext::BlockStatements:
                return $this->isStartOfStatement($token);

            case ParseContext::ClassMembers:
                return $this->isClassMemberDeclarationStart($token);
        }
        return false;
    }

    function isClassMemberDeclarationStart(Token $token) {
        switch ($token->kind) {
            // const-modifier
            case TokenKind::ConstKeyword:

            // visibility-modifier
            case TokenKind::PublicKeyword:
            case TokenKind::ProtectedKeyword:
            case TokenKind::PrivateKeyword:

            // static-modifier
            case TokenKind::StaticKeyword:

            // class-modifier
            case TokenKind::AbstractKeyword:
            case TokenKind::FinalKeyword:

            case TokenKind::VarKeyword:

            case TokenKind::FunctionKeyword:

            case TokenKind::UseKeyword:
                return true;

        }

        return false;
    }

    function isStartOfStatement(Token $token) {
        // https://github.com/php/php-langspec/blob/master/spec/19-grammar.md#statements
        switch ($token->kind) {
            // Compound Statements
            case TokenKind::OpenBraceToken:

            // Labeled Statements
            case TokenKind::Name:
            case TokenKind::CaseKeyword:
            case TokenKind::DefaultKeyword:

            // Expression Statements
            case TokenKind::SemicolonToken:
            case TokenKind::IfKeyword:
            case TokenKind::SwitchKeyword:

            // Iteration Statements
            case TokenKind::WhileKeyword:
            case TokenKind::DoKeyword:
            case TokenKind::ForKeyword:
            case TokenKind::ForeachKeyword:

            // Jump Statements
            case TokenKind::GotoKeyword:
            case TokenKind::ContinueKeyword:
            case TokenKind::BreakKeyword:
            case TokenKind::ReturnKeyword:
            case TokenKind::ThrowKeyword:

            // The try Statement
            case TokenKind::TryKeyword:

            // The declare Statement
            case TokenKind::DeclareKeyword:

            // const-declaration
            case TokenKind::ConstKeyword:

            // function-definition
            case TokenKind::FunctionKeyword:

            // class-declaration
            case TokenKind::ClassKeyword:
            case TokenKind::AbstractKeyword:
            case TokenKind::FinalKeyword:

            // interface-declaration
            case TokenKind::InterfaceKeyword:

            // trait-declaration
            case TokenKind::TraitKeyword:

            // namespace-definition
            case TokenKind::NamespaceKeyword:

            // namespace-use-declaration
            case TokenKind::UseKeyword:

            // global-declaration
            case TokenKind::GlobalKeyword:

            // function-static-declaration
            case TokenKind::StaticKeyword:
                return true;

            default:
                return $this->isStartOfExpression($token);
        }
    }

    function isStartOfExpression($token) {
        // TODO
        return false;
    }

    function isListTerminator(int $parseContext) {
        $tokenKind = $this->getCurrentToken()->kind;
        if ($tokenKind === TokenKind::EndOfFileToken) {
            // Being at the end of the file ends all lists.
            return true;
        }

        switch ($parseContext) {
            case ParseContext::SourceElements:
            case ParseContext::ClassMembers:
                if ($tokenKind === TokenKind::CloseBraceToken) {
                    return true;
                }
                break;
        }
        return false;
    }
}


class SourceFileNode extends Node {
    public $document;
    public function __construct($document) {
        $this->document = $document;
        $this->kind = NodeKind::SourceFileNode;
    }
}

class MethodBlockNode extends Node {

}

class ClassMembersNode extends Node {

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
    const ClassMembers = 2;
    const Count = 3;
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