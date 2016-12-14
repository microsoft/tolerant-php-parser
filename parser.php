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

use PhpParser\Node\AnonymousFunctionCreationExpression;
use PhpParser\Node\AnonymousFunctionUseClause;
use PhpParser\Node\ArgumentExpression;
use PhpParser\Node\ArrayElement;
use PhpParser\Node\ArrayCreationExpression;
use PhpParser\Node\BinaryExpression;
use PhpParser\Node\BracedExpression;
use PhpParser\Node\CallExpression;
use PhpParser\Node\CaseStatementNode;
use PhpParser\Node\CastExpression;
use PhpParser\Node\CatchClause;
use PhpParser\Node\ClassBaseClause;
use PhpParser\Node\ClassInterfaceClause;
use PhpParser\Node\ClassMembersNode;
use PhpParser\Node\ClassNode;
use PhpParser\Node\ConstDeclaration;
use PhpParser\Node\ConstElement;
use PhpParser\Node\FunctionStaticDeclaration;
use PhpParser\Node\GlobalDeclaration;
use PhpParser\Node\StaticVariableDeclaration;
use PhpParser\Node\TraitDeclaration;
use PhpParser\Node\BreakOrContinueStatement;
use PhpParser\Node\ClassConstDeclaration;
use PhpParser\Node\DeclareDirective;
use PhpParser\Node\DeclareStatement;
use PhpParser\Node\DelimitedList;
use PhpParser\Node\DoStatement;
use PhpParser\Node\EchoExpression;
use PhpParser\Node\ElseClauseNode;
use PhpParser\Node\ElseIfClauseNode;
use PhpParser\Node\EmptyIntrinsicExpression;
use PhpParser\Node\EmptyStatementNode;
use PhpParser\Node\ErrorControlExpression;
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
use PhpParser\Node\CompoundStatementNode;
use PhpParser\Node\GotoStatement;
use PhpParser\Node\IfStatementNode;
use PhpParser\Node\InterfaceBaseClause;
use PhpParser\Node\InterfaceDeclaration;
use PhpParser\Node\InterfaceMembers;
use PhpParser\Node\IssetIntrinsicExpression;
use PhpParser\Node\ListIntrinsicExpression;
use PhpParser\Node\MissingMemberDeclaration;
use PhpParser\Node\NamespaceAliasingClause;
use PhpParser\Node\NamespaceDefinition;
use PhpParser\Node\NamespaceUseGroupClause;
use PhpParser\Node\NamespaceUseDeclaration;
use PhpParser\Node\NumericLiteral;
use PhpParser\Node\ObjectCreationExpression;
use PhpParser\Node\PropertyDeclaration;
use PhpParser\Node\ReservedWord;
use PhpParser\Node\ScriptInclusionExpression;
use PhpParser\Node\StringLiteral;
use PhpParser\Node\MemberAccessExpression;
use PhpParser\Node\MethodDeclaration;
use PhpParser\Node\NamedLabelStatementNode;
use PhpParser\Node\Node;
use PhpParser\Node\Parameter;
use PhpParser\Node\ParenthesizedExpression;
use PhpParser\Node\PostfixUpdateExpression;
use PhpParser\Node\PrefixUpdateExpression;
use PhpParser\Node\PrintIntrinsicExpression;
use PhpParser\Node\QualifiedName;
use PhpParser\Node\RelativeSpecifier;
use PhpParser\Node\ReturnStatement;
use PhpParser\Node\ScopedPropertyAccessExpression;
use PhpParser\Node\Script;
use PhpParser\Node\ScriptSection;
use PhpParser\Node\StatementNode;
use PhpParser\Node\SubscriptExpression;
use PhpParser\Node\SwitchStatementNode;
use PhpParser\Node\TemplateExpressionNode;
use PhpParser\Node\TernaryExpression;
use PhpParser\Node\ThrowStatement;
use PhpParser\Node\TraitMembers;
use PhpParser\Node\TraitSelectOrAliasClause;
use PhpParser\Node\TraitUseClause;
use PhpParser\Node\TryStatement;
use PhpParser\Node\UnaryExpression;
use PhpParser\Node\UnaryOpExpression;
use PhpParser\Node\UnknownExpression;
use PhpParser\Node\UnsetIntrinsicExpression;
use PhpParser\Node\UseVariableName;
use PhpParser\Node\Variable;
use PhpParser\Node\WhileStatement;

class Parser {

    private $lexer;

    private $currentParseContext;
    public $sourceFile;

    public function __construct($filename) {
        $this->lexer = new Lexer($filename);
    }

