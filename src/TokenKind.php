<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser;

class TokenKind {
    const Unknown = 0;
    const EndOfFileToken = 1;

    // begin_expression
    const VariableName = 2;

    // end_expression
    const SkippedToken = 3;
    const MissingToken = 4;
    const QualifiedName = 5;

    // begin_name_or_keyword_or_reserved
    const Name = 6;


    // begin_keyword
    const AbstractKeyword = 100;
    const AndKeyword = 101;

    // begin_parameter_type_declaration
    const ArrayKeyword = 102;
    const CallableKeyword = 103;

    // end_parameter_type_declaration

    // begin_statement
    const IfKeyword = 104;
    const SwitchKeyword = 105;
    const WhileKeyword = 106;
    const DoKeyword = 107;
    const ForKeyword = 108;
    const ForeachKeyword = 109;
    const GotoKeyword = 110;
    const ContinueKeyword = 111;
    const BreakKeyword = 112;
    const ReturnKeyword = 113;
    const ThrowKeyword = 114;
    const TryKeyword = 115;
    const DeclareKeyword = 116;
    const ConstKeyword = 117;
    const ClassKeyword = 118;
    const FinalKeyword = 119;
    const InterfaceKeyword = 120;
    const TraitKeyword = 121;
    const NamespaceKeyword = 122;
    const UseKeyword = 123;
    const GlobalKeyword = 124;

    // end_statement
    const AsKeyword = 125;
    const CaseKeyword = 126;
    const CatchKeyword = 127;
    const DefaultKeyword = 128;
    const ElseKeyword = 129;
    const ElseIfKeyword = 130;
    const EndDeclareKeyword = 131;
    const EndForKeyword = 132;
    const EndForEachKeyword = 133;
    const EndIfKeyword = 134;
    const EndSwitchKeyword = 135;
    const EndWhileKeyword = 136;
    const ExtendsKeyword = 137;
    const FinallyKeyword = 138;
    const ImplementsKeyword = 139;
    const InstanceOfKeyword = 140;
    const InsteadOfKeyword = 141;
    const OrKeyword = 142;
    const PrivateKeyword = 143;
    const ProtectedKeyword = 144;
    const PublicKeyword = 145;
    const VarKeyword = 146;
    const XorKeyword = 147;

    // begin_expression

    // script inclusion expression
    const RequireKeyword = 148;
    const RequireOnceKeyword = 149;
    const IncludeKeyword = 150;
    const IncludeOnceKeyword = 151;

    // yield expression
    const YieldKeyword = 152;
    const YieldFromKeyword = 153;

    // object creation expression
    const CloneKeyword = 154;
    const NewKeyword = 155;

    // intrinsic constructor
    const EchoKeyword = 156;
    const ListKeyword = 157;
    const UnsetKeyword = 158;

    // intrinsic operator
    const EmptyKeyword = 159;
    const EvalKeyword = 160;
    const ExitKeyword = 161;
    const DieKeyword = 162;
    const IsSetKeyword = 163;
    const PrintKeyword = 164;

    // anonymous function creation expression
    const FnKeyword = 165;

    // begin_statement
    const FunctionKeyword = 166;
    const StaticKeyword = 167;

    // end_statement

    // end_expression

    // end_keyword


    // begin_reserved
    const TrueReservedWord = 201;
    const FalseReservedWord = 202;
    const NullReservedWord = 203;

    // begin_parameter_type_declaration
    const IntReservedWord = 204;
    const FloatReservedWord = 205;
    const BoolReservedWord = 206;
    const StringReservedWord = 207;
    const ObjectReservedWord = 208;

    // end_parameter_type_declaration
    const BinaryReservedWord = 209;
    const IntegerReservedWord = 210;
    const DoubleReservedWord = 211;
    const BooleanReservedWord = 212;
    const RealReservedWord = 213;
    const VoidReservedWord = 214;

    // end_reserved

    // end_name_or_keyword_or_reserved

    // begin_statement
    const OpenBraceToken = 215;
    const SemicolonToken = 216;

