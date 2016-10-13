<?php

namespace PhpParser;

require_once('Node.php');

class Parser {

    private $lexer;

    private $currentParseContext;
    public $sourceFile;

    public function __construct($filename) {
        $this->lexer = new Lexer($filename);
    }

    public function parseSourceFile() : Node {
        $this->reset();

        $sourceFile = new Node(NodeKind::SourceFileNode);
        $this->sourceFile = & $sourceFile;
        $sourceFile->children = $this->parseList($sourceFile, ParseContext::SourceElements);
        array_push($sourceFile->children, $this->getCurrentToken());
        $this->advanceToken();

        $sourceFile->parent = null;

        return $sourceFile;
    }

    function reset() {
        $this->advanceToken();

        // Stores the current parse context, which includes the current and enclosing lists.
        $this->currentParseContext = 0;
    }

    /**
     * Parse a list of elements for a given ParseContext until a list terminator associated
     * with that ParseContext is reached. Additionally abort parsing when an element is reached
     * that is invalid in the current context, but valid in an enclosing context. If an element
     * is invalid in both current and enclosing contexts, generate a SkippedToken, and continue.
     * @param $parentNode
     * @param int $listParseContext
     * @return array
     */
    function parseList($parentNode, int $listParseContext) {
        $savedParseContext = $this->currentParseContext;
        $this->updateCurrentParseContext($listParseContext);
        $parseListElementFn = $this->getParseListElementFn($listParseContext);

        $nodeArray = array();
        while (!$this->isListTerminator($listParseContext)) {
            if ($this->isValidListElement($listParseContext, $this->getCurrentToken())) {
                $element = $parseListElementFn($parentNode);
                if ($element instanceof Node) {
                    $element->parent = $parentNode;
                }
                array_push($nodeArray, $element);
                continue;
            }

            // Error handling logic:
            // The current parse context does not know how to handle the current token,
            // so check if the enclosing contexts know what to do. If so, we assume that
            // the list has completed parsing, and return to the enclosing context.
            //
            // Example:
            //     class A {
            //         function foo() {
            //            return;
            //      // } <- MissingToken (generated when we try to "eat" the closing brace)
            //
            //         public function bar() {
            //         }
            //     }
            //
            // In the case above, the Method ParseContext doesn't know how to handle "public", but
            // the Class ParseContext will know what to do with it. So we abort the Method ParseContext,
            // and return to the Class ParseContext. This enables us to generate a tree with a single
            // class that contains two method nodes, even though there was an error present in the first method.
            if ($this->isCurrentTokenValidInEnclosingContexts()) {
                break;
            }

            // None of the enclosing contexts know how to handle the token. Generate a
            // SkippedToken, and continue parsing in the current context.
            // Example:
            //     class A {
            //         function foo() {
            //            return;
            //            & // <- SkippedToken
            //         }
            //     }
            $token = $this->getCurrentToken();
            $token->kind = TokenKind::SkippedToken;
            array_push($nodeArray, $token);
            $this->advanceToken();
        }

        $this->currentParseContext = $savedParseContext;

        return $nodeArray;
    }

    function isListTerminator(int $parseContext) {
        $tokenKind = $this->getCurrentToken()->kind;
        if ($tokenKind === TokenKind::EndOfFileToken) {
            // Being at the end of the file ends all lists.
            return true;
        }

        switch ($parseContext) {
            case ParseContext::ClassMembers:
            case ParseContext::BlockStatements:
                if ($tokenKind === TokenKind::CloseBraceToken) {
                    return true;
                }
                break;
        }
        return false;
    }

    function isValidListElement($context, $token) {
        // TODO
        switch ($context) {
            case ParseContext::SourceElements:
            case ParseContext::BlockStatements:
                return $this->isStatementStart($token);

            case ParseContext::ClassMembers:
                return $this->isClassMemberDeclarationStart($token);
        }
        return false;
    }

    function getParseListElementFn($context) {
        switch ($context) {
            case ParseContext::SourceElements:
                return $this->parseStatement();
            case ParseContext::ClassMembers:
                return $this->parseClassElement();
            case ParseContext::BlockStatements:
                return $this->parseBlockElement();
            default:
                throw new \Exception("Unrecognized parse context");
        }
    }

    /**
     * Aborts parsing list when one of the parent contexts understands something
     * @param ParseContext $context
     * @return bool
     */
    function isCurrentTokenValidInEnclosingContexts() {
        for ($contextKind = 0; $contextKind < ParseContext::Count; $contextKind++) {
            if ($this->isInParseContext($contextKind)) {
                if ($this->isValidListElement($contextKind, $this->getCurrentToken()) || $this->isListTerminator($contextKind)) {
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

    /**
     * Retrieve the current token, and check that it's of the expected TokenKind.
     * If so, advance and return the token. Otherwise return a MissingToken for
     * the expected token.
     * @param int $kind
     * @return Token
     */
    function eat(int $kind) {
        $token = $this->getCurrentToken();
        if ($token->kind === $kind) {
            $this->advanceToken();
            return $token;
        }
        return new Token(TokenKind::MissingToken, $token->fullStart, $token->fullStart, 0);
    }

    private $token;

    function getCurrentToken() : Token {
        return $this->token;
    }

    function advanceToken() {
        $this->token = $this->lexer->scanNextToken();
    }

    function updateCurrentParseContext($context) {
        $this->currentParseContext |= 1 << $context;
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

    function parseClassDeclaration($parentNode) : Node {
        $node = new Node(NodeKind::ClassNode);
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::ClassKeyword));
        array_push($node->children, $this->eat(TokenKind::Name));
        array_push($node->children, $this->parseClassMembers($node));
        $node->parent = $parentNode;
        return $node;
    }

    function parseClassMembers($parentNode) : Node {
        $node = new Node(NodeKind::ClassMembersNode);
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::OpenBraceToken));
        $this->array_push_list($node->children, $this->parseList($node, ParseContext::ClassMembers));
        array_push($node->children, $this->eat(TokenKind::CloseBraceToken));
        $node->parent = $parentNode;
        return $node;
    }

    function parseMethodDeclaration($parentNode) {
        $node = new Node(NodeKind::MethodNode);
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
        $node = new Node(NodeKind::MethodBlockNode);
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::OpenBraceToken));
        $this->array_push_list($node->children, $this->parseList($node, ParseContext::BlockStatements));
        array_push($node->children, $this->eat(TokenKind::CloseBraceToken));
        $node->parent = $parentNode;
        return $node;
    }

    function array_push_list(& $array, $list) {
        foreach ($list as $item) {
            array_push($array, $item);
        }
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

    function isStatementStart(Token $token) {
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
                return $this->isExpressionStart($token);
        }
    }

    function isExpressionStart($token) {
        // TODO
        return false;
    }
}

class ParseContext {
    const SourceElements = 0;
    const BlockStatements = 1;
    const ClassMembers = 2;
    const Count = 3;
}