<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser;

require_once(__DIR__ . "/Token.php");
require_once(__DIR__ . "/characterCodes.php");
require_once(__DIR__ . "/ITokenStreamProvider.php");

class PhpTokenizer implements ITokenStreamProvider {

    public $pos;
    public $endOfFilePos;
    private $token;

    public $inScriptSection = false;

    private $tokensArray;

    public function __construct($content) {
        $tokens = \token_get_all($content);
        $this->initialize($tokens);
        $this->pos = 0;
    }

    public function scanNextToken() : Token {
        return $this->pos >= $this->endOfFilePos
            ? $this->tokensArray[$this->endOfFilePos]
            : $this->tokensArray[$this->pos++];
    }

    function getCurrentPosition() : int {
        return $this->pos;
    }

    function setCurrentPosition(int $pos) {
        $this->pos = $pos;
    }

    function getEndOfFilePosition() : int {
        return $this->endOfFilePos;
    }

    function getTokensArray() : array {
        return $this->tokensArray;
    }

    function initialize($tokens) {
        $arr = array();
        $fullStart = 0;
        $start = 0;
        $pos = 0;

        foreach ($tokens as $token) {
            if (\is_array($token)) {
                $tokenKind = $token[0];
                $strlen = \strlen($token[1]);
            } else {
                $tokenKind = $token;
                $strlen = \strlen($token);
            }

            $pos += $strlen;

            switch ($tokenKind) {
                case T_OPEN_TAG:
                    $arr[] = new Token(TokenKind::ScriptSectionStartTag, $fullStart, $start, $pos-$fullStart);
                    $start = $fullStart = $pos;
                    continue;

                case T_WHITESPACE:
                case T_COMMENT:
                case T_DOC_COMMENT:
                    $start += $strlen;
                    continue;

                case T_STRING:
                    $name = \strtolower($token[1]);
                    if (isset(RESERVED_WORDS[$name])) {
                        $newTokenKind = RESERVED_WORDS[$name];
                        $arr[] = new Token($newTokenKind, $fullStart, $start, $pos - $fullStart);
                        $start = $fullStart = $pos;
                        continue;
                    }

                default:
                    $newTokenKind = isset(TOKEN_MAP[$tokenKind])
                        ? TOKEN_MAP[$tokenKind]
                        : $newTokenKind = TokenKind::Unknown;
                    $arr[] = new Token($newTokenKind, $fullStart, $start, $pos - $fullStart);
                    $start = $fullStart = $pos;
                    continue;
            }
        }

        $arr[] = new Token(TokenKind::EndOfFileToken, $fullStart, $start, $pos - $fullStart);
        $this->tokensArray = $arr;
        $this->endOfFilePos = \count($arr) - 1;
    }
}

