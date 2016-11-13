<?php



namespace PhpParser;

// TODO make this less hacky
spl_autoload_register(function ($class) {
    if (file_exists($filepath = __DIR__ . "/Node/" . basename($class) . ".php")) {
        require_once $filepath;
    } elseif (file_exists($filepath = __DIR__ . "/" . basename($class) . ".php")) {
        require_once $filepath;
    }
});

use PhpParser\Node\ArrayElement;
use PhpParser\Node\ArrayIntrinsicExpression;
use PhpParser\Node\BinaryExpression;
use PhpParser\Node\CaseStatementNode;
use PhpParser\Node\CatchClause;
use PhpParser\Node\ClassMembersNode;
use PhpParser\Node\ClassNode;
use PhpParser\Node\BreakOrContinueStatement;
use PhpParser\Node\DeclareDirective;
use PhpParser\Node\DeclareStatement;
use PhpParser\Node\DelimitedList;
use PhpParser\Node\DoStatement;
use PhpParser\Node\EchoExpression;
use PhpParser\Node\ElseClauseNode;
use PhpParser\Node\ElseIfClauseNode;
use PhpParser\Node\EmptyIntrinsicExpression;
use PhpParser\Node\EmptyStatementNode;
use PhpParser\Node\EvalIntrinsicExpression;
use PhpParser\Node\ExitIntrinsicExpression;
use PhpParser\Node\Expression;
use PhpParser\Node\ExpressionStatement;
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
use PhpParser\Node\IssetIntrinsicExpression;
use PhpParser\Node\ListIntrinsicExpression;
use PhpParser\Node\Literal;
use PhpParser\Node\MethodDeclaration;
use PhpParser\Node\NamedLabelStatementNode;
use PhpParser\Node\Node;
use PhpParser\Node\Parameter;
use PhpParser\Node\ParenthesizedExpression;
use PhpParser\Node\PrintIntrinsicExpression;
use PhpParser\Node\QualifiedName;
use PhpParser\Node\RelativeSpecifier;
use PhpParser\Node\ReturnStatement;
use PhpParser\Node\Script;
use PhpParser\Node\ScriptSection;
use PhpParser\Node\StatementNode;
use PhpParser\Node\SwitchStatementNode;
use PhpParser\Node\TemplateExpressionNode;
use PhpParser\Node\ThrowStatement;
use PhpParser\Node\TryStatement;
use PhpParser\Node\UnaryOpExpression;
use PhpParser\Node\UnknownExpression;
use PhpParser\Node\UnsetIntrinsicExpression;
use PhpParser\Node\Variable;
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
            $token = $this->getCurrentToken();
            switch($token->kind) {
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

                // try-statement
                case TokenKind::TryKeyword:
                    return $this->parseTryStatement($parentNode);

                // declare-statement
                case TokenKind::DeclareKeyword:
                    return $this->parseDeclareStatement($parentNode);

                // function-declaration
                case TokenKind::FunctionKeyword:
                    return $this->parseFunctionDeclaration($parentNode);

                // class-declaration
                case TokenKind::ClassKeyword:
                    return $this->parseClassDeclaration($parentNode);

                case TokenKind::SemicolonToken:
                    return $this->parseEmptyStatement($parentNode);
            }

            $expressionStatement = new ExpressionStatement();
            $expressionStatement->parent = $parentNode;
            $expressionStatement->expression = $this->parseExpression($expressionStatement, true);
            $expressionStatement->semicolon = $this->eat(TokenKind::SemicolonToken);
            return $expressionStatement;
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
        $functionNode = new Function_();
        $this->parseFunctionDefinition($functionNode);
        $functionNode->parent = $parentNode;
        return $functionNode;
    }

    function parseMethodDeclaration($parentNode) {
        $methodDeclaration = new MethodDeclaration();
        $methodDeclaration->modifiers = $this->parseModifiers();
        $this->parseFunctionDefinition($methodDeclaration);
        $methodDeclaration->parent = $parentNode;
        return $methodDeclaration;
    }

    function parseParameterFn() {
        return function ($parentNode) {
            $node = new Parameter();
            $node->parent = $parentNode;
            $node->typeDeclaration = $this->tryParseTypeDeclaration($node);
            $node->byRefToken = $this->eatOptional(TokenKind::AmpersandToken);
            // TODO add post-parse rule that prevents assignment
            // TODO add post-parse rule that requires only last parameter be variadic
            $node->dotDotDotToken = $this->eatOptional(TokenKind::DotDotDotToken);
            $node->variableName = $this->eat(TokenKind::VariableName);
            $node->equalsToken = $this->eatOptional(TokenKind::EqualsToken);
            if ($node->equalsToken !== null) {
                // TODO add post-parse rule that checks for invalid assignments
                $node->default = $this->parseExpression($node);
            }
            return $node;
        };
    }

    function tryParseTypeDeclaration($parentNode) {
        $typeDeclaration = $this->parseQualifiedName($parentNode);
        if ($typeDeclaration === null) {
            $typeDeclaration = $this->eatOptional(
                TokenKind::ArrayKeyword, TokenKind::CallableKeyword, TokenKind::BoolReservedWord,
                TokenKind::FloatReservedWord, TokenKind::IntReservedWord, TokenKind::StringReservedWord);
        }
        return $typeDeclaration;
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
                // unary-op-expression
                case TokenKind::PlusToken:
                case TokenKind::MinusToken:
                case TokenKind::ExclamationToken:
                case TokenKind::TildeToken:
                    return true;

                // variable-name
                case TokenKind::VariableName:
                    return true;
                // qualified-name
                case TokenKind::Name:
                case TokenKind::BackslashToken:
                    return true;
                case TokenKind::NamespaceKeyword:
                    // TODO currently only supports qualified-names, but eventually parse namespace declarations
                    return $this->lookahead(TokenKind::BackslashToken);
                // literal
                case TokenKind::TemplateStringStart:

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

                // ( expression )
                case TokenKind::OpenParenToken:

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
            case TokenKind::Name: // TODO Qualified name

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
            // variable-name
            case TokenKind::VariableName: // TODO special case $this
                return $this->parseVariable($parentNode);

            // qualified-name
            case TokenKind::Name: // TODO Qualified name
            case TokenKind::BackslashToken:
            case TokenKind::NamespaceKeyword:
                return $this->parseQualifiedName($parentNode);

            // literal
            case TokenKind::TemplateStringStart:
                return $this->parseTemplateString($parentNode);

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

            // TODO constant-expression

            // intrinsic-construct
            case TokenKind::EchoKeyword:
                return $this->parseEchoExpression($parentNode);
            case TokenKind::ListKeyword:
                return $this->parseListIntrinsicExpression($parentNode);
            case TokenKind::UnsetKeyword:
                return $this->parseUnsetIntrinsicExpression($parentNode);


            // intrinsic-operator
            case TokenKind::ArrayKeyword:
                return $this->parseArrayIntrinsicExpression($parentNode);

            case TokenKind::EmptyKeyword:
                return $this->parseEmptyIntrinsicExpression($parentNode);
            case TokenKind::EvalKeyword:
                return $this->parseEvalIntrinsicExpression($parentNode);

            case TokenKind::ExitKeyword:
            case TokenKind::DieKeyword:
                return $this->parseExitIntrinsicExpression($parentNode);

            case TokenKind::IsSetKeyword:
                return $this->parseIssetIntrinsicExpression($parentNode);

            case TokenKind::PrintKeyword:
                return $this->parsePrintIntrinsicExpression($parentNode);

            // ( expression )
            case TokenKind::OpenParenToken:
                return $this->parseParenthesizedExpression($parentNode);

            /*
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
                $expression = new UnknownExpression();
                $expression->parent = $parentNode;
                $expression->children = array(
                    new Token(TokenKind::MissingToken, $token->fullStart, $token->fullStart, 0)
                );
                return $expression;
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
        // TODO validate input token
        $expression = new Literal();
        $expression->parent = $parentNode;
        $expression->children = $this->getCurrentToken();
        $this->advanceToken();
        return $expression;
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
                case TokenKind::DotDotDotToken:

                case TokenKind::ArrayKeyword:
                case TokenKind::CallableKeyword:

                // qualified-name
                case TokenKind::Name: // http://php.net/manual/en/language.namespaces.rules.php
                case TokenKind::BackslashToken:
                case TokenKind::NamespaceKeyword:
                    // All of these can be the start of a qualified name

                // scalar-type
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

    private function parseDelimitedList($delimeter, $isElementStartFn, $parseElementFn, $parentNode, $allowEmptyElements = false) {
        $node = new DelimitedList();
        $token = $this->getCurrentToken();
        do {
            if ($isElementStartFn($token)) {
                $node->addToken($parseElementFn($node));
            } elseif (!$allowEmptyElements || ($allowEmptyElements && !$this->lookahead($delimeter))) {
                break;
            }

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

    private function parseFunctionDefinition(FunctionDefinition $node) {
        $node->functionKeyword = $this->eat(TokenKind::FunctionKeyword);
        $node->byRefToken = $this->eatOptional(TokenKind::AmpersandToken);
        $node->name = $this->eat(TokenKind::Name);
        $node->openParen = $this->eat(TokenKind::OpenParenToken);
        $node->parameters = $this->parseDelimitedList(TokenKind::CommaToken, $this->isParameterStartFn(), $this->parseParameterFn(), $node);
        $node->closeParen = $this->eat(TokenKind::CloseParenToken);
        if ($this->lookahead(TokenKind::ColonToken)) {
            $node->colonToken = $this->eat(TokenKind::ColonToken);
            $node->returnType = $this->tryParseTypeDeclaration($node) ?? $this->eat(TokenKind::VoidReservedWord);
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
        while ($this->lookahead(TokenKind::ElseIfKeyword)) {
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

    function parseExpression($parentNode, $force = false) {
        $token = $this->getCurrentToken();
        $expression = ($this->parseExpressionFn())($parentNode);
        if ($force && $expression->kind === NodeKind::UnknownExpression) {
            array_push(
                $expression->children,
                new Token(TokenKind::SkippedToken, $token->fullStart, $token->start, $token->length)
            );
            $this->advanceToken();
        }

        return $expression;
    }

    function parseExpressionFn() {
        return function ($parentNode) {
            return $this->parseBinaryExpressionOrHigher(0, $parentNode);
        };
    }

    private function parseUnaryExpressionOrHigher($parentNode) {
        $token = $this->getCurrentToken();
        switch ($token->kind) {
            // unary-op-expression
            case TokenKind::PlusToken:
            case TokenKind::MinusToken:
            case TokenKind::ExclamationToken:
            case TokenKind::TildeToken:
                return $this->parseUnaryOpExpression($parentNode);
/*
            // error-control-expression
            case TokenKind::AtSymbolToken:
                return $this->parseErrorControlExpression($parentNode);

            case TokenKind::BacktickToken:
                return $this->parseShellCommandExpression($parentNode);

            case TokenKind::OpenParenToken:
                // TODO
//                return $this->parseCastExpression($parentNode);
                break;
            case TokenKind::PlusPlusToken:
            case TokenKind::MinusMinusToken:
                return $this->parsePrefixUpdateExpression($parentNode);*/
        }

        return $this->parsePrimaryExpression($parentNode);
    }

    private function parseBinaryExpressionOrHigher($precedence, $parentNode) {
        $leftOperand = $this->parseUnaryExpressionOrHigher($parentNode);

        list($prevNewPrecedence, $prevAssociativity) = self::UNKNOWN_PRECEDENCE_AND_ASSOCIATIVITY;

        while (true) {
            $token = $this->getCurrentToken();

            list($newPrecedence, $associativity) = $this->getBinaryOperatorPrecedenceAndAssociativity($token);

            // Expressions using operators w/o associativity (equality, relational, instanceof)
            // cannot reference identical expression types within one of their operands.
            //
            // Example:
            //   $a < $b < $c // CASE 1: INVALID
            //   $a < $b === $c < $d // CASE 2: VALID
            //
            // In CASE 1, it is expected that we stop parsing the expression after the $b token.
            if ($prevAssociativity === Associativity::None && $prevNewPrecedence === $newPrecedence) {
                break;
            }

            // Precedence and associativity properties determine whether we recurse, and continue
            // building up the current operand, or whether we pop out.
            //
            // Example:
            //   $a + $b + $c // CASE 1: additive-expression (left-associative)
            //   $a = $b = $c // CASE 2: equality-expression (right-associative)
            //
            // CASE 1:
            // The additive-expression is left-associative, which means we expect the grouping to be:
            //   ($a + $b) + $c
            //
            // Because both + operators have the same precedence, and the + operator is left associative,
            // we expect the second + operator NOT to be consumed because $newPrecedence > $precedence => FALSE
            //
            // CASE 2:
            // The equality-expression is right-associative, which means we expect the grouping to be:
            //   $a = ($b = $c)
            //
            // Because both = operators have the same precedence, and the = operator is right-associative,
            // we expect the second = operator to be consumed because $newPrecedence >= $precedence => TRUE
            $shouldConsumeCurrentOperator =
                $associativity === Associativity::Right ?
                    $newPrecedence >= $precedence:
                    $newPrecedence > $precedence;

            if (!$shouldConsumeCurrentOperator) {
                break;
            }

            $this->advanceToken();
            $leftOperand = $this->makeBinaryExpression(
                $leftOperand,
                $token,
                $this->parseBinaryExpressionOrHigher($newPrecedence, null),
                $parentNode);

            $prevNewPrecedence = $newPrecedence;
            $prevAssociativity = $associativity;
        }
        return $leftOperand;
    }

    const OPERATOR_PRECEDENCE_AND_ASSOCIATIVITY =
        [
            // logical-inc-OR-expression-2 (L)
            TokenKind::OrKeyword => [6, Associativity::Left],

            // logical-exc-OR-expression-2 (L)
            TokenKind::XorKeyword=> [7, Associativity::Left],

            // logical-AND-expression-2 (L)
            TokenKind::AndKeyword=> [8, Associativity::Left],

            // simple-assignment-expression (R)
            // TODO byref-assignment-expression
            TokenKind::EqualsToken => [9, Associativity::Right],

            // compound-assignment-expression (R)
            TokenKind::AsteriskAsteriskEqualsToken => [9, Associativity::Right],
            TokenKind::AsteriskEqualsToken => [9, Associativity::Right],
            TokenKind::SlashEqualsToken => [9, Associativity::Right],
            TokenKind::PercentEqualsToken => [9, Associativity::Right],
            TokenKind::PlusEqualsToken => [9, Associativity::Right],
            TokenKind::MinusEqualsToken => [9, Associativity::Right],
            TokenKind::DotEqualsToken => [9, Associativity::Right],
            TokenKind::LessThanLessThanEqualsToken => [9, Associativity::Right],
            TokenKind::GreaterThanGreaterThanEqualsToken => [9, Associativity::Right],
            TokenKind::AmpersandEqualsToken => [9, Associativity::Right],
            TokenKind::CaretEqualsToken => [9, Associativity::Right],
            TokenKind::BarEqualsToken => [9, Associativity::Right],

            // TODO coalesce-expression (R)
            TokenKind::QuestionQuestionToken => [9, Associativity::Right],

            // TODO conditional-expression (L)
//            TokenKind::QuestionToken => [9, Associativity::Left],
//            TokenKind::ColonToken => [9, Associativity::Left],

            //logical-inc-OR-expression-1 (L)
            TokenKind::BarBarToken => [10, Associativity::Left],

            // logical-AND-expression-1 (L)
            TokenKind::AmpersandAmpersandToken => [11, Associativity::Left],

            // bitwise-inc-OR-expression (L)
            TokenKind::BarToken => [12, Associativity::Left],

            // bitwise-exc-OR-expression (L)
            TokenKind::CaretToken => [13, Associativity::Left],

            // bitwise-AND-expression (L)
            TokenKind::AmpersandToken => [14, Associativity::Left],

            // equality-expression (X)
            TokenKind::EqualsEqualsToken => [15, Associativity::None],
            TokenKind::ExclamationEqualsToken => [15, Associativity::None],
            TokenKind::LessThanGreaterThanToken => [15, Associativity::None],
            TokenKind::EqualsEqualsEqualsToken => [15, Associativity::None],
            TokenKind::ExclamationEqualsEqualsToken => [15, Associativity::None],

            // relational-expression (X)
            TokenKind::LessThanToken => [16, Associativity::None],
            TokenKind::GreaterThanToken => [16, Associativity::None],
            TokenKind::LessThanEqualsToken => [16, Associativity::None],
            TokenKind::GreaterThanEqualsToken => [16, Associativity::None],
            TokenKind::LessThanEqualsGreaterThanToken => [16, Associativity::None],

            // shift-expression (L)
            TokenKind::LessThanLessThanToken => [17, Associativity::Left],
            TokenKind::GreaterThanGreaterThanToken => [17, Associativity::Left],

            // additive-expression (L)
            TokenKind::PlusToken => [18, Associativity::Left],
            TokenKind::MinusToken => [18, Associativity::Left],
            TokenKind::DotToken =>[18, Associativity::Left],

            // multiplicative-expression (L)
            TokenKind::AsteriskToken => [19, Associativity::Left],
            TokenKind::SlashToken => [19, Associativity::Left],
            TokenKind::PercentToken => [19, Associativity::Left],

            // instanceof-expression (X)
            TokenKind::InstanceOfKeyword => [20, Associativity::None]
        ];

    const UNKNOWN_PRECEDENCE_AND_ASSOCIATIVITY = [-1, -1];

    private function getBinaryOperatorPrecedenceAndAssociativity($token) {
        if (isset(self::OPERATOR_PRECEDENCE_AND_ASSOCIATIVITY[$token->kind])) {
            return self::OPERATOR_PRECEDENCE_AND_ASSOCIATIVITY[$token->kind];
        }

        return self::UNKNOWN_PRECEDENCE_AND_ASSOCIATIVITY;
    }

    private function makeBinaryExpression($leftOperand, $operatorToken, $rightOperand, $parentNode) {
        $binaryExpression = new BinaryExpression();
        $binaryExpression->parent = $parentNode;
        $leftOperand->parent = $binaryExpression;
        $rightOperand->parent = $binaryExpression;
        $binaryExpression->leftOperand = $leftOperand;
        $binaryExpression->operator = $operatorToken;
        $binaryExpression->rightOperand = $rightOperand;
        return $binaryExpression;
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
        } elseif ($this->lookahead(TokenKind::ColonToken)) {
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

    private function parseVariable($parentNode) {
        $token = $this->getCurrentToken();
        $expression = new Variable();
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
    }

    private function parseEchoExpression($parentNode) {
        $echoExpression = new EchoExpression();
        $echoExpression->parent = $parentNode;
        $echoExpression->echoKeyword = $this->eat(TokenKind::EchoKeyword);
        $echoExpression->expressions =
            $this->parseExpressionList($echoExpression);

        return $echoExpression;
    }

    private function parseListIntrinsicExpression($parentNode) {
        $listExpression = new ListIntrinsicExpression();
        $listExpression->parent = $parentNode;
        $listExpression->listKeyword = $this->eat(TokenKind::ListKeyword);
        $listExpression->openParen = $this->eat(TokenKind::OpenParenToken);
        $listExpression->listElements =
            $this->parseArrayElementList($listExpression);
        $listExpression->closeParen = $this->eat(TokenKind::CloseParenToken);

        return $listExpression;
    }

    private function isArrayElementStart($token) {
        return ($this->isArrayElementStartFn())($token);
    }

    private function isArrayElementStartFn() {
        return function ($token) {
            return $token->kind === TokenKind::AmpersandToken || $this->isExpressionStart($token);
        };
    }

    private function parseArrayElement($parentNode) {
        return ($this->parseArrayElementFn())($parentNode);
    }

    private function parseArrayElementFn() {
        return function($parentNode) {
            $arrayElement = new ArrayElement();
            $arrayElement->parent = $parentNode;

            $token = $this->getCurrentToken();
            if ($this->lookahead(TokenKind::AmpersandToken)) {
                $arrayElement->byRef = $this->eat(TokenKind::AmpersandToken);
                $arrayElement->elementValue = $this->parseExpression($arrayElement);
            } else {
                $expression = $this->parseExpression($arrayElement);
                if ($this->lookahead(TokenKind::DoubleArrowToken)) {
                    $arrayElement->elementKey = $expression;
                    $arrayElement->arrowToken = $this->eat(TokenKind::DoubleArrowToken);
                    $arrayElement->byRef = $this->eatOptional(TokenKind::AmpersandToken); // TODO not okay for list expressions
                    $arrayElement->elementValue = $this->parseExpression($arrayElement);
                } else {
                    $arrayElement->elementValue = $expression;
                }
            }

            return $arrayElement;
        };
    }

    private function parseExpressionList($parentExpression) {
        return $this->parseDelimitedList(
            TokenKind::CommaToken,
            $this->isExpressionStartFn(),
            $this->parseExpressionFn(),
            $parentExpression
        );
    }

    private function parseUnsetIntrinsicExpression($parentNode) {
        $unsetExpression = new UnsetIntrinsicExpression();
        $unsetExpression->parent = $parentNode;

        $unsetExpression->unsetKeyword = $this->eat(TokenKind::UnsetKeyword);
        $unsetExpression->openParen = $this->eat(TokenKind::OpenParenToken);
        $unsetExpression->expressions = $this->parseExpressionList($unsetExpression);
        $unsetExpression->closeParen = $this->eat(TokenKind::CloseParenToken);

        return $unsetExpression;
    }

    private function parseArrayIntrinsicExpression($parentNode) {
        $arrayExpression = new ArrayIntrinsicExpression();
        $arrayExpression->parent = $parentNode;

        $arrayExpression->arrayKeyword = $this->eat(TokenKind::ArrayKeyword);
        $arrayExpression->openParen = $this->eat(TokenKind::OpenParenToken);
        $arrayExpression->arrayElements = $this->parseArrayElementList($arrayExpression);
        $arrayExpression->closeParen = $this->eat(TokenKind::CloseParenToken);

        return $arrayExpression;
    }

    private function parseArrayElementList($listExpression) {
        return $this->parseDelimitedList(
            TokenKind::CommaToken,
            $this->isArrayElementStartFn(),
            $this->parseArrayElementFn(),
            $listExpression,
            true
        );
    }

    private function parseEmptyIntrinsicExpression($parentNode) {
        $emptyExpression = new EmptyIntrinsicExpression();
        $emptyExpression->parent = $parentNode;

        $emptyExpression->emptyKeyword = $this->eat(TokenKind::EmptyKeyword);
        $emptyExpression->openParen = $this->eat(TokenKind::OpenParenToken);
        $emptyExpression->expression = $this->parseExpression($emptyExpression);
        $emptyExpression->closeParen = $this->eat(TokenKind::CloseParenToken);

        return $emptyExpression;
    }

    private function parseEvalIntrinsicExpression($parentNode) {
        $evalExpression = new EvalIntrinsicExpression();
        $evalExpression->parent = $parentNode;

        $evalExpression->evalKeyword = $this->eat(TokenKind::EvalKeyword);
        $evalExpression->openParen = $this->eat(TokenKind::OpenParenToken);
        $evalExpression->expression = $this->parseExpression($evalExpression);
        $evalExpression->closeParen = $this->eat(TokenKind::CloseParenToken);

        return $evalExpression;
    }

    private function parseParenthesizedExpression($parentNode) {
        $parenthesizedExpression = new ParenthesizedExpression();
        $parenthesizedExpression->parent = $parentNode;

        $parenthesizedExpression->openParen = $this->eat(TokenKind::OpenParenToken);
        $parenthesizedExpression->expression = $this->parseExpression($parenthesizedExpression);
        $parenthesizedExpression->closeParen = $this->eat(TokenKind::CloseParenToken);

        return $parenthesizedExpression;
    }

    private function parseExitIntrinsicExpression($parentNode) {
        $exitExpression = new ExitIntrinsicExpression();
        $exitExpression->parent = $parentNode;

        $exitExpression->exitOrDieKeyword = $this->eat(TokenKind::ExitKeyword, TokenKind::DieKeyword);
        $exitExpression->openParen = $this->eatOptional(TokenKind::OpenParenToken);
        if ($exitExpression->openParen !== null) {
            if ($this->isExpressionStart($this->getCurrentToken())){
                $exitExpression->expression = $this->parseExpression($exitExpression);
            }
            $exitExpression->closeParen = $this->eat(TokenKind::CloseParenToken);
        }

        return $exitExpression;
    }

    private function parsePrintIntrinsicExpression($parentNode) {
        $printExpression = new PrintIntrinsicExpression();
        $printExpression->parent = $parentNode;

        $printExpression->printKeyword = $this->eat(TokenKind::PrintKeyword);
        $printExpression->expression = $this->parseExpression($printExpression);

        return $printExpression;
    }

    private function parseIssetIntrinsicExpression($parentNode) {
        $issetExpression = new IssetIntrinsicExpression();
        $issetExpression->parent = $parentNode;

        $issetExpression->issetKeyword = $this->eat(TokenKind::IsSetKeyword);
        $issetExpression->openParen = $this->eat(TokenKind::OpenParenToken);
        $issetExpression->expressions = $this->parseExpressionList($issetExpression);
        $issetExpression->closeParen = $this->eat(TokenKind::CloseParenToken);

        return $issetExpression;
    }

    private function parseUnaryOpExpression($parentNode) {
        $unaryOpExpression = new UnaryOpExpression();
        $unaryOpExpression->parent = $parentNode;
        $unaryOpExpression->operator =
            $this->eat(TokenKind::PlusToken, TokenKind::MinusToken, TokenKind::ExclamationToken, TokenKind::TildeToken);
        if ($this->isExpressionStart($this->getCurrentToken())) {

        }
        $unaryOpExpression->operand = $this->parseUnaryExpressionOrHigher($unaryOpExpression);

        return $unaryOpExpression;
    }
}

class Associativity {
    const None = 0;
    const Left = 1;
    const Right = 2;
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
