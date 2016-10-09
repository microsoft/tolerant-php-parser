<?php
namespace PhpParser;

require_once(__DIR__ . "/Token.php");

class Lexer {

    function getTokensArray($filename) {
        $fileContents = file_get_contents($filename);
        $endOfFilePos = strlen($fileContents);

        // TODO figure out how to optimize memory
        // $tokensArray = new SplFixedArray($strLen);
        $tokensArray = array();

        $pos = 0;
        do {
            $token = $this->scanNextToken($fileContents, $pos, $endOfFilePos);
            array_push($tokensArray, $token);
        } while ($token->kind != TokenKind::EndOfFileToken);

        return $tokensArray;
    }

    public function scanNextToken(string $text, int & $pos, int $endOfFilePos) : Token {
        $fullStart = $pos;

        while (true) {
            $start = $pos;
            if ($pos >= $endOfFilePos) {
                return new Token(TokenKind::EndOfFileToken, $fullStart, $start, $pos - $fullStart);
            }

            // TODO skip past <?php
            $char = $text[$pos];

            switch ($char) {
                case "#":
                    $this->scanSingleLineComment($text, $pos, $endOfFilePos);
                    continue;

                case " ":
                case "\t":
                case "\r":
                case "\n":
                    $pos++;
                    continue;

                // Potential 3-char compound
                case ".": // ..., .=, .
                case "<": // <=>, <=, <<=, <<, <
                case "=": // ===, ==, =
                case ">": // >>=, >>, >=, >
                case "*": // **=, **, *=, *
                case "!": // !==, !=, !

                // Potential 2-char compound
                case "+": // +=, ++, +
                case "-": // -= , --, ->, -
                case "%": // %=, %
                case "^": // ^=, ^
                case "|": // |=, ||, |
                case "&": // &=, &&, &
                case "?": // ??, ?

                case ":": // : (TODO should this actually be treated as compound?)
                case ",": // , (TODO should this actually be treated as compound?)

                // Non-compound
                case "[":
                case "]":
                case "(":
                case ")":
                case "{":
                case "}":
                case ";":
                case "~":
                case "\\":
                    // TODO this can be made more performant, but we're going for simple/correct first.
                    for ($tokenEnd = 2; $tokenEnd >= 0; $tokenEnd--) {
                        if ($pos + $tokenEnd >= $endOfFilePos) {
                            continue;
                        }

                        $textSubstring = substr($text, $pos, $tokenEnd + 1);
                        if ($this->isOperatorOrPunctuator($textSubstring)) {
                            $tokenKind = OPERATORS_AND_PUNCTUATORS[$textSubstring];
                            $pos += $tokenEnd + 1;
                            return new Token($tokenKind, $fullStart, $start, $pos - $fullStart);
                        }
                    }

                    throw new \Exception("Unknown token kind");

                case "/":
                    if ($this->isSingleLineCommentStart($text, $pos, $endOfFilePos)) {
                        $this->scanSingleLineComment($text, $pos, $endOfFilePos);
                        continue;
                    } else if ($this->isDelimitedCommentStart($text, $pos, $endOfFilePos)) {
                        $this->scanDelimitedComment($text, $pos, $endOfFilePos);
                        continue;
                    } else if (isset($text[$pos+1]) && $text[$pos+1] === "=") {
                        $pos+=2;
                        return new Token(TokenKind::SlashEqualsToken, $fullStart, $start, $pos - $fullStart);
                    }
                    $pos++;
                    return new Token(TokenKind::SlashToken, $fullStart, $start, $pos - $fullStart);

                case "$":
                    $pos++;
                    if ($this->isNameStart($text, $pos, $endOfFilePos)) {
                        $this->scanName($text, $pos, $endOfFilePos);
                        return new Token(TokenKind::VariableName, $fullStart, $start, $pos - $fullStart);
                    }
                    return new Token(TokenKind::DollarToken, $fullStart, $start, $pos - $fullStart);

                default:
                    if ($this->isNameStart($text, $pos, $endOfFilePos)) {
                        $this->scanName($text, $pos, $endOfFilePos);
                        $token = new Token(TokenKind::Name, $fullStart, $start, $pos - $fullStart);
                        $tokenText = $token->getTextForToken($text);
                        if ($this->isKeyword($tokenText)) {
                            $token->kind = $this->getTokenKindForKeyword($tokenText);
                        }
                        return $token;
                    }
                    $pos++;
                    return new Token(TokenKind::Unknown, $fullStart, $start, $pos - $fullStart);
            }
        }
    }