const KEYWORDS = array(
    T_ABSTRACT => TokenKind::AbstractKeyword,
    T_LOGICAL_AND => TokenKind::AndKeyword,
    T_ARRAY => TokenKind::ArrayKeyword,
    T_AS => TokenKind::AsKeyword,
    T_BREAK => TokenKind::BreakKeyword,
    T_CALLABLE => TokenKind::CallableKeyword,
    T_CASE => TokenKind::CaseKeyword,
    T_CATCH => TokenKind::CatchKeyword,
    T_CLASS => TokenKind::ClassKeyword,
    T_CLONE => TokenKind::CloneKeyword,
    T_CONST => TokenKind::ConstKeyword,
    T_CONTINUE => TokenKind::ContinueKeyword,
    T_DECLARE => TokenKind::DeclareKeyword,
    T_DEFAULT => TokenKind::DefaultKeyword,
    T_EXIT => TokenKind::DieKeyword,
    T_DO => TokenKind::DoKeyword,
    T_ECHO => TokenKind::EchoKeyword,
    T_ELSE => TokenKind::ElseKeyword,
    T_ELSEIF => TokenKind::ElseIfKeyword,
    T_EMPTY => TokenKind::EmptyKeyword,
    T_ENDDECLARE => TokenKind::EndDeclareKeyword,
    T_ENDFOR => TokenKind::EndForKeyword,
    T_ENDFOREACH => TokenKind::EndForEachKeyword,
    T_ENDIF => TokenKind::EndIfKeyword,
    T_ENDSWITCH => TokenKind::EndSwitchKeyword,
    T_ENDWHILE => TokenKind::EndWhileKeyword,
    T_EVAL => TokenKind::EvalKeyword,
    T_EXIT => TokenKind::ExitKeyword,
    T_EXTENDS => TokenKind::ExtendsKeyword,
    T_FINAL => TokenKind::FinalKeyword,
    T_FINALLY => TokenKind::FinallyKeyword,
    T_FOR => TokenKind::ForKeyword,
    T_FOREACH => TokenKind::ForeachKeyword,
    T_FUNCTION => TokenKind::FunctionKeyword,
    T_GLOBAL => TokenKind::GlobalKeyword,
    T_GOTO => TokenKind::GotoKeyword,
    T_IF => TokenKind::IfKeyword,
    T_IMPLEMENTS => TokenKind::ImplementsKeyword,
    T_INCLUDE => TokenKind::IncludeKeyword,
    T_INCLUDE_ONCE => TokenKind::IncludeOnceKeyword,
    T_INSTANCEOF => TokenKind::InstanceOfKeyword,
    T_INSTEADOF => TokenKind::InsteadOfKeyword,
    T_INTERFACE => TokenKind::InterfaceKeyword,
    T_ISSET => TokenKind::IsSetKeyword,
    T_LIST => TokenKind::ListKeyword,
    T_NAMESPACE => TokenKind::NamespaceKeyword,
    T_NEW => TokenKind::NewKeyword,
    T_LOGICAL_OR => TokenKind::OrKeyword,
    T_PRINT => TokenKind::PrintKeyword,
    T_PRIVATE => TokenKind::PrivateKeyword,
    T_PROTECTED => TokenKind::ProtectedKeyword,
    T_PUBLIC => TokenKind::PublicKeyword,
    T_REQUIRE => TokenKind::RequireKeyword,
    T_REQUIRE_ONCE => TokenKind::RequireOnceKeyword,
    T_RETURN => TokenKind::ReturnKeyword,
    T_STATIC => TokenKind::StaticKeyword,
    T_SWITCH => TokenKind::SwitchKeyword,
    T_THROW => TokenKind::ThrowKeyword,
    T_TRAIT => TokenKind::TraitKeyword,
    T_TRY => TokenKind::TryKeyword,
    T_UNSET => TokenKind::UnsetKeyword,
    T_USE => TokenKind::UseKeyword,
    T_VAR => TokenKind::VarKeyword,
    T_WHILE => TokenKind::WhileKeyword,
    T_LOGICAL_XOR => TokenKind::XorKeyword,
    T_YIELD => TokenKind::YieldKeyword,
    T_YIELD_FROM => TokenKind::YieldFromKeyword,


    // TODO soft reserved words?
);

const RESERVED_WORDS = [
    // http://php.net/manual/en/reserved.constants.php
    // TRUE, FALSE, NULL are special predefined constants
    // TODO - also consider adding other constants
    "true" => TokenKind::TrueReservedWord,
    "false" => TokenKind::FalseReservedWord,
    "null" => TokenKind::NullReservedWord,

    // RESERVED WORDS:
    // http://php.net/manual/en/reserved.other-reserved-words.php
    "int" => TokenKind::IntReservedWord,
    "float" => TokenKind::FloatReservedWord,
    "bool" => TokenKind::BoolReservedWord,
    "string" => TokenKind::StringReservedWord,
    "binary" => TokenKind::BinaryReservedWord,
    "boolean" => TokenKind::BooleanReservedWord,
    "double" => TokenKind::DoubleReservedWord,
    "integer" => TokenKind::IntegerReservedWord,
    "object" => TokenKind::ObjectReservedWord,
    "real" => TokenKind::RealReservedWord,
    "void" => TokenKind::VoidReservedWord
];