    // end_statement
    const CloseBracketToken = 217;
    const CloseParenToken = 218;
    const CloseBraceToken = 219;
    const DotToken = 220;
    const ArrowToken = 221;
    const AsteriskAsteriskToken = 222;
    const AsteriskToken = 223;
    const SlashToken = 224;
    const PercentToken = 225;
    const LessThanLessThanToken = 226;
    const GreaterThanGreaterThanToken = 227;
    const LessThanToken = 228;
    const GreaterThanToken = 229;
    const LessThanEqualsToken = 230;
    const GreaterThanEqualsToken = 231;
    const EqualsEqualsToken = 232;
    const EqualsEqualsEqualsToken = 233;
    const ExclamationEqualsToken = 234;
    const ExclamationEqualsEqualsToken = 235;
    const CaretToken = 236;
    const BarToken = 237;
    const AmpersandToken = 238;
    const AmpersandAmpersandToken = 239;
    const BarBarToken = 240;
    const ColonToken = 241;
    const EqualsToken = 242;
    const AsteriskAsteriskEqualsToken = 243;
    const AsteriskEqualsToken = 244;
    const SlashEqualsToken = 245;
    const PercentEqualsToken = 246;
    const PlusEqualsToken = 247;
    const MinusEqualsToken = 248;
    const DotEqualsToken = 249;
    const LessThanLessThanEqualsToken = 250;
    const GreaterThanGreaterThanEqualsToken = 251;
    const AmpersandEqualsToken = 252;
    const CaretEqualsToken = 253;
    const BarEqualsToken = 254;
    const CommaToken = 255;
    const QuestionQuestionToken = 256;
    const LessThanEqualsGreaterThanToken = 257;
    const DotDotDotToken = 258;
    const ColonColonToken = 259;
    const DoubleArrowToken = 260;
    const LessThanGreaterThanToken = 261;
    const QuestionToken = 262;
    const QuestionQuestionEqualsToken = 263;

    // begin_expression

    // unary op expression
    const PlusToken = 264;
    const MinusToken = 265;
    const TildeToken = 266;
    const ExclamationToken = 267;

    // error control expression
    const AtSymbolToken = 268;

    // prefix increment expression
    const PlusPlusToken = 269;

    // prefix decrement expression
    const MinusMinusToken = 270;
    const OpenBracketToken = 271;
    const OpenParenToken = 272;
    const DollarToken = 273;
    const BackslashToken = 274;

    // literal
    const DecimalLiteralToken = 275;
    const OctalLiteralToken = 276;
    const HexadecimalLiteralToken = 277;
    const BinaryLiteralToken = 278;
    const FloatingLiteralToken = 279;
    const InvalidOctalLiteralToken = 280;
    const InvalidHexadecimalLiteral = 281;
    const InvalidBinaryLiteral = 282;
    const StringLiteralToken = 283;
    const SingleQuoteToken = 284;
    const DoubleQuoteToken = 285;
    const HeredocStart = 286;
    const BacktickToken = 287;

    // end_expression


    const ScriptSectionStartTag = 317;

    // begin_statement
    const ScriptSectionEndTag = 318;

    // end_statement
    const ScriptSectionStartWithEchoTag = 319;

    // TODO how to handle incremental parsing w/ this?
    const ScriptSectionPrependedText = 320;
    const MemberName = 321;
    const Expression = 322;
    const ReturnType = 323;
    const InlineHtml = 324;
    const PropertyType = 325;

    // DollarOpenCurly

    const EncapsedAndWhitespace = 400;
    const DollarOpenBraceToken = 401;
    const OpenBraceDollarToken = 402;
    const CastToken = 403;
    const HeredocEnd = 404;
    const StringVarname = 405;

    // begin_cast
    const UnsetCastToken = 406;
    const StringCastToken = 407;
    const ObjectCastToken = 408;
    const IntCastToken = 409;
    const DoubleCastToken = 410;
    const BoolCastToken = 411;
    const ArrayCastToken = 412;

    // end_cast
    const IntegerLiteralToken = 413;
    const CommentToken = 414;
    const DocCommentToken = 415;

    // TODO type annotations - PHP7

    public static function isExpression(int $tokenKind): bool
    {
        return (2 === $tokenKind) || (147 < $tokenKind && $tokenKind < 201) ||(263 < $tokenKind && $tokenKind < 317);
    }

    public static function isNameOrKeywordOrReserved(int $tokenKind): bool
    {
        return 5 < $tokenKind && $tokenKind < 215;
    }

    public static function isKeyword(int $tokenKind): bool
    {
        return 6 < $tokenKind && $tokenKind < 201;
    }

    public static function isParameterTypeDeclaration(int $tokenKind): bool
    {
        return (101 < $tokenKind && $tokenKind < 104) || (203 < $tokenKind && $tokenKind < 209);
    }

    public static function isStatement(int $tokenKind): bool
    {
        return (103 < $tokenKind && $tokenKind < 125) || (165 < $tokenKind && $tokenKind < 201) || (214 < $tokenKind && $tokenKind < 217) || (318 === $tokenKind);
    }

    public static function isReserved(int $tokenKind): bool
    {
        return 167 < $tokenKind && $tokenKind < 215;
    }

    public static function isCast(int $tokenKind): bool
    {
        return 405 < $tokenKind && $tokenKind < 413;
    }
}
