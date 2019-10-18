<?php


$tokens = [

    "Unknown",
    "EndOfFileToken",

    "begin_expression",

    "VariableName",

    "end_expression",

    "SkippedToken",
    "MissingToken",
    "QualifiedName",

    "begin_name_or_keyword_or_reserved",

    "Name",

    "jump 100",

    "begin_keyword",

    "AbstractKeyword",
    "AndKeyword",

    "begin_parameter_type_declaration",

    "ArrayKeyword",
    "CallableKeyword",

    "end_parameter_type_declaration",

    "begin_statement",

    "IfKeyword",
    "SwitchKeyword",
    "WhileKeyword",
    "DoKeyword",
    "ForKeyword",
    "ForeachKeyword",
    "GotoKeyword",
    "ContinueKeyword",
    "BreakKeyword",
    "ReturnKeyword",
    "ThrowKeyword",
    "TryKeyword",
    "DeclareKeyword",
    "ConstKeyword",
    "ClassKeyword",
    "FinalKeyword",
    "InterfaceKeyword",
    "TraitKeyword",
    "NamespaceKeyword",
    "UseKeyword",
    "GlobalKeyword",

    "end_statement",

    "AsKeyword",
    "CaseKeyword",
    "CatchKeyword",
    "DefaultKeyword",
    "ElseKeyword",
    "ElseIfKeyword",
    "EndDeclareKeyword",
    "EndForKeyword",
    "EndForEachKeyword",
    "EndIfKeyword",
    "EndSwitchKeyword",
    "EndWhileKeyword",
    "ExtendsKeyword",
    "FinallyKeyword",
    "ImplementsKeyword",
    "InstanceOfKeyword",
    "InsteadOfKeyword",
    "OrKeyword",
    "PrivateKeyword",
    "ProtectedKeyword",
    "PublicKeyword",
    "VarKeyword",
    "XorKeyword",

    "begin_expression",

    "comment script inclusion expression",

    "RequireKeyword",
    "RequireOnceKeyword",
    "IncludeKeyword",
    "IncludeOnceKeyword",

    "comment yield expression",

    "YieldKeyword",
    "YieldFromKeyword",

    "comment object creation expression",

    "CloneKeyword",
    "NewKeyword",

    "comment intrinsic constructor",

    "EchoKeyword",
    "ListKeyword",
    "UnsetKeyword",

    "comment intrinsic operator",

    "EmptyKeyword",
    "EvalKeyword",
    "ExitKeyword",
    "DieKeyword",
    "IsSetKeyword",
    "PrintKeyword",

    "comment anonymous function creation expression",

    "FnKeyword",
    
    "begin_statement",
    
    "FunctionKeyword",
    "StaticKeyword",
    
    "end_statement",

    "end_expression",

    "end_keyword",

    "jump 201",

    // RESERVED WORDS

    "begin_reserved",


    "TrueReservedWord",
    "FalseReservedWord",
    "NullReservedWord",

    "begin_parameter_type_declaration",

    "IntReservedWord",
    "FloatReservedWord",
    "BoolReservedWord",
    "StringReservedWord",
    "ObjectReservedWord",

    "end_parameter_type_declaration",

    "BinaryReservedWord", // TODO bet",
    "IntegerReservedWord",
    "DoubleReservedWord",
    "BooleanReservedWord",
    "RealReservedWord",
    "VoidReservedWord",

    "end_reserved",

    "end_name_or_keyword_or_reserved",

    "begin_statement",

    "OpenBraceToken",
    "SemicolonToken",

    "end_statement",

    "CloseBracketToken",
    "CloseParenToken",
    "CloseBraceToken",
    "DotToken",
    "ArrowToken",
    "AsteriskAsteriskToken",
    "AsteriskToken",
    "SlashToken",
    "PercentToken",
    "LessThanLessThanToken",
    "GreaterThanGreaterThanToken",
    "LessThanToken",
    "GreaterThanToken",
    "LessThanEqualsToken",
    "GreaterThanEqualsToken",
    "EqualsEqualsToken",
    "EqualsEqualsEqualsToken",
    "ExclamationEqualsToken",
    "ExclamationEqualsEqualsToken",
    "CaretToken",
    "BarToken",
    "AmpersandToken",
    "AmpersandAmpersandToken",
    "BarBarToken",
    "ColonToken",
    "EqualsToken",
    "AsteriskAsteriskEqualsToken",
    "AsteriskEqualsToken",
    "SlashEqualsToken",
    "PercentEqualsToken",
    "PlusEqualsToken",
    "MinusEqualsToken",
    "DotEqualsToken",
    "LessThanLessThanEqualsToken",
    "GreaterThanGreaterThanEqualsToken",
    "AmpersandEqualsToken",
    "CaretEqualsToken",
    "BarEqualsToken",
    "CommaToken",
    "QuestionQuestionToken",
    "LessThanEqualsGreaterThanToken",
    "DotDotDotToken",
    "ColonColonToken",
    "DoubleArrowToken", // TODO missing from spec
    "LessThanGreaterThanToken", // TODO missing from spec
    "QuestionToken",
    "QuestionQuestionEqualsToken",

    "begin_expression",

    "comment unary op expression",

    "PlusToken",
    "MinusToken",
    "TildeToken",
    "ExclamationToken",

    "comment error control expression",

    "AtSymbolToken",

    "comment prefix increment expression",

    "PlusPlusToken",

    "comment prefix decrement expression",

    "MinusMinusToken",

    "OpenBracketToken",
    "OpenParenToken",

    "DollarToken",
    "BackslashToken",

    "comment literal",

    "DecimalLiteralToken",
    "OctalLiteralToken",
    "HexadecimalLiteralToken",
    "BinaryLiteralToken",
    "FloatingLiteralToken",
    "InvalidOctalLiteralToken",
    "InvalidHexadecimalLiteral",
    "InvalidBinaryLiteral",
    "StringLiteralToken",

    "SingleQuoteToken",
    "DoubleQuoteToken",
    "HeredocStart",
    "BacktickToken",

    "end_expression",

    "jump 301",

    "jump 317",



    "ScriptSectionStartTag",

    "begin_statement",

    "ScriptSectionEndTag",

    "end_statement",

    "ScriptSectionStartWithEchoTag",


    "TODO how to handle incremental parsing w/ this?",

    "ScriptSectionPrependedText",

    "MemberName",
    "Expression",

    "ReturnType",
    "InlineHtml",
    "PropertyType",

    "comment DollarOpenCurly",

    "jump 400",

    "EncapsedAndWhitespace",
    "DollarOpenBraceToken",
    "OpenBraceDollarToken",
    "CastToken",
    "HeredocEnd",
    "StringVarname",

    "begin_cast",

    "UnsetCastToken",
    "StringCastToken",
    "ObjectCastToken",
    "IntCastToken",
    "DoubleCastToken",
    "BoolCastToken",
    "ArrayCastToken",

   "end_cast",

    "IntegerLiteralToken",
    "CommentToken",
    "DocCommentToken",
    "TODO type annotations - PHP7"
];