const OPERATORS_AND_PUNCTUATORS = array(
    "[" => TokenKind::OpenBracketToken,
    "]" => TokenKind::CloseBracketToken,
    "(" => TokenKind::OpenParenToken,
    ")" => TokenKind::CloseParenToken,
    "{" => TokenKind::OpenBraceToken,
    "}" => TokenKind::CloseBraceToken,
    "." => TokenKind::DotToken,
    T_OBJECT_OPERATOR => TokenKind::ArrowToken,
    T_DOUBLE_ARROW => TokenKind::DoubleArrowToken,
    T_INC => TokenKind::PlusPlusToken,
    T_DEC => TokenKind::MinusMinusToken,
    T_POW => TokenKind::AsteriskAsteriskToken,
    "*" => TokenKind::AsteriskToken,
    "+" => TokenKind::PlusToken,
    "-" => TokenKind::MinusToken,
    "~" => TokenKind::TildeToken,
    "!" => TokenKind::ExclamationToken,
    "$" => TokenKind::DollarToken,
    "/" => TokenKind::SlashToken,
    "%" => TokenKind::PercentToken,
    T_SL => TokenKind::LessThanLessThanToken,
    T_SR => TokenKind::GreaterThanGreaterThanToken,
    "<" => TokenKind::LessThanToken,
    ">" => TokenKind::GreaterThanToken,
    T_IS_SMALLER_OR_EQUAL => TokenKind::LessThanEqualsToken,
    T_IS_GREATER_OR_EQUAL => TokenKind::GreaterThanEqualsToken,
    T_IS_EQUAL => TokenKind::EqualsEqualsToken,
    T_IS_IDENTICAL => TokenKind::EqualsEqualsEqualsToken,
    T_IS_NOT_EQUAL => TokenKind::ExclamationEqualsToken,
    T_IS_NOT_IDENTICAL => TokenKind::ExclamationEqualsEqualsToken,
    "^" => TokenKind::CaretToken,
    "|" => TokenKind::BarToken,
    "&" => TokenKind::AmpersandToken,
    T_BOOLEAN_AND => TokenKind::AmpersandAmpersandToken,
    T_BOOLEAN_OR => TokenKind::BarBarToken,
    "?" => TokenKind::QuestionToken,
    ":" => TokenKind::ColonToken,
    T_DOUBLE_COLON => TokenKind::ColonColonToken,
    ";" => TokenKind::SemicolonToken,
    "=" => TokenKind::EqualsToken,
    T_POW_EQUAL => TokenKind::AsteriskAsteriskEqualsToken,
    T_MUL_EQUAL => TokenKind::AsteriskEqualsToken,
    T_DIV_EQUAL => TokenKind::SlashEqualsToken,
    T_MOD_EQUAL => TokenKind::PercentEqualsToken,
    T_PLUS_EQUAL => TokenKind::PlusEqualsToken,
    T_MINUS_EQUAL => TokenKind::MinusEqualsToken,
    T_CONCAT_EQUAL => TokenKind::DotEqualsToken,
    T_SL_EQUAL => TokenKind::LessThanLessThanEqualsToken,
    T_SR_EQUAL => TokenKind::GreaterThanGreaterThanEqualsToken,
    T_AND_EQUAL => TokenKind::AmpersandEqualsToken,
    T_XOR_EQUAL => TokenKind::CaretEqualsToken,
    T_OR_EQUAL => TokenKind::BarEqualsToken,
    "," => TokenKind::CommaToken,
    T_COALESCE => TokenKind::QuestionQuestionToken,
    T_SPACESHIP => TokenKind::LessThanEqualsGreaterThanToken,
    T_IS_NOT_EQUAL => TokenKind::LessThanGreaterThanToken,
    T_ELLIPSIS => TokenKind::DotDotDotToken,
    T_NS_SEPARATOR => TokenKind::BackslashToken,
    T_OPEN_TAG_WITH_ECHO => TokenKind::ScriptSectionStartTag, // TODO, technically not an operator
    T_OPEN_TAG => TokenKind::ScriptSectionStartTag, // TODO, technically not an operator
    T_CLOSE_TAG => TokenKind::ScriptSectionEndTag, // TODO, technically not an operator
    "@" => TokenKind::AtSymbolToken, // TODO not in spec
    "`" => TokenKind::BacktickToken,
    T_VARIABLE => TokenKind::VariableName
);