    /**
     * Returns case-insensitive token kind given a string.
     * @param $text
     * @return int
     */
    function getTokenKindForKeyword($nameText) : int {
        return KEYWORDS[strtolower($nameText)];
    }

    function isKeyword($nameText) : bool {
        return isset(KEYWORDS[strtolower($nameText)]);
    }

    function isOperatorOrPunctuator($text): bool {
        return isset(OPERATORS_AND_PUNCTUATORS[$text]);
    }

    function isSingleLineCommentStart($text, $pos, $endOfFilePos) : bool {
        return
            $pos+1 < $endOfFilePos &&
            $text[$pos] === "/" &&
            $text[$pos+1] === "/";
    }

    function scanSingleLineComment($text, & $pos, $endOfFilePos) {
        while ($pos < $endOfFilePos) {
            if ($this->isNewLineChar($text[$pos])) {
                return;
            }
            $pos++;
        }
    }

    function isDelimitedCommentStart($text, $pos, $endOfFilePos) : bool {
        return
            $pos + 1 < $endOfFilePos &&
            $text[$pos] === "/" &&
            $text[$pos+1] === "*";
    }

    function scanDelimitedComment($text, & $pos, $endOfFilePos) {
        while ($pos < $endOfFilePos) {
            if (($pos + 1 < $endOfFilePos && $text[$pos] === "*" && $text[$pos + 1] === "/")) {
                $pos += 2;
                return;
            }
            $pos++;
        }
    }

    function isNameStart($text, $pos, $endOfFilePos) : bool {
        return
            $pos < $endOfFilePos &&
            $this->isNameNonDigitChar($text[$pos]);
    }

    function scanName($text, & $pos, $endOfFilePos) {
        while ($pos < $endOfFilePos) {
            $char = $text[$pos];
            if ($this->isNameNonDigitChar($char) || $this->isDigitChar($char)) {
                $pos++;
                continue;
            }
            return;
        }
    }

    function isNewLineChar($char) : bool {
        return
            $char === "\n" ||
            $char === "\r";
    }

    function isNameNonDigitChar($char) : bool {
        return
            $this->isNonDigitChar($char) ||
            $this->isValidNameUnicodeChar($char);
    }

    /**
     * valid chars: U+0080–U+00ff
     * @param $char
     * @return bool
     */
    function isValidNameUnicodeChar($char) : bool {
        return
            $char >= "\u{0080}" &&
            $char <= "\u{00ff}";
    }

    /**
     * NonDigit is defined as '_' or 'a-z' or 'A-Z'
     * @param $char
     * @return bool
     */
    function isNonDigitChar($char) : bool {
        $asciiCode = ord($char);
        return
            ($asciiCode >= 65 && $asciiCode <= 90) ||
            ($asciiCode >= 97 && $asciiCode <= 122) ||
            $asciiCode === 95;
    }

    function isDigitChar($char) : bool {
        $asciiCode = ord($char);
        return
            $asciiCode >= 48 &&
            $asciiCode <= 57;
    }

    function isNonzeroDigitChar($char) : bool {
        $asciiCode = ord($char);
        return
            $asciiCode > 48 &&
            $asciiCode <= 57;
    }

    function isOctalDigitChar($char) : bool {
        $asciiCode = ord($char);
        return
            $asciiCode >= 48 &&
            $asciiCode <= 55;
    }

