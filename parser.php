<?php



namespace PhpParser;

// TODO make this less hacky
spl_autoload_register(function ($class) {
    if (file_exists($filepath = __DIR__ . "/Node/" . basename($class) . ".php")) {
        require_once $filepath;
    } else if (file_exists($filepath = __DIR__ . "/" . basename($class) . ".php")) {
        require_once $filepath;
    }
});

use PhpParser\Node\CaseStatementNode;
use PhpParser\Node\CatchClause;
use PhpParser\Node\ClassMembersNode;
use PhpParser\Node\ClassNode;
use PhpParser\Node\BreakOrContinueStatement;
use PhpParser\Node\DeclareDirective;
use PhpParser\Node\DeclareStatement;
use PhpParser\Node\DelimitedList;
use PhpParser\Node\DoStatement;
use PhpParser\Node\ElseClauseNode;
use PhpParser\Node\ElseIfClauseNode;
use PhpParser\Node\EmptyStatementNode;
use PhpParser\Node\Expression;
use PhpParser\Node\FinallyClause;
use PhpParser\Node\ForeachKey;
use PhpParser\Node\ForeachStatement;
use PhpParser\Node\ForeachValue;
use PhpParser\Node\ForStatement;
use PhpParser\Node\Function_;
use PhpParser\Node\FunctionDefinition;
use PhpParser\Node\CompoundStatementNode;
use PhpParser\Node\GotoStatement;
use PhpParser\Node\IfStatementNode;
use PhpParser\Node\MethodNode;
use PhpParser\Node\NamedLabelStatementNode;
use PhpParser\Node\Node;
use PhpParser\Node\Parameter;
use PhpParser\Node\QualifiedName;
use PhpParser\Node\RelativeSpecifier;
use PhpParser\Node\ReturnStatement;
use PhpParser\Node\Script;
use PhpParser\Node\ScriptSection;
use PhpParser\Node\SwitchStatementNode;
use PhpParser\Node\TemplateExpressionNode;
use PhpParser\Node\ThrowStatement;
use PhpParser\Node\TryStatement;
use PhpParser\Node\WhileStatement;

class Parser {

    private $lexer;

    private $currentParseContext;
    public $sourceFile;

    public function __construct($filename) {
        $this->lexer = new Lexer($filename);
    }

    public function parseSourceFile() : Node {
        $this->reset();

        $sourceFile = new Script();
        $this->sourceFile = & $sourceFile;
        $sourceFile->scriptSectionList = array();
        while ($this->getCurrentToken()->kind !== TokenKind::EndOfFileToken) {
            array_push($sourceFile->scriptSectionList, $this->parseScriptSection($sourceFile));
        }
        $this->sourceFile->endOfFileToken = $this->eat(TokenKind::EndOfFileToken);
        $this->advanceToken();

        $sourceFile->parent = null;

        return $sourceFile;
    }