const TOKEN_MAP = [
//    T_BAD_CHARACTER => TokenKind::Unknown,
//    => EndOfFileToken,

    T_CLASS_C => TokenKind::Name,
    T_DIR => TokenKind::Name,
    T_FILE => TokenKind::Name,
    T_FUNC_C => TokenKind::Name,
    T_HALT_COMPILER => TokenKind::Name,
    T_METHOD_C => TokenKind::Name,
    T_NS_C => TokenKind::Name,
    T_TRAIT_C => TokenKind::Name,
    T_LINE => TokenKind::Name,

    T_STRING => TokenKind::Name,
    T_VARIABLE => TokenKind::VariableName,

//    => TokenKind::SkippedToken,
//    => TokenKind::MissingToken,

//    => TokenKind::QualifiedName,


    T_ABSTRACT => TokenKind::AbstractKeyword,
    T_LOGICAL_AND => TokenKind::AndKeyword,
    T_ARRAY => TokenKind::ArrayKeyword,
    T_AS => TokenKind::AsKeyword,
    T_BREAK => TokenKind::BreakKeyword,
    T_CALLABLE => TokenKind::CallableKeyword,
    T_CASE => TokenKind::CaseKeyword,
    T_CATCH => TokenKind::CatchKeyword,
    T_CLASS => TokenKind::ClassKeyword,
    T_CLONE => TokenKind::CloneKeyword,
    T_CONST => TokenKind::ConstKeyword,
    T_CONTINUE => TokenKind::ContinueKeyword,
    T_DECLARE => TokenKind::DeclareKeyword,
    T_DEFAULT => TokenKind::DefaultKeyword,
    T_EXIT => TokenKind::DieKeyword,
    T_DO => TokenKind::DoKeyword,
    T_ECHO => TokenKind::EchoKeyword,
    T_ELSE => TokenKind::ElseKeyword,
    T_ELSEIF => TokenKind::ElseIfKeyword,
    T_EMPTY => TokenKind::EmptyKeyword,
    T_ENDDECLARE => TokenKind::EndDeclareKeyword,
    T_ENDFOR => TokenKind::EndForKeyword,
    T_ENDFOREACH => TokenKind::EndForEachKeyword,
    T_ENDIF => TokenKind::EndIfKeyword,
    T_ENDSWITCH => TokenKind::EndSwitchKeyword,
    T_ENDWHILE => TokenKind::EndWhileKeyword,
    T_EVAL => TokenKind::EvalKeyword,
    T_EXIT => TokenKind::ExitKeyword,
    T_EXTENDS => TokenKind::ExtendsKeyword,
    T_FINAL => TokenKind::FinalKeyword,
    T_FINALLY => TokenKind::FinallyKeyword,
    T_FOR => TokenKind::ForKeyword,
    T_FOREACH => TokenKind::ForeachKeyword,
    T_FUNCTION => TokenKind::FunctionKeyword,
    T_GLOBAL => TokenKind::GlobalKeyword,
    T_GOTO => TokenKind::GotoKeyword,
    T_IF => TokenKind::IfKeyword,
    T_IMPLEMENTS => TokenKind::ImplementsKeyword,
    T_INCLUDE => TokenKind::IncludeKeyword,
    T_INCLUDE_ONCE => TokenKind::IncludeOnceKeyword,
    T_INSTANCEOF => TokenKind::InstanceOfKeyword,
    T_INSTEADOF => TokenKind::InsteadOfKeyword,
    T_INTERFACE => TokenKind::InterfaceKeyword,
    T_ISSET => TokenKind::IsSetKeyword,
    T_LIST => TokenKind::ListKeyword,
    T_NAMESPACE => TokenKind::NamespaceKeyword,
    T_NEW => TokenKind::NewKeyword,
    T_LOGICAL_OR => TokenKind::OrKeyword,
    T_PRINT => TokenKind::PrintKeyword,
    T_PRIVATE => TokenKind::PrivateKeyword,
    T_PROTECTED => TokenKind::ProtectedKeyword,
    T_PUBLIC => TokenKind::PublicKeyword,
    T_REQUIRE => TokenKind::RequireKeyword,
    T_REQUIRE_ONCE => TokenKind::RequireOnceKeyword,
    T_RETURN => TokenKind::ReturnKeyword,
    T_STATIC => TokenKind::StaticKeyword,
    T_SWITCH => TokenKind::SwitchKeyword,
    T_THROW => TokenKind::ThrowKeyword,
    T_TRAIT => TokenKind::TraitKeyword,
    T_TRY => TokenKind::TryKeyword,
    T_UNSET => TokenKind::UnsetKeyword,
    T_USE => TokenKind::UseKeyword,
    T_VAR => TokenKind::VarKeyword,
    T_WHILE => TokenKind::WhileKeyword,
    T_LOGICAL_XOR => TokenKind::XorKeyword,
    T_YIELD => TokenKind::YieldKeyword,
    T_YIELD_FROM => TokenKind::YieldFromKeyword,

    "[" => TokenKind::OpenBracketToken,
    "]" => TokenKind::CloseBracketToken,
    "(" => TokenKind::OpenParenToken,
    ")" => TokenKind::CloseParenToken,
    "{" => TokenKind::OpenBraceToken,
    "}" => TokenKind::CloseBraceToken,
    "." => TokenKind::DotToken,
    T_OBJECT_OPERATOR => TokenKind::ArrowToken,
    T_INC => TokenKind::PlusPlusToken,
    T_DEC => TokenKind::MinusMinusToken,
    T_POW => TokenKind::AsteriskAsteriskToken,
    "*" => TokenKind::AsteriskToken,
    "+" => TokenKind::PlusToken,
    "-" => TokenKind::MinusToken,
    "~" => TokenKind::TildeToken,
    "!" => TokenKind::ExclamationToken,
    "$" => TokenKind::DollarToken,
    "/" => TokenKind::SlashToken,
    "%" => TokenKind::PercentToken,
    T_SL => TokenKind::LessThanLessThanToken,
    T_SR => TokenKind::GreaterThanGreaterThanToken,
    "<" => TokenKind::LessThanToken,
    ">" => TokenKind::GreaterThanToken,
    T_IS_SMALLER_OR_EQUAL => TokenKind::LessThanEqualsToken,
    T_IS_GREATER_OR_EQUAL => TokenKind::GreaterThanEqualsToken,
    T_IS_EQUAL => TokenKind::EqualsEqualsToken,
    T_IS_IDENTICAL => TokenKind::EqualsEqualsEqualsToken,
    T_IS_NOT_EQUAL => TokenKind::ExclamationEqualsToken,
    T_IS_NOT_IDENTICAL => TokenKind::ExclamationEqualsEqualsToken,
    "^" => TokenKind::CaretToken,
    "|" => TokenKind::BarToken,
    "&" => TokenKind::AmpersandToken,
    T_BOOLEAN_AND => TokenKind::AmpersandAmpersandToken,
    T_BOOLEAN_OR => TokenKind::BarBarToken,
    ":" => TokenKind::ColonToken,
    ";" => TokenKind::SemicolonToken,
    "=" => TokenKind::EqualsToken,
    T_POW_EQUAL => TokenKind::AsteriskAsteriskEqualsToken,
    T_MUL_EQUAL => TokenKind::AsteriskEqualsToken,
    T_DIV_EQUAL => TokenKind::SlashEqualsToken,
    T_MOD_EQUAL => TokenKind::PercentEqualsToken,
    T_PLUS_EQUAL => TokenKind::PlusEqualsToken,
    T_MINUS_EQUAL => TokenKind::MinusEqualsToken,
    T_CONCAT_EQUAL => TokenKind::DotEqualsToken,
    T_SL_EQUAL => TokenKind::LessThanLessThanEqualsToken,
    T_SR_EQUAL => TokenKind::GreaterThanGreaterThanEqualsToken,
    T_AND_EQUAL => TokenKind::AmpersandEqualsToken,
    T_XOR_EQUAL => TokenKind::CaretEqualsToken,
    T_OR_EQUAL => TokenKind::BarEqualsToken,
    "," => TokenKind::CommaToken,
    T_COALESCE => TokenKind::QuestionQuestionToken,
    T_SPACESHIP => TokenKind::LessThanEqualsGreaterThanToken,
    T_ELLIPSIS => TokenKind::DotDotDotToken,
    T_NS_SEPARATOR => TokenKind::BackslashToken,
    T_PAAMAYIM_NEKUDOTAYIM => TokenKind::ColonColonToken,
    T_DOUBLE_ARROW => TokenKind::DoubleArrowToken, // TODO missing from spec
//    T_IS_NOT_EQUAL => TokenKind::LessThanGreaterThanToken, // TODO missing from spec
    "@" => TokenKind::AtSymbolToken,
    "`" => TokenKind::BacktickToken,
    "?" => TokenKind::QuestionToken,

    T_LNUMBER => TokenKind::IntegerLiteralToken,
//    T_LNUMBER => TokenKind::OctalLiteralToken,
//    T_LNUMBER => TokenKind::HexadecimalLiteralToken,
//    T_LNUMBER => TokenKind::BinaryLiteralToken,
    T_DNUMBER => TokenKind::FloatingLiteralToken,
//    T_LNUMBER => TokenKind::InvalidOctalLiteralToken,
//    T_LNUMBER => TokenKind::InvalidHexadecimalLiteral,
//    T_LNUMBER => TokenKind::InvalidBinaryLiteral,
    T_CONSTANT_ENCAPSED_STRING => TokenKind::StringLiteralToken,
//    T_CONSTANT_ENCAPSED_STRING => TokenKind::UnterminatedStringLiteralToken,

//    => TokenKind::TemplateStringStart,
//    => TokenKind::TemplateStringMiddle,
//    => TokenKind::TemplateStringEnd,
//    => TokenKind::NoSubstitutionTemplateLiteral,
//    => TokenKind::UnterminatedNoSubstitutionTemplateLiteral,
//    => TokenKind::UnterminatedTemplateStringEnd,
//
//    => TokenKind::IntReservedWord,
//    => TokenKind::FloatReservedWord,
//    => TokenKind::TrueReservedWord,
//    => TokenKind::StringReservedWord,
//    => TokenKind::BoolReservedWord,
//    => TokenKind::NullReservedWord,

    T_OPEN_TAG => TokenKind::ScriptSectionStartTag,
    T_OPEN_TAG_WITH_ECHO => TokenKind::ScriptSectionStartTag,
    T_CLOSE_TAG => TokenKind::ScriptSectionEndTag,

//    => TokenKind::ScriptSectionPrependedText,
//    => TokenKind::VoidReservedWord,
//    => TokenKind::FalseReservedWord,

//    => TokenKind::MemberName,
//    =>TokenKind:: TokenKind::Expression,

//    => TokenKind::BinaryReservedWord, // TODO better way
//    => TokenKind::BooleanReservedWord,
//    => TokenKind::DoubleReservedWord,
//    => TokenKind::IntegerReservedWord,
//    => TokenKind::ObjectReservedWord,
//    => TokenKind::RealReservedWord,
//    => TokenKind::ReturnType,
    T_INLINE_HTML => TokenKind::InlineHtml,

    "\"" => TokenKind::DoubleQuoteToken,
    "'" => TokenKind::SingleQuoteToken,
    T_ENCAPSED_AND_WHITESPACE => TokenKind::EncapsedAndWhitespace,
    T_DOLLAR_OPEN_CURLY_BRACES => TokenKind::DollarOpenBraceToken,
    T_CURLY_OPEN => TokenKind::OpenBraceDollarToken,
    T_CONSTANT_ENCAPSED_STRING => TokenKind::StringLiteralToken,

    T_ARRAY_CAST        => TokenKind::ArrayCastToken,
    T_BOOL_CAST         => TokenKind::BoolCastToken,
    T_DOUBLE_CAST       => TokenKind::DoubleCastToken,
    T_INT_CAST          => TokenKind::IntCastToken,
    T_OBJECT_CAST       => TokenKind::ObjectCastToken,
    T_STRING_CAST       => TokenKind::StringCastToken,
    T_UNSET_CAST        => TokenKind::UnsetCastToken,
    T_START_HEREDOC     => TokenKind::HeredocStart,
    T_END_HEREDOC       => TokenKind::HeredocEnd,
    T_STRING_VARNAME    => TokenKind::VariableName
];