    function isBinaryDigitChar($char) : bool {
        $asciiCode = ord($char);
        return
            $asciiCode === 48 ||
            $asciiCode === 49;
    }

    function isHexadecimalDigit($char) {
        // 0  1  2  3  4  5  6  7  8  9
        // a  b  c  d  e  f
        // A  B  C  D  E  F
    }

    function isDecimalLiteralStart($text, $pos, $endOfFilePos) {
        // nonzero-digit
    }

    function isOctalLiteralStart($text, $pos, $endOfFilePos) {
        // 0
        // need to lookahead to resolve ambiguity w/ hexadecimal literal
    }

    function isHexadecimalLiteralStart($text, $pos, $endOfFilePos) {
        // 0x  0X
    }

    function isBinaryLiteralStart($text, $pos, $endOfFilePos) {
        // 0b, 0B
    }

    function isFloatingLiteralStart($text, $pos, $endOfFilePos) {
        // . or digit
        // ambiguity of first char - start of octal literal or decimal?
        // is there some ordering to the grammar that helps resolve this?
    }
}

const KEYWORDS = array(
    "abstract" => TokenKind::AbstractKeyword,
    "and" => TokenKind::AndKeyword,
    "array" => TokenKind::ArrayKeyword,
    "as" => TokenKind::AsKeyword,
    "break" => TokenKind::BreakKeyword,
    "callable" => TokenKind::CallableKeyword,
    "case" => TokenKind::CaseKeyword,
    "catch" => TokenKind::CatchKeyword,
    "class" => TokenKind::ClassKeyword,
    "clone" => TokenKind::CloneKeyword,
    "const" => TokenKind::ConstKeyword,
    "continue" => TokenKind::ContinueKeyword,
    "declare" => TokenKind::DeclareKeyword,
    "default" => TokenKind::DefaultKeyword,
    "die" => TokenKind::DieKeyword,
    "do" => TokenKind::DoKeyword,
    "echo" => TokenKind::EchoKeyword,
    "else" => TokenKind::ElseKeyword,
    "elseif" => TokenKind::ElseIfKeyword,
    "empty" => TokenKind::EmptyKeyword,
    "enddeclare" => TokenKind::EndDeclareKeyword,
    "endfor" => TokenKind::EndForKeyword,
    "endforeach" => TokenKind::EndForEachKeyword,
    "endif" => TokenKind::EndIfKeyword,
    "endswitch" => TokenKind::EndSwitchKeyword,
    "endwhile" => TokenKind::EndWhileKeyword,
    "eval" => TokenKind::EvalKeyword,
    "exit" => TokenKind::ExitKeyword,
    "extends" => TokenKind::ExtendsKeyword,
    "final" => TokenKind::FinalKeyword,
    "finally" => TokenKind::FinallyKeyword,
    "for" => TokenKind::ForKeyword,
    "foreach" => TokenKind::ForeachKeyword,
    "function" => TokenKind::FunctionKeyword,
    "global" => TokenKind::GlobalKeyword,
    "goto" => TokenKind::GotoKeyword,
    "if" => TokenKind::IfKeyword,
    "implements" => TokenKind::ImplementsKeyword,
    "include" => TokenKind::IncludeKeyword,
    "include_once" => TokenKind::IncludeOnceKeyword,
    "instanceof" => TokenKind::InstanceOfKeyword,
    "insteadof" => TokenKind::InsteadOfKeyword,
    "interface" => TokenKind::InterfaceKeyword,
    "isset" => TokenKind::IsSetKeyword,
    "list" => TokenKind::ListKeyword,
    "namespace" => TokenKind::NamespaceKeyword,
    "new" => TokenKind::NewKeyword,
    "or" => TokenKind::OrKeyword,
    "print" => TokenKind::PrintKeyword,
    "private" => TokenKind::PrivateKeyword,
    "protected" => TokenKind::ProtectedKeyword,
    "public" => TokenKind::PublicKeyword,
    "require" => TokenKind::RequireKeyword,
    "require_once" => TokenKind::RequireOnceKeyword,
    "return" => TokenKind::ReturnKeyword,
    "static" => TokenKind::StaticKeyword,
    "switch" => TokenKind::SwitchKeyword,
    "throw" => TokenKind::ThrowKeyword,
    "trait" => TokenKind::TraitKeyword,
    "try" => TokenKind::TryKeyword,
    "unset" => TokenKind::UnsetKeyword,
    "use" => TokenKind::UseKeyword,
    "var" => TokenKind::VarKeyword,
    "while" => TokenKind::WhileKeyword,
    "xor" => TokenKind::XorKeyword,
    "yield" => TokenKind::YieldKeyword,
    "yield from" => TokenKind::YieldFromKeyword
);