    function parseScriptSection($parent) {
        // TODO - for the sake of simplicity, this doesn't actually match the spec.
        // The spec defines a script-section to be:
        //     text_opt start-tag statement-list_opt end-tag_opt text_opt
        //
        // However, currently, a script section does not include the trailing text_opt.
        // Consider changing this in the future.
        $scriptSection = new ScriptSection();
        $scriptSection->parent = $parent;
        $token = $this->getCurrentToken();
        $scriptSection->text =
            new Token(
                TokenKind::ScriptSectionPrependedText,
                $token->fullStart,
                $token->fullStart,
                0
            );

        while ($token->kind !== TokenKind::EndOfFileToken) {
            if ($token->kind === TokenKind::ScriptSectionStartTag) {
                $scriptSection->startTag = $this->eat(TokenKind::ScriptSectionStartTag);
                $preTextLength = $scriptSection->startTag->start - $scriptSection->startTag->fullStart;
                $scriptSection->startTag->length -= $preTextLength;
                $scriptSection->startTag->fullStart += $preTextLength;
                $scriptSection->text->length += $preTextLength;
                $scriptSection->statementList = $this->parseList($scriptSection, ParseContext::SourceElements);
                $scriptSection->endTag = $this->eatOptional(TokenKind::ScriptSectionEndTag);
                break;
            }

            $scriptSection->text->length += $token->length;

            $this->advanceToken();
            $token = $this->getCurrentToken();
        }

        return $scriptSection;
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
            case ParseContext::SourceElements:
                return $tokenKind === TokenKind::ScriptSectionEndTag;

            case ParseContext::ClassMembers:
            case ParseContext::BlockStatements:
            case ParseContext::SwitchStatementElements:
                return $tokenKind === TokenKind::CloseBraceToken || $tokenKind === TokenKind::EndSwitchKeyword;
            case ParseContext::IfClause2Elements:
                return
                    $tokenKind === TokenKind::ElseIfKeyword ||
                    $tokenKind === TokenKind::EndIfKeyword;

            case ParseContext::WhileStatementElements:
                Return $tokenKind === TokenKind::EndWhileKeyword;

            case ParseContext::CaseStatementElements:
                return
                    $tokenKind === TokenKind::CaseKeyword ||
                    $tokenKind === TokenKind::DefaultKeyword;

            case ParseContext::ForStatementElements:
                return
                    $tokenKind === TokenKind::EndForKeyword;

            case ParseContext::ForeachStatementElements:
                return $tokenKind === TokenKind::EndForEachKeyword;

            case ParseContext::DeclareStatementElements:
                return $tokenKind === TokenKind::EndDeclareKeyword;
        }
        // TODO warn about unhandled parse context
        return false;
    }

    function isValidListElement($context, Token $token) {

        // TODO
        switch ($context) {
            case ParseContext::SourceElements:
            case ParseContext::BlockStatements:
            case ParseContext::IfClause2Elements:
            case ParseContext::CaseStatementElements:
            case ParseContext::WhileStatementElements:
            case ParseContext::ForStatementElements:
            case ParseContext::ForeachStatementElements:
            case ParseContext::DeclareStatementElements:
                return $this->isStatementStart($token);

            case ParseContext::ClassMembers:
                return $this->isClassMemberDeclarationStart($token);

            case ParseContext::SwitchStatementElements:
                return
                    $token->kind === TokenKind::CaseKeyword ||
                    $token->kind === TokenKind::DefaultKeyword;
        }
        return false;
    }

    function getParseListElementFn($context) {
        switch ($context) {
            case ParseContext::SourceElements:
            case ParseContext::BlockStatements:
            case ParseContext::IfClause2Elements:
            case ParseContext::CaseStatementElements:
            case ParseContext::WhileStatementElements:
            case ParseContext::ForStatementElements:
            case ParseContext::ForeachStatementElements:
            case ParseContext::DeclareStatementElements:
                return $this->parseStatementFn();
            case ParseContext::ClassMembers:
                return $this->parseClassElement();

            case ParseContext::SwitchStatementElements:
                return $this->parseCaseOrDefaultStatement();
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
    function eat(...$kinds) {
        $token = $this->getCurrentToken();
        foreach ($kinds as $kind) {
            if ($token->kind === $kind) {
                $this->advanceToken();
                return $token;
            }
        }
        return new Token(TokenKind::MissingToken, $token->fullStart, $token->fullStart, 0);
    }

    function eatOptional(...$kinds) {
        $token = $this->getCurrentToken();
        if (in_array($token->kind, $kinds)) {
            $this->advanceToken();
            return $token;
        }
        return null;
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

    function parseStatement($parentNode) {
        return ($this->parseStatementFn())($parentNode);
    }

    function parseStatementFn() {
        return function($parentNode) {
            switch($this->getCurrentToken()->kind) {
                // compound-statement
                case TokenKind::OpenBraceToken:
                    return $this->parseCompoundStatement($parentNode);

                // labeled-statement
                case TokenKind::Name:
                    if ($this->lookahead(TokenKind::Name, TokenKind::ColonToken)) {
                        return $this->parseNamedLabelStatement($parentNode);
                    }
                    break;

                // selection-statement
                case TokenKind::IfKeyword:
                    return $this->parseIfStatement($parentNode);
                case TokenKind::SwitchKeyword:
                    return $this->parseSwitchStatement($parentNode);

                // iteration-statement
                case TokenKind::WhileKeyword: // while-statement
                    return $this->parseWhileStatement($parentNode);
                case TokenKind::DoKeyword: // do-statement
                    return $this->parseDoStatement($parentNode);
                case TokenKind::ForKeyword: // for-statement
                    return $this->parseForStatement($parentNode);
                case TokenKind::ForeachKeyword: // foreach-statement
                    return $this->parseForeachStatement($parentNode);

                // jump-statement
                case TokenKind::GotoKeyword: // goto-statement
                    return $this->parseGotoStatement($parentNode);
                case TokenKind::ContinueKeyword: // continue-statement
                case TokenKind::BreakKeyword: // break-statement
                    return $this->parseBreakOrContinueStatement($parentNode);
                case TokenKind::ReturnKeyword: // return-statement
                    return $this->parseReturnStatement($parentNode);
                case TokenKind::ThrowKeyword: // throw-statement
                    return $this->parseThrowStatement($parentNode);

                case TokenKind::TryKeyword: // try-statement
                    return $this->parseTryStatement($parentNode);

                case TokenKind::DeclareKeyword: // declare-statement
                    return $this->parseDeclareStatement($parentNode);

                // function-declaration
                case TokenKind::FunctionKeyword:
                    return $this->parseFunctionDeclaration($parentNode);

                // class-declaration
                case TokenKind::ClassKeyword:
                    return $this->parseClassDeclaration($parentNode);

                case TokenKind::TemplateStringStart:
                    return $this->parseTemplateString($parentNode);

                case TokenKind::SemicolonToken:
                    return $this->parseEmptyStatement($parentNode);
            }

            return $this->parsePrimaryExpression($parentNode);
        };
    }

    function parseClassElement() {
        return function($parentNode) {
            switch($this->getCurrentToken()->kind) {
                case TokenKind::PrivateKeyword:
                case TokenKind::PublicKeyword:
                case TokenKind::ProtectedKeyword:

                case (TokenKind::FunctionKeyword):
                    return $this->parseMethodDeclaration($parentNode);
                default:
                    $token = $this->getCurrentToken(); // TODO new unsupported token
                    $this->advanceToken();
                    return $token;
            }
        };
    }

    function parseBlockElement() {
        return function() {
            switch($this->getCurrentToken()->kind) {
                default:
                    $token = $this->getCurrentToken(); // TODO new unsupported token
                    $this->advanceToken();
                    return $token;
            }
        };
    }

    function parseClassDeclaration($parentNode) : Node {
        $node = new ClassNode();
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::ClassKeyword));
        array_push($node->children, $this->eat(TokenKind::Name));
        array_push($node->children, $this->parseClassMembers($node));
        $node->parent = $parentNode;
        return $node;
    }

    function parseClassMembers($parentNode) : Node {
        $node = new ClassMembersNode();
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::OpenBraceToken));
        $this->array_push_list($node->children, $this->parseList($node, ParseContext::ClassMembers));
        array_push($node->children, $this->eat(TokenKind::CloseBraceToken));
        $node->parent = $parentNode;
        return $node;
    }

    function parseFunctionDeclaration($parentNode) {
        $node = new Function_();
        $this->parseFunctionDefinition($node);
        $node->parent = $parentNode;
        return $node;
    }

    function parseMethodDeclaration($parentNode) {
        $node = new MethodNode();
        $node->modifiers = $this->parseModifiers();
        $this->parseFunctionDefinition($node);
        $node->parent = $parentNode;
        return $node;
    }

    function parseParameterFn() {
        return function ($parentNode) {
            $node = new Parameter();
            $node->parent = $parentNode;
            $node->typeOpt = $this->parseQualifiedName($node);
            if (!isset($node->typeOpt)) {
                $node->typeOpt = $this->eatOptional(
                TokenKind::ArrayKeyword, TokenKind::CallableKeyword, TokenKind::BoolReservedWord,
                TokenKind::FloatReservedWord, TokenKind::IntReservedWord, TokenKind::StringReservedWord);
            }
            $node->byRefToken = $this->eatOptional(TokenKind::AmpersandToken);
            $node->variableName = $this->eat(TokenKind::VariableName);
            $node->equalsToken = $this->eatOptional(TokenKind::EqualsToken);
            if ($node->equalsToken !== null) {
                $node->default = $this->parseConstantExpression($node);
            }
                return $node;
//            }
//            return null;
        };
    }

    function parseCompoundStatement($parentNode) {
        $node = new CompoundStatementNode();
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
//            case TokenKind::CaseKeyword: // TODO update spec
//            case TokenKind::DefaultKeyword:

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
        return ($this->isExpressionStartFn())($token);
    }

    function isExpressionStartFn() {
        return function($token) {
            switch($token->kind) {
                case TokenKind::NoSubstitutionTemplateLiteral:
                case TokenKind::TemplateStringStart:
                case TokenKind::VariableName:
                    return true;
            }
            return false;
        };
    }

    private function parseTemplateString($parentNode) {
        $templateNode = new TemplateExpressionNode();
        $templateNode->parent = $parentNode;
        $templateNode->children = array();
        do {
            array_push($templateNode->children, $this->getCurrentToken());
            $this->advanceToken();
            $token = $this->getCurrentToken();

            if ($token->kind === TokenKind::VariableName) {
                array_push($templateNode->children, $token);
                // $this->advanceToken();
                // $token = $this->getCurrentToken();
                $this->token = $this->lexer->reScanTemplateToken($token);
                $token = $this->getCurrentToken();
            }
        } while ($token->kind === TokenKind::TemplateStringMiddle);

        array_push($templateNode->children, $this->eat(TokenKind::TemplateStringEnd));
        return $templateNode;
    }

    private function isPrimaryExpressionStart() {
        switch ($this->getCurrentToken()) {
            // variable-name
            case TokenKind::VariableName: // TODO special case $this

            // qualified-name
            case TokenKind::QualifiedName: // TODO Qualified name

            // literal
            case TokenKind::DecimalLiteralToken: // TODO merge dec, oct, hex, bin, float -> NumericLiteral
            case TokenKind::OctalLiteralToken:
            case TokenKind::HexadecimalLiteralToken:
            case TokenKind::BinaryLiteralToken:
            case TokenKind::FloatingLiteralToken:
            case TokenKind::InvalidOctalLiteralToken:
            case TokenKind::InvalidHexadecimalLiteral:
            case TokenKind::InvalidBinaryLiteral:

            case TokenKind::StringLiteralToken: // TODO merge unterminated
            case TokenKind::UnterminatedStringLiteralToken:
            case TokenKind::NoSubstitutionTemplateLiteral:
            case TokenKind::UnterminatedNoSubstitutionTemplateLiteral:

            case TokenKind::TemplateStringStart: //TODO - parse this as an expression


            // TODO constant-expression

            // intrinsic-construct
            case TokenKind::EchoKeyword:
            case TokenKind::ListKeyword:
            case TokenKind::UnsetKeyword:

            // intrinsic-operator
            case TokenKind::ArrayKeyword:
            case TokenKind::EmptyKeyword:
            case TokenKind::EvalKeyword:
            case TokenKind::ExitKeyword:
            case TokenKind::DieKeyword:
            case TokenKind::IsSetKeyword:
            case TokenKind::PrintKeyword:

            // anonymous-function-creation-expression
            case TokenKind::StaticKeyword:
            case TokenKind::FunctionKeyword:

            // ( expression )
            case TokenKind::OpenParenToken:
                return true; // TODO
        }
        return false;
    }

    private function parsePrimaryExpression($parentNode) {
        $token = $this->getCurrentToken();
        switch ($token->kind) {
           /* // variable-name
            case TokenKind::VariableName: // TODO special case $this
                return $this->parseVariableNameExpression($parentNode);

                // qualified-name
            case TokenKind::QualifiedName: // TODO Qualified name
                return $this->parseQualifiedNameExpression($parentNode);

                // literal
            case TokenKind::DecimalLiteralToken: // TODO merge dec, oct, hex, bin, float -> NumericLiteral
            case TokenKind::OctalLiteralToken:
            case TokenKind::HexadecimalLiteralToken:
            case TokenKind::BinaryLiteralToken:
            case TokenKind::FloatingLiteralToken:
            case TokenKind::InvalidOctalLiteralToken:
            case TokenKind::InvalidHexadecimalLiteral:
            case TokenKind::InvalidBinaryLiteral:

            case TokenKind::StringLiteralToken: // TODO merge unterminated
            case TokenKind::UnterminatedStringLiteralToken:
            case TokenKind::NoSubstitutionTemplateLiteral:
            case TokenKind::UnterminatedNoSubstitutionTemplateLiteral:
                return $this->parseLiteralExpression($parentNode);


            case TokenKind::TemplateStringStart:
                return $this->parseTemplateString($parentNode);


            // TODO constant-expression

                // intrinsic-construct
            case TokenKind::EchoKeyword:
            case TokenKind::ListKeyword:
            case TokenKind::UnsetKeyword:
                return $this->parseIntrinsicConstructExpression($parentNode);

                // intrinsic-operator
            case TokenKind::ArrayKeyword:
            case TokenKind::EmptyKeyword:
            case TokenKind::EvalKeyword:
            case TokenKind::ExitKeyword:
            case TokenKind::DieKeyword:
            case TokenKind::IsSetKeyword:
            case TokenKind::PrintKeyword:
//                return $this->

                // anonymous-function-creation-expression
            case TokenKind::StaticKeyword:
            case TokenKind::FunctionKeyword:

                // ( expression )
            case TokenKind::OpenParenToken:
                return true;*/
            case TokenKind::EndOfFileToken:
                return new Token(TokenKind::MissingToken, $token->fullStart, $token->fullStart, 0);

            default:
                // TODO
                $this->advanceToken();
                return $token;
        }
    }

    private function parseEmptyStatement($parentNode) {
        $node = new EmptyStatementNode();
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::SemicolonToken));
        $node->parent = $parentNode;
        return $node;
    }

    private function parseLiteralExpression($parentNode) {
    }

    private function isMethodModifier($token) {
        switch($token->kind) {
            case TokenKind::AbstractKeyword:
            case TokenKind::FinalKeyword:
            case TokenKind::PublicKeyword:
            case TokenKind::ProtectedKeyword:
            case TokenKind::PrivateKeyword:
            case TokenKind::StaticKeyword:
                return true;
        }
        return false;
    }

    private function parseModifiers() {
        $modifiers = array();
        $token = $this->getCurrentToken();
        while ($this->isMethodModifier($token)) {
            array_push($modifiers, $token);
            $this->advanceToken();
            $token = $this->getCurrentToken();
        }
        return $modifiers;
    }

    private function isParameterStartFn() {
        return function($token) {
            switch ($token->kind) {
                case TokenKind::ArrayKeyword:
                case TokenKind::CallableKeyword:

                case TokenKind::Name: // http://php.net/manual/en/language.namespaces.rules.php
                case TokenKind::BackslashToken:
                case TokenKind::NamespaceKeyword:
                    // All of these can be the start of a qualified name

                case TokenKind::BoolReservedWord:
                case TokenKind::FloatReservedWord:
                case TokenKind::IntReservedWord:
                case TokenKind::StringReservedWord:

                case TokenKind::AmpersandToken:

                case TokenKind::VariableName:
                    return true;
            }
            return false;
        };
    }

    private function parseConstantExpression($parentNode) {
        $node = new Expression();
        $node->parent = $parentNode;
        $node->children = array();
        // TODO
        array_push($node->children, $this->eat(TokenKind::DecimalLiteralToken));
        return $node;
    }

    private function parseDelimitedList($delimeter, $isElementStartFn, $parseElementFn, $parentNode) {
        $node = new DelimitedList();
        $token = $this->getCurrentToken();
        do {
            if (!$isElementStartFn($token)) {
                break;
            }
            $node->addToken($parseElementFn($node));
            $delimeterToken = $this->eatOptional($delimeter);
            if ($delimeterToken !== null) {
                $node->addToken($delimeterToken);
            }
            $token = $this->getCurrentToken();
            // TODO ERROR CASE - no delimeter, but a param follows
        } while ($delimeterToken !== null);


        $node->parent = $parentNode;
        if ($node->children === null) {
            return null;
        }
        return $node;
    }

    private function parseQualifiedName($parentNode) {
        $node = new QualifiedName();
        $node->parent = $parentNode;
        $node->relativeSpecifier = $this->parseRelativeSpecifier($node);
        if (!isset($node->relativeSpecifier)) {
            $node->globalSpecifier = $this->eatOptional(TokenKind::BackslashToken);
        }
        $node->nameParts =
            $this->parseDelimitedList(
                TokenKind::BackslashToken,
                function ($token) {
                    return $token->kind === TokenKind::Name;
                },
                function ($parentNode) {
                    return $this->eat(TokenKind::Name);
                }, $node);
        if ($node->nameParts === null) {
            return null;
        }
        return $node;
    }

    private function parseRelativeSpecifier($parentNode) {
        $node = new RelativeSpecifier();
        $node->parent = $parentNode;
        $node->namespaceKeyword = $this->eatOptional(TokenKind::NamespaceKeyword);
        if ($node->namespaceKeyword !== null) {
            $node->backslash = $this->eat(TokenKind::BackslashToken);
        }
        if (isset($node->backslash)) {
            return $node;
        }
        return null;
    }

    private function parseFunctionDefinition(FunctionDefinition & $node) {
        $node->functionKeyword = $this->eat(TokenKind::FunctionKeyword);
        $node->byRefToken = $this->eatOptional(TokenKind::AmpersandToken);
        $node->name = $this->eat(TokenKind::Name);
        $node->openParen = $this->eat(TokenKind::OpenParenToken);
        $node->parameters = $this->parseDelimitedList(TokenKind::CommaToken, $this->isParameterStartFn(), $this->parseParameterFn(), $node);
        $node->closeParen = $this->eat(TokenKind::CloseParenToken);
        $node->colonToken = $this->eatOptional(TokenKind::ColonToken);
        $node->returnTypeOpt = $this->parseQualifiedName($node);
        if (!isset($node->returnTypeOpt)) {
            $node->returnTypeOpt = $this->eatOptional(
                TokenKind::ArrayKeyword, TokenKind::CallableKeyword, TokenKind::BoolReservedWord,
                TokenKind::FloatReservedWord, TokenKind::IntReservedWord, TokenKind::StringReservedWord);
        }
        $node->compoundStatement = $this->parseCompoundStatement($node);
    }

    private function parseNamedLabelStatement($parentNode) {
        $namedLabelStatement = new NamedLabelStatementNode();
        $namedLabelStatement->parent = $parentNode;
        $namedLabelStatement->name = $this->eat(TokenKind::Name);
        $namedLabelStatement->colon = $this->eat(TokenKind::ColonToken);
        $namedLabelStatement->statement = $this->parseStatement($namedLabelStatement); // TODO this is ugly
        return $namedLabelStatement;
    }

    private function lookahead(int ...$expectedKinds) : bool {
        $startPos = $this->lexer->pos;
        $startToken = $this->getCurrentToken();
        $success = true;
        foreach ($expectedKinds as $kind) {
            if ($this->eatOptional($kind) === null) {
                $success = false;
                break;
            }
        }
        $this->lexer->pos = $startPos;
        $this->token = $startToken;
        return $success;
    }

    private function parseIfStatement($parentNode) {
        $ifStatement = new IfStatementNode();
        $ifStatement->parent = $parentNode;
        $ifStatement->ifKeyword = $this->eat(TokenKind::IfKeyword);
        $ifStatement->openParen = $this->eat(TokenKind::OpenParenToken);
        $ifStatement->expression = $this->parseExpression($ifStatement);
        $ifStatement->closeParen = $this->eat(TokenKind::CloseParenToken);
        if ($this->lookahead(TokenKind::ColonToken)) {
            $ifStatement->colon = $this->eat(TokenKind::ColonToken);
            $ifStatement->statements = $this->parseList($ifStatement, ParseContext::IfClause2Elements);
        } else {
            $ifStatement->statements = $this->parseStatement($ifStatement);
        }
        $ifStatement->elseIfClauses = array(); // TODO - should be some standard for empty arrays vs. null?
        while ($this->lookahead(TokenKind::ElseKeyword, TokenKind::IfKeyword) || $this->lookahead(TokenKind::ElseIfKeyword)) {
            array_push($ifStatement->elseIfClauses, $this->parseElseIfClause($ifStatement));
        }

        if ($this->lookahead(TokenKind::ElseKeyword)) {
            $ifStatement->elseClause = $this->parseElseClause($ifStatement);
        }

        $ifStatement->endifKeyword = $this->eatOptional(TokenKind::EndIfKeyword);
        if ($ifStatement->endifKeyword) {
            $ifStatement->semicolon = $this->eat(TokenKind::SemicolonToken);
        }

        return $ifStatement;
    }

    private function parseElseIfClause($parentNode) {
        $elseIfClause = new ElseIfClauseNode();
        $elseIfClause->parent = $parentNode;
        $elseIfClause->elseIfKeyword = array();
        $firstToken = $this->eatOptional(TokenKind::ElseIfKeyword);
        if ($firstToken !== null) {
            array_push($elseIfClause->elseIfKeyword, $firstToken);
        }
        else {
            array_push($elseIfClause->elseIfKeyword, $this->eat(TokenKind::ElseKeyword));
            array_push($elseIfClause->elseIfKeyword, $this->eat(TokenKind::IfKeyword));
        }
        $elseIfClause->openParen = $this->eat(TokenKind::OpenParenToken);
        $elseIfClause->expression = $this->parseExpression($elseIfClause);
        $elseIfClause->closeParen = $this->eat(TokenKind::CloseParenToken);
        if ($this->lookahead(TokenKind::ColonToken)) {
            $elseIfClause->colon = $this->eat(TokenKind::ColonToken);
            $elseIfClause->statements = $this->parseList($elseIfClause, ParseContext::IfClause2Elements);
        } else {
            $elseIfClause->statements = $this->parseStatement($elseIfClause);
        }
        return $elseIfClause;
    }

    private function parseElseClause($parentNode) {
        $elseClause = new ElseClauseNode();
        $elseClause->parent = $parentNode;
        $elseClause->elseKeyword = $this->eat(TokenKind::ElseKeyword);
        if ($this->lookahead(TokenKind::ColonToken)) {
            $elseClause->colon = $this->eat(TokenKind::ColonToken);
            $elseClause->statements = $this->parseList($elseClause, ParseContext::IfClause2Elements);
        } else {
            $elseClause->statements = $this->parseStatement($elseClause);
        }
        return $elseClause;
    }

    private function parseSwitchStatement($parentNode) {
        $switchStatement = new SwitchStatementNode();
        $switchStatement->parent = $parentNode;
        $switchStatement->switchKeyword = $this->eat(TokenKind::SwitchKeyword);
        $switchStatement->openParen = $this->eat(TokenKind::OpenParenToken);
        $switchStatement->expression = $this->parseExpression($switchStatement);
        $switchStatement->closeParen = $this->eat(TokenKind::CloseParenToken);
        $switchStatement->openBrace = $this->eatOptional(TokenKind::OpenBraceToken);
        $switchStatement->colon = $this->eatOptional(TokenKind::ColonToken);
        $switchStatement->caseStatements = $this->parseList($switchStatement, ParseContext::SwitchStatementElements);
        if ($switchStatement->colon !== null) {
            $switchStatement->endswitch = $this->eat(TokenKind::EndSwitchKeyword);
            $switchStatement->semicolon = $this->eat(TokenKind::SemicolonToken);
        } else {
            $switchStatement->closeBrace = $this->eat(TokenKind::CloseBraceToken);
        }

        return $switchStatement;
    }

    private function parseCaseOrDefaultStatement() {
        return function($parentNode) {
            $caseStatement = new CaseStatementNode();
            $caseStatement->parent = $parentNode;
            // TODO add error checking
            $caseStatement->caseKeyword = $this->eat(TokenKind::CaseKeyword, TokenKind::DefaultKeyword);
            if ($caseStatement->caseKeyword->kind === TokenKind::CaseKeyword) {
                $caseStatement->expression = $this->parseExpression($caseStatement);
            }
            $caseStatement->defaultLabelTerminator = $this->eat(TokenKind::ColonToken, TokenKind::SemicolonToken);
            $caseStatement->statementList = $this->parseList($caseStatement, ParseContext::CaseStatementElements);
            return $caseStatement;
        };
    }

    private function parseWhileStatement($parentNode) {
        $whileStatement = new WhileStatement();
        $whileStatement->parent = $parentNode;
        $whileStatement->whileToken = $this->eat(TokenKind::WhileKeyword);
        $whileStatement->openParen = $this->eat(TokenKind::OpenParenToken);
        $whileStatement->expression = $this->parseExpression($whileStatement);
        $whileStatement->closeParen = $this->eat(TokenKind::CloseParenToken);
        $whileStatement->colon = $this->eatOptional(TokenKind::ColonToken);
        if ($whileStatement->colon !== null) {
            $whileStatement->statements = $this->parseList($whileStatement, ParseContext::WhileStatementElements);
            $whileStatement->endWhile = $this->eat(TokenKind::EndWhileKeyword);
            $whileStatement->semicolon = $this->eat(TokenKind::SemicolonToken);
        } else {
            $whileStatement->statements = $this->parseStatement($whileStatement);
        }
        return $whileStatement;
    }

    function parseExpression($parentNode) {
        return ($this->parseExpressionFn())($parentNode);
    }

    function parseExpressionFn() {
        return function ($parentNode) {
            // TODO currently only parses variable names to help w/ testing, but eventually implement
            $token = $this->getCurrentToken();
            $expression = new Expression();
            $expression->parent = $parentNode;
            $expression->children = array();

            if ($token->kind === TokenKind::VariableName) {
                array_push($expression->children, $token);
            } else {
                array_push($expression->children, new Token(TokenKind::MissingToken, $token->fullStart, $token->fullStart, 0));
                return $expression;
            }
            $this->advanceToken();
            return $expression;
        };
    }

    private function parseDoStatement($parentNode) {
        $doStatement = new DoStatement();
        $doStatement->parent = $parentNode;
        $doStatement->do = $this->eat(TokenKind::DoKeyword);
        $doStatement->statement = $this->parseStatement($doStatement);
        $doStatement->whileToken = $this->eat(TokenKind::WhileKeyword);
        $doStatement->openParen = $this->eat(TokenKind::OpenParenToken);
        $doStatement->expression = $this->parseExpression($doStatement);
        $doStatement->closeParen = $this->eat(TokenKind::CloseParenToken);
        $doStatement->semicolon = $this->eat(TokenKind::SemicolonToken);
        return $doStatement;
    }

    private function parseForStatement($parentNode) {
        $forStatement = new ForStatement();
        $forStatement->parent = $parentNode;
        $forStatement->for = $this->eat(TokenKind::ForKeyword);
        $forStatement->openParen = $this->eat(TokenKind::OpenParenToken);
        $forStatement->forInitializer = $this->parseDelimitedList(TokenKind::CommaToken, $this->isExpressionStartFn(), $this->parseExpressionFn(), $forStatement);
        $forStatement->exprGroupSemicolon1 = $this->eat(TokenKind::SemicolonToken);
        $forStatement->forControl = $this->parseDelimitedList(TokenKind::CommaToken, $this->isExpressionStartFn(), $this->parseExpressionFn(), $forStatement);
        $forStatement->exprGroupSemicolon2 = $this->eat(TokenKind::SemicolonToken);
        $forStatement->forEndOfLoop = $this->parseDelimitedList(TokenKind::CommaToken, $this->isExpressionStartFn(), $this->parseExpressionFn(), $forStatement);
        $forStatement->closeParen = $this->eat(TokenKind::CloseParenToken);
        $forStatement->colon = $this->eatOptional(TokenKind::ColonToken);
        if ($forStatement->colon !== null) {
            $forStatement->statements = $this->parseList($forStatement, ParseContext::ForStatementElements);
            $forStatement->endFor = $this->eat(TokenKind::EndForKeyword);
            $forStatement->endForSemicolon = $this->eat(TokenKind::SemicolonToken);
        } else {
            $forStatement->statements = $this->parseStatement($forStatement);
        }
        return $forStatement;
    }

    private function parseForeachStatement($parentNode) {
        $foreachStatement = new ForeachStatement();
        $foreachStatement->parent = $parentNode;
        $foreachStatement->foreach = $this->eat(TokenKind::ForeachKeyword);
        $foreachStatement->openParen = $this->eat(TokenKind::OpenParenToken);
        $foreachStatement->forEachCollectionName = $this->parseExpression($foreachStatement);
        $foreachStatement->asKeyword = $this->eat(TokenKind::AsKeyword);
        $foreachStatement->foreachKey = $this->tryParseForeachKey($foreachStatement);
        $foreachStatement->foreachValue = $this->parseForeachValue($foreachStatement);
        $foreachStatement->closeParen = $this->eat(TokenKind::CloseParenToken);
        $foreachStatement->colon = $this->eatOptional(TokenKind::ColonToken);
        if ($foreachStatement->colon !== null) {
            $foreachStatement->statements = $this->parseList($foreachStatement, ParseContext::ForeachStatementElements);
            $foreachStatement->endForeach = $this->eat(TokenKind::EndForEachKeyword);
            $foreachStatement->endForeachSemicolon = $this->eat(TokenKind::SemicolonToken);
        } else {
            $foreachStatement->statements = $this->parseStatement($foreachStatement);
        }
        return $foreachStatement;
    }

    private function tryParseForeachKey($parentNode) {
        if (!$this->isExpressionStart($this->getCurrentToken())) {
            return null;
        }

        $startPos = $this->lexer->pos;
        $startToken = $this->getCurrentToken();
        $foreachKey = new ForeachKey();
        $foreachKey->parent = $parentNode;
        $foreachKey->expression = $this->parseExpression($foreachKey);

        if (!$this->lookahead(TokenKind::DoubleArrowToken)) {
            $this->lexer->pos = $startPos;
            $this->token = $startToken;
            return null;
        }

        $foreachKey->arrow = $this->eat(TokenKind::DoubleArrowToken);
        return $foreachKey;
    }

    private function parseForeachValue($parentNode) {
        $foreachValue = new ForeachValue();
        $foreachValue->parent = $parentNode;
        $foreachValue->ampersand = $this->eatOptional(TokenKind::AmpersandToken);
        $foreachValue->expression = $this->parseExpression($foreachValue);
        return $foreachValue;
    }

    private function parseGotoStatement($parentNode) {
        $gotoStatement = new GotoStatement();
        $gotoStatement->parent = $parentNode;
        $gotoStatement->goto = $this->eat(TokenKind::GotoKeyword);
        $gotoStatement->name = $this->eat(TokenKind::Name);
        $gotoStatement->semicolon = $this->eat(TokenKind::SemicolonToken);
        return $gotoStatement;
    }

    private function parseBreakOrContinueStatement($parentNode) {
        // TODO should be error checking if on top level
        $continueStatement = new BreakOrContinueStatement();
        $continueStatement->parent = $parentNode;
        $continueStatement->breakOrContinueKeyword = $this->eat(TokenKind::ContinueKeyword, TokenKind::BreakKeyword);

        // TODO this level of granularity is unnecessary - integer-literal should be sufficient
        $continueStatement->breakoutLevel =
            $this->eatOptional(
                TokenKind::BinaryLiteralToken,
                TokenKind::DecimalLiteralToken,
                TokenKind::InvalidHexadecimalLiteral,
                TokenKind::InvalidBinaryLiteral,
                TokenKind::FloatingLiteralToken,
                TokenKind::HexadecimalLiteralToken,
                TokenKind::OctalLiteralToken,
                TokenKind::InvalidOctalLiteralToken,
                // TODO the parser should be permissive of floating literals, but rule validation should produce error
                TokenKind::FloatingLiteralToken
                );
        $continueStatement->semicolon = $this->eat(TokenKind::SemicolonToken);

        return $continueStatement;
    }

    private function parseReturnStatement($parentNode) {
        $returnStatement = new ReturnStatement();
        $returnStatement->parent = $parentNode;
        $returnStatement->returnKeyword = $this->eat(TokenKind::ReturnKeyword);
        if ($this->isExpressionStart($this->getCurrentToken())) {
            $returnStatement->expression = $this->parseExpression($returnStatement);
        }
        $returnStatement->semicolon = $this->eat(TokenKind::SemicolonToken);

        return $returnStatement;
    }

    private function parseThrowStatement($parentNode) {
        $throwStatement = new ThrowStatement();
        $throwStatement->parent = $parentNode;
        $throwStatement->throwKeyword = $this->eat(TokenKind::ThrowKeyword);
        // TODO error for failures to parse expressions when not optional
        $throwStatement->expression = $this->parseExpression($throwStatement);
        $throwStatement->semicolon = $this->eat(TokenKind::SemicolonToken);

        return $throwStatement;
    }

    private function parseTryStatement($parentNode) {
        $tryStatement = new TryStatement();
        $tryStatement->parent = $parentNode;
        $tryStatement->tryKeyword = $this->eat(TokenKind::TryKeyword);
        $tryStatement->compoundStatement = $this->parseCompoundStatement($tryStatement); // TODO verifiy this is only compound

        $tryStatement->catchClauses = array(); // TODO - should be some standard for empty arrays vs. null?
        while ($this->lookahead(TokenKind::CatchKeyword)) {
            array_push($tryStatement->catchClauses, $this->parseCatchClause($tryStatement));
        }

        if ($this->lookahead(TokenKind::FinallyKeyword)) {
            $tryStatement->finallyClause = $this->parseFinallyClause($tryStatement);
        }

        return $tryStatement;
    }

    private function parseCatchClause($parentNode) {
        $catchClause = new CatchClause();
        $catchClause->parent = $parentNode;
        $catchClause->catch = $this->eat(TokenKind::CatchKeyword);
        $catchClause->openParen = $this->eat(TokenKind::OpenParenToken);
        $catchClause->qualifiedName = $this->parseQualifiedName($catchClause); // TODO generate missing token or error if null
        $catchClause->variableName = $this->eat(TokenKind::VariableName);
        $catchClause->closeParen = $this->eat(TokenKind::CloseParenToken);
        $catchClause->compoundStatement = $this->parseCompoundStatement($catchClause);

        return $catchClause;
    }

    private function parseFinallyClause($parentNode) {
        $finallyClause = new FinallyClause();
        $finallyClause->parent = $parentNode;
        $finallyClause->finallyToken = $this->eat(TokenKind::FinallyKeyword);
        $finallyClause->compoundStatement = $this->parseCompoundStatement($finallyClause);

        return $finallyClause;
    }

    private function parseDeclareStatement($parentNode) {
        $declareStatement = new DeclareStatement();
        $declareStatement->parent = $parentNode;
        $declareStatement->declareKeyword = $this->eat(TokenKind::DeclareKeyword);
        $declareStatement->openParen = $this->eat(TokenKind::OpenParenToken);
        $declareStatement->declareDirective = $this->parseDeclareDirective($declareStatement);
        $declareStatement->closeParen = $this->eat(TokenKind::CloseParenToken);

        if ($this->lookahead(TokenKind::SemicolonToken)) {
            $declareStatement->semicolon = $this->eat(TokenKind::SemicolonToken);
        } else if ($this->lookahead(TokenKind::ColonToken)) {
            $declareStatement->colon = $this->eat(TokenKind::ColonToken);
            $declareStatement->statements = $this->parseList($declareStatement, ParseContext::DeclareStatementElements);
            $declareStatement->enddeclareKeyword = $this->eat(TokenKind::EndDeclareKeyword);
            $declareStatement->semicolon = $this->eat(TokenKind::SemicolonToken);
        } else {
            $declareStatement->statements = $this->parseStatement($declareStatement);
        }

        return $declareStatement;
    }

    private function parseDeclareDirective($parentNode) {
        $declareDirective = new DeclareDirective();
        $declareDirective->parent = $parentNode;
        $declareDirective->name = $this->eat(TokenKind::Name);
        $declareDirective->equals = $this->eat(TokenKind::EqualsToken);
        $declareDirective->literal =
            $this->eat(
                TokenKind::DecimalLiteralToken,
                TokenKind::OctalLiteralToken,
                TokenKind::HexadecimalLiteralToken,
                TokenKind::BinaryLiteralToken,
                TokenKind::FloatingLiteralToken,
                TokenKind::InvalidOctalLiteralToken,
                TokenKind::InvalidHexadecimalLiteral,
                TokenKind::InvalidBinaryLiteral,
                TokenKind::StringLiteralToken,
                TokenKind::UnterminatedStringLiteralToken,
                TokenKind::NoSubstitutionTemplateLiteral,
                TokenKind::UnterminatedNoSubstitutionTemplateLiteral
            ); // TODO simplify

        return $declareDirective;
    }
}

class ParseContext {
    const SourceElements = 0;
    const BlockStatements = 1;
    const ClassMembers = 2;
    const IfClause2Elements = 3;
    const SwitchStatementElements = 4;
    const CaseStatementElements = 5;
    const WhileStatementElements = 6;
    const ForStatementElements = 7;
    const ForeachStatementElements = 8;
    const DeclareStatementElements = 9;
    const Count = 10;
}