    public function getErrors(Node $ast) {
        $errors = [];
        $unexpectedTokens = [];
        $missingTokens = [];
        $invalid = [];
        foreach ($ast->getChildren() as $child) {
            if ($child instanceof Node) {
                $childErrors = $this->getErrors($child);
                $unexpectedTokens = array_merge($unexpectedTokens, $childErrors["skipped"]);
                $missingTokens = array_merge($missingTokens, $childErrors["missing"]);
                $invalid = array_merge($invalid, $childErrors["invalid"]);

                $invalid = array_merge($invalid, $child->validateRules());

            } elseif ($child instanceof SkippedToken) {
                $unexpectedTokens[] = $child;
            } elseif ($child instanceof MissingToken) {
                $missingTokens[] = $child;
            }
        }
        return ["skipped" => $unexpectedTokens, "missing" => $missingTokens, "invalid"=>$invalid];
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
            $token = new SkippedToken($this->getCurrentToken());
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

            case ParseContext::InterfaceMembers:
            case ParseContext::ClassMembers:
            case ParseContext::BlockStatements:
            case ParseContext::TraitMembers:
                return $tokenKind === TokenKind::CloseBraceToken;
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

            case ParseContext::TraitMembers:
                return $this->isTraitMemberDeclarationStart($token);

            case ParseContext::InterfaceMembers:
                return $this->isInterfaceMemberDeclarationStart($token);

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
                return $this->parseClassElementFn();

            case ParseContext::TraitMembers:
                return $this->parseTraitElementFn();

            case ParseContext::InterfaceMembers:
                return $this->parseInterfaceElementFn();

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
        // TODO include optional grouping for token kinds
        return new MissingToken($kinds[0], $token->fullStart);
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
                    if ($this->lookahead(TokenKind::ColonToken)) {
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
                    if ($this->lookahead(TokenKind::Name) || $this->lookahead(TokenKind::AmpersandToken, TokenKind::Name)) {
                        return $this->parseFunctionDeclaration($parentNode);
                    }
                    break;

                // class-declaration
                case TokenKind::FinalKeyword:
                case TokenKind::AbstractKeyword:
                    if (!$this->lookahead(TokenKind::ClassKeyword)) {
                        $this->advanceToken();
                        return new SkippedToken($token);
                    }
                case TokenKind::ClassKeyword:
                    return $this->parseClassDeclaration($parentNode);

                // interface-declaration
                case TokenKind::InterfaceKeyword:
                    return $this->parseInterfaceDeclaration($parentNode);

                // namespace-definition
                case TokenKind::NamespaceKeyword:
                    return $this->parseNamespaceDefinition($parentNode);

                // namespace-use-declaration
                case TokenKind::UseKeyword:
                    return $this->parseNamespaceUseDeclaration($parentNode);

                case TokenKind::SemicolonToken:
                    return $this->parseEmptyStatement($parentNode);

                // trait-declaration
                case TokenKind::TraitKeyword:
                    return $this->parseTraitDeclaration($parentNode);

                // global-declaration
                case TokenKind::GlobalKeyword:
                    return $this->parseGlobalDeclaration($parentNode);
                
                // const-declaration
                case TokenKind::ConstKeyword:
                    return $this->parseConstDeclaration($parentNode);
                
                // function-static-declaration
                case TokenKind::StaticKeyword:
                    if (!$this->lookahead(TokenKind::FunctionKeyword)) {
                        return $this->parseFunctionStaticDeclaration($parentNode);
                    }
                    break;
            }

            $expressionStatement = new ExpressionStatement();
            $expressionStatement->parent = $parentNode;
            $expressionStatement->expression = $this->parseExpression($expressionStatement, true);
            $expressionStatement->semicolon = $this->eat(TokenKind::SemicolonToken);
            return $expressionStatement;
        };
    }

    function parseClassElementFn() {
        return function($parentNode) {
            $modifiers = $this->parseModifiers();

            $token = $this->getCurrentToken();
            switch($token->kind) {
                case TokenKind::ConstKeyword:
                    return $this->parseClassConstDeclaration($parentNode, $modifiers);

                case TokenKind::FunctionKeyword:
                    return $this->parseMethodDeclaration($parentNode, $modifiers);

                case TokenKind::VariableName:
                    return $this->parsePropertyDeclaration($parentNode, $modifiers);

                case TokenKind::UseKeyword:
                    return $this->parseTraitUseClause($parentNode);

                default:
                    $missingClassMemberDeclaration = new MissingMemberDeclaration();
                    $missingClassMemberDeclaration->parent = $parentNode;
                    $missingClassMemberDeclaration->modifiers = $modifiers;
                    return $missingClassMemberDeclaration;
            }
        };
    }

    function parseClassDeclaration($parentNode) : Node {
        $classNode = new ClassNode();
        $classNode->parent = $parentNode;
        $classNode->abstractOrFinalModifier = $this->eatOptional(TokenKind::AbstractKeyword, TokenKind::FinalKeyword);
        $classNode->classKeyword = $this->eat(TokenKind::ClassKeyword);
        $classNode->name = $this->eat(TokenKind::Name);
        $classNode->classBaseClause = $this->parseClassBaseClause($classNode);
        $classNode->classInterfaceClause = $this->parseClassInterfaceClause($classNode);
        $classNode->classMembers = $this->parseClassMembers($classNode);
        return $classNode;
    }

    function parseClassMembers($parentNode) : Node {
        $classMembers = new ClassMembersNode();
        $classMembers->openBrace = $this->eat(TokenKind::OpenBraceToken);
        $classMembers->classMemberDeclarations = $this->parseList($classMembers, ParseContext::ClassMembers);
        $classMembers->closeBrace = $this->eat(TokenKind::CloseBraceToken);
        $classMembers->parent = $parentNode;
        return $classMembers;
    }

    function parseFunctionDeclaration($parentNode) {
        $functionNode = new Function_();
        $this->parseFunctionDefinition($functionNode);
        $functionNode->parent = $parentNode;
        return $functionNode;
    }

    function parseMethodDeclaration($parentNode, $modifiers) {
        $methodDeclaration = new MethodDeclaration();
        $methodDeclaration->modifiers = $modifiers;
        $this->parseFunctionDefinition($methodDeclaration, true);
        $methodDeclaration->parent = $parentNode;
        return $methodDeclaration;
    }

    function parseParameterFn() {
        return function ($parentNode) {
            $parameter = new Parameter();
            $parameter->parent = $parentNode;
            $parameter->typeDeclaration = $this->tryParseTypeDeclaration($parameter);
            $parameter->byRefToken = $this->eatOptional(TokenKind::AmpersandToken);
            // TODO add post-parse rule that prevents assignment
            // TODO add post-parse rule that requires only last parameter be variadic
            $parameter->dotDotDotToken = $this->eatOptional(TokenKind::DotDotDotToken);
            $parameter->variableName = $this->eat(TokenKind::VariableName);
            $parameter->equalsToken = $this->eatOptional(TokenKind::EqualsToken);
            if ($parameter->equalsToken !== null) {
                // TODO add post-parse rule that checks for invalid assignments
                $parameter->default = $this->parseExpression($parameter);
            }
            return $parameter;
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
                // Script Inclusion Expression
                case TokenKind::RequireKeyword:
                case TokenKind::RequireOnceKeyword:
                case TokenKind::IncludeKeyword:
                case TokenKind::IncludeOnceKeyword:

                case TokenKind::NewKeyword:
                    return true;

                // unary-op-expression
                case TokenKind::PlusToken:
                case TokenKind::MinusToken:
                case TokenKind::ExclamationToken:
                case TokenKind::TildeToken:

                // error-control-expression
                case TokenKind::AtSymbolToken:

                // prefix-increment-expression
                case TokenKind::PlusPlusToken:
                    // prefix-decrement-expression
                case TokenKind::MinusMinusToken:
                    return true;

                // variable-name
                case TokenKind::VariableName:
                case TokenKind::DollarToken:
                    return true;

                // qualified-name
                case TokenKind::Name:
                case TokenKind::BackslashToken:
                    return true;
                case TokenKind::NamespaceKeyword:
                    // TODO currently only supports qualified-names, but eventually parse namespace declarations
                    return $this->checkToken(TokenKind::BackslashToken);
                
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

                // array-creation-expression
                case TokenKind::ArrayKeyword:
                case TokenKind::OpenBracketToken:

                // intrinsic-construct
                case TokenKind::EchoKeyword:
                case TokenKind::ListKeyword:
                case TokenKind::UnsetKeyword:

                // intrinsic-operator
                case TokenKind::EmptyKeyword:
                case TokenKind::EvalKeyword:
                case TokenKind::ExitKeyword:
                case TokenKind::DieKeyword:
                case TokenKind::IsSetKeyword:
                case TokenKind::PrintKeyword:

                // ( expression )
                case TokenKind::OpenParenToken:

                // reserved words
                case TokenKind::NullReservedWord:
                case TokenKind::FalseReservedWord:
                case TokenKind::TrueReservedWord:

                // anonymous-function-creation-expression
                case TokenKind::StaticKeyword:
                case TokenKind::FunctionKeyword:
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

    private function parsePrimaryExpression($parentNode) {
        $token = $this->getCurrentToken();
        switch ($token->kind) {
            // variable-name
            case TokenKind::VariableName: // TODO special case $this
            case TokenKind::DollarToken:
                return $this->parseSimpleVariable($parentNode);

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
                return $this->parseNumericLiteralExpression($parentNode);

            case TokenKind::StringLiteralToken: // TODO merge unterminated
            case TokenKind::UnterminatedStringLiteralToken:
            case TokenKind::NoSubstitutionTemplateLiteral:
            case TokenKind::UnterminatedNoSubstitutionTemplateLiteral:
                return $this->parseStringLiteralExpression($parentNode);

            // TODO constant-expression

            // array-creation-expression
            case TokenKind::ArrayKeyword:
            case TokenKind::OpenBracketToken:
                return $this->parseArrayCreationExpression($parentNode);

            // intrinsic-construct
            case TokenKind::EchoKeyword:
                return $this->parseEchoExpression($parentNode);
            case TokenKind::ListKeyword:
                return $this->parseListIntrinsicExpression($parentNode);
            case TokenKind::UnsetKeyword:
                return $this->parseUnsetIntrinsicExpression($parentNode);

            // intrinsic-operator
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

            // reserved words
            case TokenKind::FalseReservedWord:
            case TokenKind::TrueReservedWord:
            case TokenKind::NullReservedWord:
                return $this->parseReservedWordExpression($parentNode);

            // anonymous-function-creation-expression
            case TokenKind::StaticKeyword:
            case TokenKind::FunctionKeyword:
                return $this->parseAnonymousFunctionCreationExpression($parentNode);
            /*
            */

            default:
                return new MissingToken(TokenKind::Expression, $token->fullStart);
        }
    }

    private function parseEmptyStatement($parentNode) {
        $node = new EmptyStatementNode();
        $node->children = array();
        array_push($node->children, $this->eat(TokenKind::SemicolonToken));
        $node->parent = $parentNode;
        return $node;
    }

    private function parseStringLiteralExpression($parentNode) {
        // TODO validate input token
        $expression = new StringLiteral();
        $expression->parent = $parentNode;
        $expression->children = $this->getCurrentToken();
        $this->advanceToken();
        return $expression;
    }

    private function parseNumericLiteralExpression($parentNode) {
        $numericLiteral = new NumericLiteral();
        $numericLiteral->parent = $parentNode;
        $numericLiteral->children = $this->getCurrentToken();
        $this->advanceToken();
        return $numericLiteral;
    }

    private function parseReservedWordExpression($parentNode) {
        $reservedWord = new ReservedWord();
        $reservedWord->parent = $parentNode;
        $reservedWord->children = $this->getCurrentToken();
        $this->advanceToken();
        return $reservedWord;
    }

    private function isModifier($token) {
        switch($token->kind) {
            // class-modifier
            case TokenKind::AbstractKeyword:
            case TokenKind::FinalKeyword:

            // visibility-modifier
            case TokenKind::PublicKeyword:
            case TokenKind::ProtectedKeyword:
            case TokenKind::PrivateKeyword:

            // static-modifier
            case TokenKind::StaticKeyword:

            // var
            case TokenKind::VarKeyword;
                return true;
        }
        return false;
    }

    private function parseModifiers() {
        $modifiers = array();
        $token = $this->getCurrentToken();
        while ($this->isModifier($token)) {
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
        // TODO consider allowing empty delimiter to be more tolerant
        $node = new DelimitedList();
        $token = $this->getCurrentToken();
        do {
            if ($isElementStartFn($token)) {
                $node->addToken($parseElementFn($node));
            } elseif (!$allowEmptyElements || ($allowEmptyElements && !$this->checkToken($delimeter))) {
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

    private function isQualifiedNameStart($token) {
        return ($this->isQualifiedNameStartFn())($token);
    }

    private function isQualifiedNameStartFn() {
        return function ($token) {
            switch ($token->kind) {
                case TokenKind::BackslashToken:
                case TokenKind::NamespaceKeyword:
                case TokenKind::Name:
                    return true;
            }
            return false;
        };
    }

    private function parseQualifiedName($parentNode) {
        return ($this->parseQualifiedNameFn())($parentNode);
    }

    private function parseQualifiedNameFn() {
        return function ($parentNode) {
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
                        return $this->eat(TokenKind::Name); // TODO support keyword name
                    }, $node);
            if ($node->nameParts === null && $node->globalSpecifier === null && $node->relativeSpecifier === null) {
                return null;
            }
            return $node;
        };
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

    private function parseFunctionDefinition(Node $functionDefinition, $canBeAbstract = false, $isAnonymous = false) {
        $functionDefinition->functionKeyword = $this->eat(TokenKind::FunctionKeyword);
        $functionDefinition->byRefToken = $this->eatOptional(TokenKind::AmpersandToken);
        $functionDefinition->name = $isAnonymous ? $this->eatOptional(TokenKind::Name) : $this->eat(TokenKind::Name); // TODO support keyword names
        if ($isAnonymous && isset($functionDefinition->name)) {
            $functionDefinition->name = new SkippedToken($functionDefinition->name); // TODO instaed handle this during post-walk
        }

        $functionDefinition->openParen = $this->eat(TokenKind::OpenParenToken);
        $functionDefinition->parameters = $this->parseDelimitedList(TokenKind::CommaToken, $this->isParameterStartFn(), $this->parseParameterFn(), $functionDefinition);
        $functionDefinition->closeParen = $this->eat(TokenKind::CloseParenToken);
        if ($isAnonymous) {
            $functionDefinition->anonymousFunctionUseClause = $this->parseAnonymousFunctionUseClause($functionDefinition);
        }

        if ($this->checkToken(TokenKind::ColonToken)) {
            $functionDefinition->colonToken = $this->eat(TokenKind::ColonToken);
            $functionDefinition->returnType = $this->tryParseTypeDeclaration($functionDefinition) ?? $this->eat(TokenKind::VoidReservedWord);
        }

        if ($canBeAbstract) {
            $functionDefinition->compoundStatementOrSemicolon = $this->eatOptional(TokenKind::SemicolonToken);
        }

        if (!isset($functionDefinition->compoundStatementOrSemicolon)) {
            $functionDefinition->compoundStatementOrSemicolon = $this->parseCompoundStatement($functionDefinition);
        }
    }

    private function parseNamedLabelStatement($parentNode) {
        $namedLabelStatement = new NamedLabelStatementNode();
        $namedLabelStatement->parent = $parentNode;
        $namedLabelStatement->name = $this->eat(TokenKind::Name);
        $namedLabelStatement->colon = $this->eat(TokenKind::ColonToken);
        $namedLabelStatement->statement = $this->parseStatement($namedLabelStatement);
        return $namedLabelStatement;
    }

    private function lookahead(...$expectedKinds) : bool {
        $startPos = $this->lexer->pos;
        $startToken = $this->token;
        $succeeded = true;
        foreach ($expectedKinds as $kind) {
            $this->advanceToken();
            if (is_array($kind)) {
                $succeeded = false;
                foreach ($kind as $kindOption) {
                    if ($this->lexer->pos <= $this->lexer->endOfFilePos && $this->getCurrentToken()->kind === $kindOption) {
                        $succeeded = true;
                        break;
                    }
                }
            } else {
                if ($this->lexer->pos > $this->lexer->endOfFilePos || $this->getCurrentToken()->kind !== $kind) {
                    $succeeded = false;
                    break;
                }
            }
        }
        $this->lexer->pos = $startPos;
        $this->token = $startToken;
        return $succeeded;
    }

    private function checkToken($expectedKind) : bool {
        return $this->getCurrentToken()->kind === $expectedKind;
    }

    private function parseIfStatement($parentNode) {
        $ifStatement = new IfStatementNode();
        $ifStatement->parent = $parentNode;
        $ifStatement->ifKeyword = $this->eat(TokenKind::IfKeyword);
        $ifStatement->openParen = $this->eat(TokenKind::OpenParenToken);
        $ifStatement->expression = $this->parseExpression($ifStatement);
        $ifStatement->closeParen = $this->eat(TokenKind::CloseParenToken);
        if ($this->checkToken(TokenKind::ColonToken)) {
            $ifStatement->colon = $this->eat(TokenKind::ColonToken);
            $ifStatement->statements = $this->parseList($ifStatement, ParseContext::IfClause2Elements);
        } else {
            $ifStatement->statements = $this->parseStatement($ifStatement);
        }
        $ifStatement->elseIfClauses = array(); // TODO - should be some standard for empty arrays vs. null?
        while ($this->checkToken(TokenKind::ElseIfKeyword)) {
            array_push($ifStatement->elseIfClauses, $this->parseElseIfClause($ifStatement));
        }

        if ($this->checkToken(TokenKind::ElseKeyword)) {
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
        if ($this->checkToken(TokenKind::ColonToken)) {
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
        if ($this->checkToken(TokenKind::ColonToken)) {
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
        if ($token->kind === TokenKind::EndOfFileToken) {
            return new MissingToken(TokenKind::Expression, $token->fullStart);
        }

        $expression = ($this->parseExpressionFn())($parentNode);
        if ($force && $expression instanceof MissingToken) {
            $expression = [$expression, new SkippedToken($token)];
            $this->advanceToken();
        }

        return $expression;
    }

    function parseExpressionFn() {
        return function ($parentNode) {
            $token = $this->getCurrentToken();
            switch ($token->kind) {
                case TokenKind::IncludeKeyword:
                case TokenKind::IncludeOnceKeyword:
                case TokenKind::RequireKeyword:
                case TokenKind::RequireOnceKeyword:
                    $scriptInclusionExpression = new ScriptInclusionExpression($parentNode);
                    $scriptInclusionExpression->parent = $parentNode;
                    $scriptInclusionExpression->requireOrIncludeKeyword =
                        $this->eat (
                            TokenKind::RequireKeyword, TokenKind::RequireOnceKeyword,
                            TokenKind::IncludeKeyword, TokenKind::IncludeOnceKeyword
                            );
                    $scriptInclusionExpression->expression  = $this->parseExpression($scriptInclusionExpression);
                    return $scriptInclusionExpression;
            }

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

            // error-control-expression
            case TokenKind::AtSymbolToken:
                return $this->parseErrorControlExpression($parentNode);

            // prefix-increment-expression
            case TokenKind::PlusPlusToken:
            // prefix-decrement-expression
            case TokenKind::MinusMinusToken:
                return $this->parsePrefixUpdateExpression($parentNode);

            case TokenKind::OpenParenToken:
                // TODO remove duplication
                if ($this->lookahead(
                    [TokenKind::ArrayKeyword,
                    TokenKind::BinaryReservedWord,
                    TokenKind::BoolReservedWord,
                    TokenKind::BooleanReservedWord,
                    TokenKind::DoubleReservedWord,
                    TokenKind::IntReservedWord,
                    TokenKind::IntegerReservedWord,
                    TokenKind::FloatReservedWord,
                    TokenKind::ObjectReservedWord,
                    TokenKind::RealReservedWord,
                    TokenKind::StringReservedWord,
                    TokenKind::UnsetKeyword], TokenKind::CloseParenToken)) {
                    return $this->parseCastExpression($parentNode);
                }
                break;

/*

            case TokenKind::BacktickToken:
                return $this->parseShellCommandExpression($parentNode);

            case TokenKind::OpenParenToken:
                // TODO
//                return $this->parseCastExpression($parentNode);
                break;*/

            // postfix-expression
            case TokenKind::NewKeyword:
                return $this->parseObjectCreationExpression($parentNode);
        }

        $expression = $this->parsePrimaryExpression($parentNode);
        return $this->parsePostfixExpressionRest($expression);
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

            // Unlike every other binary expression, exponentiation operators take precedence over unary operators.
            //
            // Example:
            //   -3**2 => -9
            //
            // In these cases, we strip the UnaryExpression operator, and reassign $leftOperand to
            // $unaryExpression->operand.
            //
            // After we finish building the BinaryExpression, we rebuild the UnaryExpression so that it includes
            // the original operator, and the newly constructed exponentiation-expression as the operand.
            $shouldOperatorTakePrecedenceOverUnary =
                $token->kind === TokenKind::AsteriskAsteriskToken && $leftOperand instanceof UnaryExpression;

            if ($shouldOperatorTakePrecedenceOverUnary) {
                $unaryExpression = $leftOperand;
                $leftOperand = $unaryExpression->operand;
            }

            $this->advanceToken();

            $leftOperand = $token->kind === TokenKind::QuestionToken ?
                $this->parseTernaryExpression($leftOperand, $token) :
                $this->makeBinaryExpression(
                    $leftOperand,
                    $token,
                    $this->parseBinaryExpressionOrHigher($newPrecedence, null),
                    $parentNode);

            // Rebuild the unary expression if we deconstructed it earlier.
            if ($shouldOperatorTakePrecedenceOverUnary) {
                $leftOperand->parent = $unaryExpression;
                $unaryExpression->operand = $leftOperand;
                $leftOperand = $unaryExpression;
            }

            // Hold onto these values, so we know whether we've hit duplicate non-associative operators,
            // and need to terminate early.
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

            // TODO conditional-expression (L)
            TokenKind::QuestionToken => [10, Associativity::Left],
//            TokenKind::ColonToken => [9, Associativity::Left],

            // TODO coalesce-expression (R)
            TokenKind::QuestionQuestionToken => [9, Associativity::Right],

            //logical-inc-OR-expression-1 (L)
            TokenKind::BarBarToken => [12, Associativity::Left],

            // logical-AND-expression-1 (L)
            TokenKind::AmpersandAmpersandToken => [13, Associativity::Left],

            // bitwise-inc-OR-expression (L)
            TokenKind::BarToken => [14, Associativity::Left],

            // bitwise-exc-OR-expression (L)
            TokenKind::CaretToken => [15, Associativity::Left],

            // bitwise-AND-expression (L)
            TokenKind::AmpersandToken => [16, Associativity::Left],

            // equality-expression (X)
            TokenKind::EqualsEqualsToken => [17, Associativity::None],
            TokenKind::ExclamationEqualsToken => [17, Associativity::None],
            TokenKind::LessThanGreaterThanToken => [17, Associativity::None],
            TokenKind::EqualsEqualsEqualsToken => [17, Associativity::None],
            TokenKind::ExclamationEqualsEqualsToken => [17, Associativity::None],

            // relational-expression (X)
            TokenKind::LessThanToken => [18, Associativity::None],
            TokenKind::GreaterThanToken => [18, Associativity::None],
            TokenKind::LessThanEqualsToken => [18, Associativity::None],
            TokenKind::GreaterThanEqualsToken => [18, Associativity::None],
            TokenKind::LessThanEqualsGreaterThanToken => [18, Associativity::None],

            // shift-expression (L)
            TokenKind::LessThanLessThanToken => [19, Associativity::Left],
            TokenKind::GreaterThanGreaterThanToken => [19, Associativity::Left],

            // additive-expression (L)
            TokenKind::PlusToken => [20, Associativity::Left],
            TokenKind::MinusToken => [20, Associativity::Left],
            TokenKind::DotToken =>[20, Associativity::Left],

            // multiplicative-expression (L)
            TokenKind::AsteriskToken => [21, Associativity::Left],
            TokenKind::SlashToken => [21, Associativity::Left],
            TokenKind::PercentToken => [21, Associativity::Left],

            // instanceof-expression (X)
            TokenKind::InstanceOfKeyword => [22, Associativity::None],

            // exponentiation-expression (R)
            TokenKind::AsteriskAsteriskToken => [23, Associativity::Right]
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

        if (!$this->checkToken(TokenKind::DoubleArrowToken)) {
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
        while ($this->checkToken(TokenKind::CatchKeyword)) {
            array_push($tryStatement->catchClauses, $this->parseCatchClause($tryStatement));
        }

        if ($this->checkToken(TokenKind::FinallyKeyword)) {
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

        if ($this->checkToken(TokenKind::SemicolonToken)) {
            $declareStatement->semicolon = $this->eat(TokenKind::SemicolonToken);
        } elseif ($this->checkToken(TokenKind::ColonToken)) {
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

    private function parseSimpleVariable($parentNode) {
        return ($this->parseSimpleVariableFn())($parentNode);
    }

    private function parseSimpleVariableFn() {
        return function ($parentNode) {
            $token = $this->getCurrentToken();
            $variable = new Variable();
            $variable->parent = $parentNode;

            if ($token->kind === TokenKind::DollarToken) {
                $variable->dollar = $this->eat(TokenKind::DollarToken);
                $token = $this->getCurrentToken();

                $variable->name =
                    $token->kind === TokenKind::OpenBraceToken ?
                        $this->parseBracedExpression($variable) :
                        $this->parseSimpleVariable($variable);

            } elseif ($token->kind === TokenKind::VariableName) {
                // TODO consider splitting into dollar and name
                $variable->name = $this->eat(TokenKind::VariableName);
            } else {
                $variable->name = new MissingToken(TokenKind::VariableName, $token->fullStart);
            }

            return $variable;
        };
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

            if ($this->checkToken(TokenKind::AmpersandToken)) {
                $arrayElement->byRef = $this->eat(TokenKind::AmpersandToken);
                $arrayElement->elementValue = $this->parseExpression($arrayElement);
            } else {
                $expression = $this->parseExpression($arrayElement);
                if ($this->checkToken(TokenKind::DoubleArrowToken)) {
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

    private function parseArrayCreationExpression($parentNode) {
        $arrayExpression = new ArrayCreationExpression();
        $arrayExpression->parent = $parentNode;

        $arrayExpression->arrayKeyword = $this->eatOptional(TokenKind::ArrayKeyword);

        $arrayExpression->openParenOrBracket = $arrayExpression->arrayKeyword !== null
            ? $this->eat(TokenKind::OpenParenToken)
            : $this->eat(TokenKind::OpenBracketToken);

        $arrayExpression->arrayElements = $this->parseArrayElementList($arrayExpression);

        $arrayExpression->closeParenOrBracket = $arrayExpression->arrayKeyword !== null
            ? $this->eat(TokenKind::CloseParenToken)
            : $this->eat(TokenKind::CloseBracketToken);

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
        $unaryOpExpression->operand = $this->parseUnaryExpressionOrHigher($unaryOpExpression);

        return $unaryOpExpression;
    }

    private function parseErrorControlExpression($parentNode) {
        $errorControlExpression = new ErrorControlExpression();
        $errorControlExpression->parent = $parentNode;

        $errorControlExpression->operator = $this->eat(TokenKind::AtSymbolToken);
        $errorControlExpression->operand = $this->parseUnaryExpressionOrHigher($errorControlExpression);

        return $errorControlExpression;
    }

    private function parsePrefixUpdateExpression($parentNode) {
        $prefixUpdateExpression = new PrefixUpdateExpression();
        $prefixUpdateExpression->parent = $parentNode;

        $prefixUpdateExpression->incrementOrDecrementOperator = $this->eat(TokenKind::PlusPlusToken, TokenKind::MinusMinusToken);
        $prefixUpdateExpression->operand = $this->parseSimpleVariable($prefixUpdateExpression);

        return $prefixUpdateExpression;
    }

    private function parsePostfixExpressionRest($expression) {
        $tokenKind = $this->getCurrentToken()->kind;

        if ($tokenKind === TokenKind::PlusPlusToken ||
            $tokenKind === TokenKind::MinusMinusToken) {
            return $this->parseParsePostfixUpdateExpression($expression);
        }

        if (!($expression instanceof Variable ||
            $expression instanceof ParenthesizedExpression ||
            $expression instanceof QualifiedName ||
            $expression instanceof CallExpression ||
            $expression instanceof StringLiteral ||
            $expression instanceof ArrayCreationExpression
        )) {
            return $expression;
        }

        if ($tokenKind === TokenKind::ColonColonToken) {
            $expression = $this->parseScopedPropertyAccessExpression($expression);
        }

        while (true) {
            $tokenKind = $this->getCurrentToken()->kind;

            if ($tokenKind === TokenKind::OpenBraceToken ||
                $tokenKind === TokenKind::OpenBracketToken) {
                $expression = $this->parseSubscriptExpression($expression);
                continue;
            }

            if ($expression instanceof ArrayCreationExpression) {
                return $expression;
            } else if ($tokenKind === TokenKind::ArrowToken) {
                $expression = $this->parseMemberAccessExpression($expression);
                continue;
            } else if ($tokenKind === TokenKind::OpenParenToken) {
                return $this->parseCallExpressionRest($expression);
            }

            return $expression;
        }
    }

    private function parseMemberName($parentNode) {
        $token = $this->getCurrentToken();
        switch ($token->kind) {
            case TokenKind::Name:
                $this->advanceToken();
                return $token;
            case TokenKind::VariableName:
            case TokenKind::DollarToken:
                return $this->parseSimpleVariable($parentNode); // TODO should be simple-variable
            case TokenKind::OpenBraceToken:
                return $this->parseBracedExpression($parentNode);
            default:
                // TODO support keyword names
        }
        return new MissingToken(TokenKind::MemberName, $token->fullStart);
    }

    private function isArgumentExpressionStartFn() {
        return function($token) {
            return
                $token->kind === TokenKind::DotDotDotToken ? true : $this->isExpressionStart($token);
        };
    }

    private function parseArgumentExpressionFn() {
        return function ($parentNode) {
            $argumentExpression = new ArgumentExpression();
            $argumentExpression->parent = $parentNode;
            $argumentExpression->byRefToken = $this->eatOptional(TokenKind::AmpersandToken);
            $argumentExpression->dotDotDotToken = $this->eatOptional(TokenKind::DotDotDotToken);
            $argumentExpression->expression = $this->parseExpression($argumentExpression);
            return $argumentExpression;
        };
    }

    private function parseCallExpressionRest($expression) {
        while ($this->getCurrentToken()->kind === TokenKind::OpenParenToken) {
            $callExpression = new CallExpression();
            $callExpression->parent = $expression->parent;
            $expression->parent = $callExpression;
            $callExpression->callableExpression = $expression;
            $callExpression->openParen = $this->eat(TokenKind::OpenParenToken);
            $callExpression->argumentExpressionList =
                $this->parseArgumentExpressionList($callExpression);
            $callExpression->closeParen = $this->eat(TokenKind::CloseParenToken);
            $expression = $callExpression;
        }
        return $this->parsePostfixExpressionRest($expression);
    }

    private function parseParsePostfixUpdateExpression($prefixExpression) {
        $postfixUpdateExpression = new PostfixUpdateExpression();
        $postfixUpdateExpression->operand = $prefixExpression;
        $postfixUpdateExpression->parent = $prefixExpression->parent;
        $prefixExpression->parent = $postfixUpdateExpression;
        $postfixUpdateExpression->incrementOrDecrementOperator =
            $this->eat(TokenKind::PlusPlusToken, TokenKind::MinusMinusToken);
        return $postfixUpdateExpression;
    }

    private function parseBracedExpression($parentNode) {
        $bracedExpression = new BracedExpression();
        $bracedExpression->parent = $parentNode;

        $bracedExpression->openBrace = $this->eat(TokenKind::OpenBraceToken);
        $bracedExpression->expression = $this->parseExpression($bracedExpression);
        $bracedExpression->closeBrace = $this->eat(TokenKind::CloseBraceToken);

        return $bracedExpression;
    }

    private function parseSubscriptExpression($expression) : SubscriptExpression {
        $subscriptExpression = new SubscriptExpression();
        $subscriptExpression->parent = $expression->parent;
        $expression->parent = $subscriptExpression;

        $subscriptExpression->postfixExpression = $expression;
        $subscriptExpression->openBracketOrBrace = $this->eat(TokenKind::OpenBracketToken, TokenKind::OpenBraceToken);
        $subscriptExpression->accessExpression = $this->parseExpression($subscriptExpression);
        if ($subscriptExpression->openBracketOrBrace->kind === TokenKind::OpenBraceToken) {
            $subscriptExpression->closeBracketOrBrace = $this->eat(TokenKind::CloseBraceToken);
        } else {
            $subscriptExpression->closeBracketOrBrace = $this->eat(TokenKind::CloseBracketToken);
        }

        return $subscriptExpression;
    }

    private function parseMemberAccessExpression($expression):MemberAccessExpression {
        $memberAccessExpression = new MemberAccessExpression();
        $memberAccessExpression->parent = $expression->parent;
        $expression->parent = $memberAccessExpression;

        $memberAccessExpression->dereferencableExpression = $expression;
        $memberAccessExpression->arrowToken = $this->eat(TokenKind::ArrowToken);
        $memberAccessExpression->memberName = $this->parseMemberName($memberAccessExpression);

        return $memberAccessExpression;
    }

    private function parseScopedPropertyAccessExpression($expression):ScopedPropertyAccessExpression {
        $scopedPropertyAccessExpression = new ScopedPropertyAccessExpression();
        $scopedPropertyAccessExpression->parent = $expression->parent;
        $expression->parent = $scopedPropertyAccessExpression;

        $scopedPropertyAccessExpression->scopeResolutionQualifier = $expression;
        $scopedPropertyAccessExpression->doubleColon = $this->eat(TokenKind::ColonColonToken);
        $scopedPropertyAccessExpression->memberName = $this->parseMemberName($scopedPropertyAccessExpression);

        return $scopedPropertyAccessExpression;
    }

    private function parseObjectCreationExpression($parentNode) {
        $objectCreationExpression = new ObjectCreationExpression();
        $objectCreationExpression->parent = $parentNode;
        $objectCreationExpression->newKeword = $this->eat(TokenKind::NewKeyword);
        $objectCreationExpression->classTypeDesignator =
            $this->parseQualifiedName($objectCreationExpression) ??
            $this->eatOptional(TokenKind::ClassKeyword) ??
            $this->parseSimpleVariable($objectCreationExpression);

        $objectCreationExpression->openParen = $this->eatOptional(TokenKind::OpenParenToken);
        if ($objectCreationExpression->openParen !== null) {
            $objectCreationExpression->argumentExpressionList = $this->parseArgumentExpressionList($objectCreationExpression);
            $objectCreationExpression->closeParen = $this->eat(TokenKind::CloseParenToken);
        }

        // TODO parse extends, implements

        if ($this->getCurrentToken()->kind === TokenKind::OpenBraceToken) {
            $objectCreationExpression->classMembers = $this->parseClassMembers($objectCreationExpression);
        }

        return $objectCreationExpression;
    }

    private function parseArgumentExpressionList($parentNode) {
        return $this->parseDelimitedList(
            TokenKind::CommaToken,
            $this->isArgumentExpressionStartFn(),
            $this->parseArgumentExpressionFn(),
            $parentNode
        );
    }

    private function parseTernaryExpression($leftOperand, $questionToken):TernaryExpression {
        $ternaryExpression = new TernaryExpression();
        $ternaryExpression->parent = $leftOperand->parent;
        $leftOperand->parent = $ternaryExpression;
        $ternaryExpression->condition = $leftOperand;
        $ternaryExpression->questionToken = $questionToken;
        $ternaryExpression->ifExpression = $this->parseExpression($ternaryExpression);
        $ternaryExpression->colonToken = $this->eat(TokenKind::ColonToken);
        $ternaryExpression->elseExpression = $this->parseBinaryExpressionOrHigher(9, $ternaryExpression);
        $leftOperand = $ternaryExpression;
        return $leftOperand;
    }

    private function parseClassInterfaceClause($parentNode) {
        $classInterfaceClause = new ClassInterfaceClause();
        $classInterfaceClause->parent = $parentNode;
        $classInterfaceClause->implementsKeyword = $this->eatOptional(TokenKind::ImplementsKeyword);

        if ($classInterfaceClause->implementsKeyword === null) {
            return null;
        }

        $classInterfaceClause->interfaceNameList =
            $this->parseQualifiedNameList($classInterfaceClause);
        return $classInterfaceClause;
    }

    private function parseClassBaseClause($parentNode) {
        $classBaseClause = new ClassBaseClause();
        $classBaseClause->parent = $parentNode;

        $classBaseClause->extendsKeyword = $this->eatOptional(TokenKind::ExtendsKeyword);
        if ($classBaseClause->extendsKeyword === null) {
            return null;
        }
        $classBaseClause->baseClass = $this->parseQualifiedName($classBaseClause);

        return $classBaseClause;
    }

    private function parseClassConstDeclaration($parentNode, $modifiers) {
        $classConstDeclaration = new ClassConstDeclaration();
        $classConstDeclaration->parent = $parentNode;

        $classConstDeclaration->modifiers = $modifiers;
        $classConstDeclaration->constKeyword = $this->eat(TokenKind::ConstKeyword);
        $classConstDeclaration->constElements = $this->parseConstElements($classConstDeclaration);
        $classConstDeclaration->semicolon = $this->eat(TokenKind::SemicolonToken);

        return $classConstDeclaration;
    }

    private function parsePropertyDeclaration($parentNode, $modifiers) {
        $propertyDeclaration = new PropertyDeclaration();
        $propertyDeclaration->parent = $parentNode;

        $propertyDeclaration->modifiers = $modifiers;
        $propertyDeclaration->propertyElements = $this->parseExpressionList($propertyDeclaration);
        $propertyDeclaration->semicolon = $this->eat(TokenKind::SemicolonToken);

        return $propertyDeclaration;
    }

    private function parseQualifiedNameList($parentNode) {
        return $this->parseDelimitedList(
            TokenKind::CommaToken,
            $this->isQualifiedNameStartFn(),
            $this->parseQualifiedNameFn(),
            $parentNode);
    }

    private function parseInterfaceDeclaration($parentNode) {
        $interfaceDeclaration = new InterfaceDeclaration();
        $interfaceDeclaration->parent = $parentNode;
        $interfaceDeclaration->interfaceKeyword = $this->eat(TokenKind::InterfaceKeyword);
        $interfaceDeclaration->name = $this->eat(TokenKind::Name);
        $interfaceDeclaration->interfaceBaseClause = $this->parseInterfaceBaseClause($interfaceDeclaration);
        $interfaceDeclaration->interfaceMembers = $this->parseInterfaceMembers($interfaceDeclaration);
        return $interfaceDeclaration;
    }

    function parseInterfaceMembers($parentNode) : Node {
        $interfaceMembers = new InterfaceMembers();
        $interfaceMembers->openBrace = $this->eat(TokenKind::OpenBraceToken);
        $interfaceMembers->interfaceMemberDeclarations = $this->parseList($interfaceMembers, ParseContext::InterfaceMembers);
        $interfaceMembers->closeBrace = $this->eat(TokenKind::CloseBraceToken);
        $interfaceMembers->parent = $parentNode;
        return $interfaceMembers;
    }

    private function isInterfaceMemberDeclarationStart($token) {
        switch ($token->kind) {
            // visibility-modifier
            case TokenKind::PublicKeyword:
            case TokenKind::ProtectedKeyword:
            case TokenKind::PrivateKeyword:

            // static-modifier
            case TokenKind::StaticKeyword:

            // class-modifier
            case TokenKind::AbstractKeyword:
            case TokenKind::FinalKeyword:

            case TokenKind::ConstKeyword:

            case TokenKind::FunctionKeyword:
                return true;
        }
        return false;
    }

    private function parseInterfaceElementFn() {
        return function($parentNode) {
            $modifiers = $this->parseModifiers();

            $token = $this->getCurrentToken();
            switch($token->kind) {
                case TokenKind::ConstKeyword:
                    return $this->parseClassConstDeclaration($parentNode, $modifiers);

                case TokenKind::FunctionKeyword:
                    return $this->parseMethodDeclaration($parentNode, $modifiers);

                default:
                    $missingInterfaceMemberDeclaration = new MissingMemberDeclaration();
                    $missingInterfaceMemberDeclaration->parent = $parentNode;
                    $missingInterfaceMemberDeclaration->modifiers = $modifiers;
                    return $missingInterfaceMemberDeclaration;
            }
        };
    }

    private function parseInterfaceBaseClause($parentNode) {
        $interfaceBaseClause = new InterfaceBaseClause();
        $interfaceBaseClause->parent = $parentNode;

        $interfaceBaseClause->extendsKeyword = $this->eatOptional(TokenKind::ExtendsKeyword);
        if (isset($interfaceBaseClause->extendsKeyword)) {
            $interfaceBaseClause->interfaceNameList = $this->parseQualifiedNameList($interfaceBaseClause);
        }

        return $interfaceBaseClause;
    }

    private function parseNamespaceDefinition($parentNode) {
        $namespaceDefinition = new NamespaceDefinition();
        $namespaceDefinition->parent = $parentNode;

        $namespaceDefinition->namespaceKeyword = $this->eat(TokenKind::NamespaceKeyword);

        if (!$this->checkToken(TokenKind::NamespaceKeyword)) {
            $namespaceDefinition->name = $this->parseQualifiedName($namespaceDefinition); // TODO only optional with compound statement block
        }

        $namespaceDefinition->compoundStatementOrSemicolon =
            $this->checkToken(TokenKind::OpenBraceToken) ?
                $this->parseCompoundStatement($namespaceDefinition) : $this->eat(TokenKind::SemicolonToken);

        return $namespaceDefinition;
    }

    private function parseNamespaceUseDeclaration($parentNode) {
        $namespaceUseDeclaration = new NamespaceUseDeclaration();
        $namespaceUseDeclaration->parent = $parentNode;

        $namespaceUseDeclaration->useKeyword = $this->eat(TokenKind::UseKeyword);
        $namespaceUseDeclaration->functionOrConst = $this->eatOptional(TokenKind::FunctionKeyword, TokenKind::ConstKeyword);
        $namespaceUseDeclaration->namespaceName = $this->parseQualifiedName($namespaceUseDeclaration);
        if (!$this->checkToken(TokenKind::OpenBraceToken)) {
            if ($this->checkToken(TokenKind::AsKeyword)) {
                $namespaceUseDeclaration->namespaceAliasingClause = $this->parseNamespaceAliasingClause($namespaceUseDeclaration);
            }
        } else {
            $namespaceUseDeclaration->openBrace = $this->eat(TokenKind::OpenBraceToken);
            $namespaceUseDeclaration->groupClauses = $this->parseDelimitedList(
                TokenKind::CommaToken,
                function ($token) {
                    return $this->isQualifiedNameStart($token) || $token->kind === TokenKind::FunctionKeyword || $token->kind === TokenKind::ConstKeyword;
                },
                function ($parentNode) {
                    $namespaceUseGroupClause = new NamespaceUseGroupClause();
                    $namespaceUseGroupClause->parent = $parentNode;

                    $namespaceUseGroupClause->functionOrConst = $this->eatOptional(TokenKind::FunctionKeyword, TokenKind::ConstKeyword);
                    $namespaceUseGroupClause->namespaceName = $this->parseQualifiedName($namespaceUseGroupClause);
                    if ($this->checkToken(TokenKind::AsKeyword)) {
                        $namespaceUseGroupClause->namespaceAliasingClause = $this->parseNamespaceAliasingClause($namespaceUseGroupClause);
                    }

                    return $namespaceUseGroupClause;
                },
                $namespaceUseDeclaration
            );
            $namespaceUseDeclaration->closeBrace = $this->eat(TokenKind::CloseBraceToken);

        }
        $namespaceUseDeclaration->semicolon = $this->eat(TokenKind::SemicolonToken);
        return $namespaceUseDeclaration;
    }

    private function parseNamespaceAliasingClause($parentNode) {
        $namespaceAliasingClause = new NamespaceAliasingClause();
        $namespaceAliasingClause->parent = $parentNode;
        $namespaceAliasingClause->asKeyword = $this->eat(TokenKind::AsKeyword);
        $namespaceAliasingClause->name = $this->eat(TokenKind::Name);
        return $namespaceAliasingClause;
    }

    private function parseTraitDeclaration($parentNode) {
        $traitDeclaration = new TraitDeclaration();
        $traitDeclaration->parent = $parentNode;

        $traitDeclaration->traitKeyword = $this->eat(TokenKind::TraitKeyword);
        $traitDeclaration->name = $this->eat(TokenKind::Name);

        $traitDeclaration->traitMembers = $this->parseTraitMembers($traitDeclaration);

        return $traitDeclaration;
    }

    private function parseTraitMembers($parentNode) {
        $traitMembers = new TraitMembers();
        $traitMembers->parent = $parentNode;

        $traitMembers->openBrace = $this->eat(TokenKind::OpenBraceToken);

        $traitMembers->traitMemberDeclarations = $this->parseList($traitMembers, ParseContext::TraitMembers);

        $traitMembers->closeBrace = $this->eat(TokenKind::CloseBraceToken);

        return $traitMembers;
    }

    private function isTraitMemberDeclarationStart($token) {
        switch ($token->kind) {
            // property-declaration
            case TokenKind::VariableName:

            // modifiers
            case TokenKind::PublicKeyword:
            case TokenKind::ProtectedKeyword:
            case TokenKind::PrivateKeyword:
            case TokenKind::VarKeyword:
            case TokenKind::StaticKeyword:
            case TokenKind::AbstractKeyword:
            case TokenKind::FinalKeyword:

            // method-declaration
            case TokenKind::FunctionKeyword:

            // trait-use-clauses
            case TokenKind::UseKeyword:
                return true;
        }
        return false;
    }

    private function parseTraitElementFn() {
        return function($parentNode) {
            $modifiers = $this->parseModifiers();

            $token = $this->getCurrentToken();
            switch($token->kind) {
                case TokenKind::FunctionKeyword:
                    return $this->parseMethodDeclaration($parentNode, $modifiers);

                case TokenKind::VariableName:
                    return $this->parsePropertyDeclaration($parentNode, $modifiers);

                case TokenKind::UseKeyword:
                    return $this->parseTraitUseClause($parentNode);

                default:
                    $missingTraitMemberDeclaration = new MissingMemberDeclaration();
                    $missingTraitMemberDeclaration->parent = $parentNode;
                    $missingTraitMemberDeclaration->modifiers = $modifiers;
                    return $missingTraitMemberDeclaration;
            }
        };
    }

    private function parseTraitUseClause($parentNode) {
        $traitUseClause = new TraitUseClause();
        $traitUseClause->parent = $parentNode;

        $traitUseClause->useKeyword = $this->eat(TokenKind::UseKeyword);
        $traitUseClause->traitNameList = $this->parseDelimitedList(
            TokenKind::CommaToken,
            $this->isQualifiedNameStartFn(),
            $this->parseQualifiedNameFn(),
            $traitUseClause
            );

        $traitUseClause->semicolonOrOpenBrace = $this->eat(TokenKind::OpenBraceToken, TokenKind::SemicolonToken);
        if ($traitUseClause->semicolonOrOpenBrace->kind === TokenKind::OpenBraceToken) {
            $traitUseClause->traitSelectAndAliasClauses = $this->parseDelimitedList(
                TokenKind::SemicolonToken,
                function ($token) {
                    return $token->kind === TokenKind::Name;
                },
                function ($parentNode) {
                    $traitSelectAndAliasClause = new TraitSelectOrAliasClause();
                    $traitSelectAndAliasClause->parent = $parentNode;
                    $traitSelectAndAliasClause->name = // TODO update spec
                        $this->parseQualifiedNameOrScopedPropertyAccessExpression($traitSelectAndAliasClause);

                    $traitSelectAndAliasClause->asOrInsteadOfKeyword = $this->eat(TokenKind::AsKeyword, TokenKind::InsteadOfKeyword);
                    $traitSelectAndAliasClause->modifiers = $this->parseModifiers(); // TODO accept all modifiers, verify later

                    $traitSelectAndAliasClause->targetName =
                        $this->parseQualifiedNameOrScopedPropertyAccessExpression($traitSelectAndAliasClause);

                    // TODO errors for insteadof/as
                    return $traitSelectAndAliasClause;
                },
                $traitUseClause
            );
            $traitUseClause->closeBrace = $this->eat(TokenKind::CloseBraceToken);
        }

        return $traitUseClause;
    }

    private function parseQualifiedNameOrScopedPropertyAccessExpression($parentNode) {
        $qualifiedNameOrScopedProperty = $this->parseQualifiedName($parentNode);
        if ($this->getCurrentToken()->kind === TokenKind::ColonColonToken) {
            $qualifiedNameOrScopedProperty = $this->parseScopedPropertyAccessExpression($qualifiedNameOrScopedProperty);
        }
        return $qualifiedNameOrScopedProperty;
    }

    function parseGlobalDeclaration($parentNode) {
        $globalDeclaration = new GlobalDeclaration();
        $globalDeclaration->parent = $parentNode;

        $globalDeclaration->globalKeyword = $this->eat(TokenKind::GlobalKeyword);
        $globalDeclaration->variableNameList = $this->parseDelimitedList(
            TokenKind::CommaToken,
            $this->isVariableNameStartFn(),
            $this->parseSimpleVariableFn(),
            $globalDeclaration
        );

        $globalDeclaration->semicolon = $this->eat(TokenKind::SemicolonToken);

        return $globalDeclaration;
    }

    function parseFunctionStaticDeclaration($parentNode) {
        $functionStaticDeclaration = new FunctionStaticDeclaration();
        $functionStaticDeclaration->parent = $parentNode;

        $functionStaticDeclaration->staticKeyword = $this->eat(TokenKind::StaticKeyword);
        $functionStaticDeclaration->staticVariableNameList = $this->parseDelimitedList(
            TokenKind::CommaToken,
            function ($token) {
                return $token->kind === TokenKind::VariableName;
            },
            $this->parseStaticVariableDeclarationFn(),
            $functionStaticDeclaration
        );
        $functionStaticDeclaration->semicolon = $this->eat(TokenKind::SemicolonToken);

        return $functionStaticDeclaration;
    }

    function isVariableNameStartFn() {
        return function ($token) {
            return $token->kind === TokenKind::VariableName || $token->kind === TokenKind::DollarToken;
        };
    }

    function parseStaticVariableDeclarationFn() {
        return function ($parentNode) {
            $staticVariableDeclaration = new StaticVariableDeclaration();
            $staticVariableDeclaration->parent = $parentNode;
            $staticVariableDeclaration->variableName = $this->eat(TokenKind::VariableName);
            $staticVariableDeclaration->equalsToken = $this->eatOptional(TokenKind::EqualsToken);
            if ($staticVariableDeclaration->equalsToken !== null) {
                // TODO add post-parse rule that checks for invalid assignments
                $staticVariableDeclaration->assignment = $this->parseExpression($staticVariableDeclaration);
            }
            return $staticVariableDeclaration;
        };
    }

    function parseConstDeclaration($parentNode) {
        $constDeclaration = new ConstDeclaration();
        $constDeclaration->parent = $parentNode;

        $constDeclaration->constKeyword = $this->eat(TokenKind::ConstKeyword);
        $constDeclaration->constElements = $this->parseConstElements($constDeclaration);
        $constDeclaration->semicolon = $this->eat(TokenKind::SemicolonToken);

        return $constDeclaration;
    }

    function parseConstElements($parentNode) {
        return $this->parseDelimitedList(
            TokenKind::CommaToken,
            function ($token) {
                return $token->kind === TokenKind::Name;
            },
            $this->parseConstElementFn(),
            $parentNode);
    }

    function parseConstElementFn() {
        return function ($parentNode) {
            $constElement = new ConstElement();
            $constElement->parent = $parentNode;
            $constElement->name = $this->eat(TokenKind::Name);
            $constElement->equalsToken = $this->eat(TokenKind::EqualsToken);
            // TODO add post-parse rule that checks for invalid assignments
            $constElement->assignment = $this->parseExpression($constElement);
            return $constElement;
        };
    }

    private function parseCastExpression($parentNode) {
        $castExpression = new CastExpression();
        $castExpression->parent = $parentNode;

        $castExpression->openParen = $this->eat(TokenKind::OpenParenToken);
        $castExpression->castType = $this->eat(
            TokenKind::ArrayKeyword,
            TokenKind::BinaryReservedWord,
            TokenKind::BoolReservedWord,
            TokenKind::BooleanReservedWord,
            TokenKind::DoubleReservedWord,
            TokenKind::IntReservedWord,
            TokenKind::IntegerReservedWord,
            TokenKind::FloatReservedWord,
            TokenKind::ObjectReservedWord,
            TokenKind::RealReservedWord,
            TokenKind::StringReservedWord,
            TokenKind::UnsetKeyword
        );
        $castExpression->closeParen = $this->eat(TokenKind::CloseParenToken);
        $castExpression->operand = $this->parseUnaryExpressionOrHigher($castExpression);

        return $castExpression;
    }

    private function parseAnonymousFunctionCreationExpression($parentNode) {
        $anonymousFunctionCreationExpression = new AnonymousFunctionCreationExpression();
        $anonymousFunctionCreationExpression->parent = $parentNode;

        $anonymousFunctionCreationExpression->staticModifier = $this->eatOptional(TokenKind::StaticKeyword);
        $this->parseFunctionDefinition($anonymousFunctionCreationExpression, false, true);

        return $anonymousFunctionCreationExpression;
    }

    private function parseAnonymousFunctionUseClause($parentNode) {
        $anonymousFunctionUseClause = new AnonymousFunctionUseClause();
        $anonymousFunctionUseClause->parent = $parentNode;

        $anonymousFunctionUseClause->useKeyword = $this->eatOptional(TokenKind::UseKeyword);
        if ($anonymousFunctionUseClause->useKeyword === null) {
            return null;
        }
        $anonymousFunctionUseClause->openParen = $this->eat(TokenKind::OpenParenToken);
        $anonymousFunctionUseClause->useVariableNameList = $this->parseDelimitedList(
            TokenKind::CommaToken,
            function ($token) {
                return $token->kind === TokenKind::AmpersandToken || $token->kind === TokenKind::VariableName;
            },
            function ($parentNode) {
                $useVariableName = new UseVariableName();
                $useVariableName->parent = $parentNode;
                $useVariableName->byRef = $this->eatOptional(TokenKind::AmpersandToken);
                $useVariableName->variableName = $this->eat(TokenKind::VariableName);
                return $useVariableName;
            },
            $anonymousFunctionUseClause
        );
        $anonymousFunctionUseClause->closeParen = $this->eat(TokenKind::CloseParenToken);

        return $anonymousFunctionUseClause;
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
    const InterfaceMembers = 10;
    const TraitMembers = 11;
    const Count = 12;
}