const OPERATORS_AND_PUNCTUATORS = array(
    "[" => TokenKind::OpenBracketToken,
    "]" => TokenKind::CloseBracketToken,
    "(" => TokenKind::OpenParenToken,
    ")" => TokenKind::CloseParenToken,
    "{" => TokenKind::OpenBraceToken,
    "}" => TokenKind::CloseBraceToken,
    "." => TokenKind::DotToken,
    "->" => TokenKind::ArrowToken,
    "++" => TokenKind::PlusPlusToken,
    "--" => TokenKind::MinusMinusToken,
    "**" => TokenKind::AsteriskAsteriskToken,
    "*" => TokenKind::AsteriskToken,
    "+" => TokenKind::PlusToken,
    "-" => TokenKind::MinusToken,
    "~" => TokenKind::TildeToken,
    "!" => TokenKind::ExclamationToken,
    "$" => TokenKind::DollarToken,
    "/" => TokenKind::SlashToken,
    "%" => TokenKind::PercentToken,
    "<<" => TokenKind::LessThanLessThanToken,
    ">>" => TokenKind::GreaterThanGreaterThanToken,
    "<" => TokenKind::LessThanToken,
    ">" => TokenKind::GreaterThanToken,
    "<=" => TokenKind::LessThanEqualsToken,
    ">=" => TokenKind::GreaterThanEqualsToken,
    "==" => TokenKind::EqualsEqualsToken,
    "===" => TokenKind::EqualsEqualsEqualsToken,
    "!=" => TokenKind::ExclamationEqualsToken,
    "!==" => TokenKind::ExclamationEqualsEqualsToken,
    "^" => TokenKind::CaretToken,
    "|" => TokenKind::BarToken,
    "&" => TokenKind::AmpersandToken,
    "&&" => TokenKind::ApersandAmpersandToken,
    "||" => TokenKind::BarBarToken,
    "?" => TokenKind::QuestionToken,
    ":" => TokenKind::ColonToken,
    ";" => TokenKind::SemicolonToken,
    "=" => TokenKind::EqualsToken,
    "**=" => TokenKind::AsteriskAsteriskEqualsToken,
    "*=" => TokenKind::AsteriskEqualsToken,
    "/=" => TokenKind::SlashEqualsToken,
    "%=" => TokenKind::PercentEqualsToken,
    "+=" => TokenKind::PlusEqualsToken,
    "-=" => TokenKind::MinusEqualsToken,
    ".=" => TokenKind::DotEqualsToken,
    "<<=" => TokenKind::LessThanLessThanEqualsToken,
    ">>=" => TokenKind::GreaterThanGreaterThanEqualsToken,
    "&=" => TokenKind::AmpersandEqualsToken,
    "^=" => TokenKind::CaretEqualsToken,
    "|=" => TokenKind::BarEqualsToken,
    "," => TokenKind::CommaToken,
    "??" => TokenKind::QuestionQuestionToken,
    "<=>" => TokenKind::LessThanEqualsGreaterThanToken,
    "..." => TokenKind::DotDotDotToken,
    "\\" => TokenKind::BackslashToken
);