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

use PhpParser\Node\ClassMembersNode;
use PhpParser\Node\ClassNode;
use PhpParser\Node\DelimitedList;
use PhpParser\Node\EmptyStatementNode;
use PhpParser\Node\Expression;
use PhpParser\Node\Function_;
use PhpParser\Node\FunctionDefinition;
use PhpParser\Node\CompoundStatementNode;
use PhpParser\Node\MethodNode;
use PhpParser\Node\Node;
use PhpParser\Node\Parameter;
use PhpParser\Node\QualifiedName;
use PhpParser\Node\RelativeSpecifier;
use PhpParser\Node\SourceFile;
use PhpParser\Node\TemplateExpressionNode;

class Parser {

    private $lexer;

    private $currentParseContext;
    public $sourceFile;

    public function __construct($filename) {
        $this->lexer = new Lexer($filename);
    }

    public function parseSourceFile() : Node {
        $this->reset();

        $sourceFile = new SourceFile();
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
            case ParseContext::BlockStatements:
                return $this->parseStatement();
            case ParseContext::ClassMembers:
                return $this->parseClassElement();
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

    function parseStatement() {
        return function($parentNode) {
            switch($this->getCurrentToken()->kind) {
                case TokenKind::OpenBraceToken:
                    return $this->parseCompoundStatement($parentNode);
                case TokenKind::ClassKeyword:
                    return $this->parseClassDeclaration($parentNode);
                case TokenKind::FunctionKeyword:
                    return $this->parseFunctionDeclaration($parentNode);



                case TokenKind::TemplateStringStart:
                    return $this->parseTemplateString($parentNode);

                case TokenKind::SemicolonToken:
                    return $this->parseEmptyStatement($parentNode);

                default:
                    return $this->parsePrimaryExpression($parentNode);
            }
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
        switch($token->kind) {
            case TokenKind::NoSubstitutionTemplateLiteral:
            case TokenKind::TemplateStringStart:
            return true;
        }

        return false;
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
        switch ($this->getCurrentToken()) {
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

            default:
                // TODO
                $token = $this->getCurrentToken();
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

    /**
     * @param $node
     */
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
}

class ParseContext {
    const SourceElements = 0;
    const BlockStatements = 1;
    const ClassMembers = 2;
    const Count = 3;
}
