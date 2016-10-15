<?php
namespace PhpParser;

require_once(__DIR__ . "/Token.php");
require_once(__DIR__ . "/characterCodes.php");

class Lexer {

    private $pos;
    private $endOfFilePos;
    private $fileContents;
    private $token;

    public function __construct($filename) {
        $this->fileContents = file_get_contents($filename);
        $this->endOfFilePos = strlen($this->fileContents);
        $this->pos = 0;
    }

    function getTokensArray() {

        // TODO figure out how to optimize memory
        // $tokensArray = new SplFixedArray($strLen);
        $tokensArray = array();

        do {
            $token = $this->scanNextToken();
            array_push($tokensArray, $token);
        } while ($token->kind != TokenKind::EndOfFileToken);

        return $tokensArray;
    }

    public function scanNextToken() : Token {
        $this->token = $this->scan();
        return $this->token;
    }

    private function scan() : Token {
        $pos = & $this->pos;
        $endOfFilePos = & $this->endOfFilePos;
        $text = & $this->fileContents;
        $fullStart = $pos;

        while (true) {
            $start = $pos;
            if ($pos >= $endOfFilePos) {
                return new Token(TokenKind::EndOfFileToken, $fullStart, $start, $pos - $fullStart);
            }

            // TODO skip past <?php
            $char = ord($text[$pos]);

            switch ($char) {
                case CharacterCodes::_hash:
                    $this->scanSingleLineComment($text, $pos, $endOfFilePos);
                    continue;

                case ord(" "):
                case CharacterCodes::_tab:
                case ord("\r"):
                case ord("\n"):
                    $pos++;
                    continue;

                // Potential 3-char compound
                case CharacterCodes::_dot: // ..., .=, . // TODO also applies to floating point literals
                    if (isset($text[$pos+1]) && $this->isDigitChar($text[$pos+1])) {
                        $kind = $this->scanNumericLiteral($text, $pos, $endOfFilePos);
                        return new Token($kind, $fullStart, $start, $pos-$fullStart);
                    }
                    // Otherwise fall through to compounds

                case CharacterCodes::_lessThan: // <=>, <=, <<=, <<, < // TODO heredoc and nowdoc
                case CharacterCodes::_equals: // ===, ==, =
                case CharacterCodes::_greaterThan: // >>=, >>, >=, >
                case CharacterCodes::_asterisk: // **=, **, *=, *
                case CharacterCodes::_exclamation: // !==, !=, !

                // Potential 2-char compound
                case CharacterCodes::_plus: // +=, ++, +
                case CharacterCodes::_minus: // -= , --, ->, -
                case CharacterCodes::_percent: // %=, %
                case CharacterCodes::_caret: // ^=, ^
                case CharacterCodes::_bar: // |=, ||, |
                case CharacterCodes::_ampersand: // &=, &&, &
                case CharacterCodes::_question: // ??, ?

                case CharacterCodes::_colon: // : (TODO should this actually be treated as compound?)
                case CharacterCodes::_comma: // , (TODO should this actually be treated as compound?)

                // Non-compound
                case CharacterCodes::_openBracket:
                case CharacterCodes::_closeBracket:
                case CharacterCodes::_openParen:
                case CharacterCodes::_closeParen:
                case CharacterCodes::_openBrace:
                case CharacterCodes::_closeBrace:
                case CharacterCodes::_semicolon:
                case CharacterCodes::_tilde:
                case CharacterCodes::_backslash:
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

                case CharacterCodes::_slash:
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

                case CharacterCodes::_dollar:
                    $pos++;
                    if ($this->isNameStart($text, $pos, $endOfFilePos)) {
                        $this->scanName($text, $pos, $endOfFilePos);
                        return new Token(TokenKind::VariableName, $fullStart, $start, $pos - $fullStart);
                    }
                    return new Token(TokenKind::DollarToken, $fullStart, $start, $pos - $fullStart);

                case CharacterCodes::_doubleQuote:
                    $doubleQuote = true;
                case CharacterCodes::_singleQuote:
                    $quoteStart = true;
                    // Flow through to b/B
                case CharacterCodes::b:
                case CharacterCodes::B:
                    if ($text[$pos] === "'" || $text[$pos] === "\"" || (isset($text[$pos+1]) && ($text[$pos+1] === "'" || $text[$pos+1] === "\""))) {
                        $pos += isset($quoteStart) ? 0 : 1;
                        if ($text[$pos] === "\"") {
                            $kind = $this->scanTemplateAndSetTokenValue($text, $pos, $endOfFilePos, false);
                            return new Token($kind, $fullStart, $start, $pos - $fullStart);
                        }

                        $pos++;
                        if ($this->scanStringLiteral($text, $pos, $endOfFilePos)) {
                            return new Token(TokenKind::StringLiteralToken, $fullStart, $start, $pos-$fullStart);
                        }
                        return new Token(TokenKind::UnterminatedStringLiteralToken, $fullStart, $start, $pos-$fullStart);
                    }

                    // Flow through to default case

                default:
                    if ($this->isNameStart($text, $pos, $endOfFilePos)) {
                        $this->scanName($text, $pos, $endOfFilePos);
                        $token = new Token(TokenKind::Name, $fullStart, $start, $pos - $fullStart);
                        $tokenText = $token->getTextForToken($text);
                        if ($this->isKeywordStart($tokenText)) {
                            $token = $this->getKeywordTokenFromNameToken($token, $tokenText, $text, $pos, $endOfFilePos);
                        }
                        return $token;
                    } else if ($this->isDigitChar($text[$pos])) {
                        $kind = $this->scanNumericLiteral($text, $pos, $endOfFilePos);
                        return new Token($kind, $fullStart, $start, $pos - $fullStart);
                    }
                    $pos++;
                    return new Token(TokenKind::Unknown, $fullStart, $start, $pos - $fullStart);
            }
        }
    }

    function getKeywordTokenFromNameToken($token, $keywordStart, $text, & $pos, $endOfFilePos) {
        $token->kind = KEYWORDS[strtolower($keywordStart)];
        if ($token->kind === TokenKind::YieldKeyword) {
            $savedPos = $pos;
            $nextToken = $this->scanNextToken($text, $pos, $endOfFilePos);
            if (preg_replace('/\s+/','', strtolower($nextToken->getFullTextForToken($text))) === "from") {
                $token->kind = TokenKind::YieldFromKeyword;
                $token->length = $pos - $token->fullStart;
            } else {
                $pos = $savedPos;
            }
        }
        return $token;
    }

    function isKeywordStart($text) : bool {
        return isset(KEYWORDS[strtolower($text)]);
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
        $charCode = ord($char);
        return
            ($charCode >= CharacterCodes::a && $charCode <= CharacterCodes::z) ||
            ($charCode >= CharacterCodes::A && $charCode <= CharacterCodes::Z) ||
            $charCode === CharacterCodes::_underscore;
    }

    function isDigitChar($char) : bool {
        $charCode = ord($char);
        return
            $charCode >= CharacterCodes::_0 &&
            $charCode <= CharacterCodes::_9;
    }

    function isNonzeroDigitChar($char) : bool {
        $charCode = ord($char);
        return
            $charCode >= CharacterCodes::_1 &&
            $charCode <= CharacterCodes::_9;
    }

    function isOctalDigitChar($char) : bool {
        $charCode = ord($char);
        return
            $charCode >= CharacterCodes::_0 &&
            $charCode <= CharacterCodes::_7;
    }

    function isBinaryDigitChar($char) : bool {
        $charCode = ord($char);
        return
            $charCode === CharacterCodes::_0 ||
            $charCode === CharacterCodes::_1;
    }

    function isHexadecimalDigit($char) {
        // 0  1  2  3  4  5  6  7  8  9
        // a  b  c  d  e  f
        // A  B  C  D  E  F
        $charCode = ord($char);
        return
            $charCode >= CharacterCodes::_0 && $charCode <= CharacterCodes::_9 ||
            $charCode >= CharacterCodes::a && $charCode <= CharacterCodes::f ||
            $charCode >= CharacterCodes::A && $charCode <= CharacterCodes::F;
    }

    function scanNumericLiteral($text, & $pos, $endOfFilePos) : int {
        if ($this->isBinaryLiteralStart($text, $pos, $endOfFilePos)) {
            $pos+=2;
            $prevPos = $pos;
            $isValidBinaryLiteral = $this->scanBinaryLiteral($text, $pos, $endOfFilePos);
            if ($prevPos === $pos || !$isValidBinaryLiteral) {
                // invalid binary literal
                return TokenKind::InvalidBinaryLiteral;

            }
            return TokenKind::BinaryLiteralToken;
        } else if ($this->isHexadecimalLiteralStart($text, $pos, $endOfFilePos)) {
            $pos += 2;
            $prevPos = $pos;
            $isValidHexLiteral = $this->scanHexadecimalLiteral($text, $pos, $endOfFilePos);
            if ($prevPos === $pos || !$isValidHexLiteral) {
                return TokenKind::InvalidHexadecimalLiteral;
                // invalid hexadecimal literal
            }
            return TokenKind::HexadecimalLiteralToken;
        } else if ($this->isDigitChar($text[$pos]) || $text[$pos] === ".") {
            // TODO throw error if there is no number past the dot.
            $prevPos = $pos;
            $isValidFloatingLiteral = $this->scanFloatingPointLiteral($text, $pos, $endOfFilePos);

            if ($isValidFloatingLiteral) {
                return TokenKind::FloatingLiteralToken;
            }

            // Reset, try scanning octal literal
            $pos = $prevPos;

            if ($text[$pos] === "0") {
                $isValidOctalLiteral = $this->scanOctalLiteral($text, $pos, $endOfFilePos);

                // Check that it's not a 0 decimal literal
                if ($pos === $prevPos+1) {
                    return TokenKind::DecimalLiteralToken;
                }

                if (!$isValidOctalLiteral) {
                    return TokenKind::InvalidOctalLiteralToken;
                }

                return TokenKind::OctalLiteralToken;
            }

            $this->scanDecimalLiteral($text, $pos, $endOfFilePos);
            return TokenKind::DecimalLiteralToken;
        }
        // TODO throw error
        return TokenKind::Unknown;
    }

    function isDecimalLiteralStart($text, $pos, $endOfFilePos) {
        // nonzero-digit
        return $this->isNonzeroDigitChar($text[$pos]);
    }

    function isOctalLiteralStart($text, $pos, $endOfFilePos) {
        // 0
        // need to lookahead to resolve ambiguity w/ hexadecimal literal
        return
            $text[$pos] === "0";
    }

    function scanBinaryLiteral($text, & $pos, $endOfFilePos) {
        $isValid = true;
        while ($pos < $endOfFilePos) {
            $char = $text[$pos];
            if ($this->isBinaryDigitChar($char)) {
                $pos++;
                continue;
            } else if ($this->isDigitChar($char)) {
                $pos++;
                // REPORT ERROR;
                $isValid = false;
                continue;
            }
            break;
        }
        return $isValid;
    }

    function scanHexadecimalLiteral($text, & $pos, $endOfFilePos) {
        $isValid = true;
        while ($pos < $endOfFilePos) {
            $char = $text[$pos];
            if ($this->isHexadecimalDigit($char)) {
                $pos++;
                continue;
            } else if ($this->isDigitChar($char) || $this->isNameNonDigitChar($char)) {
                $pos++;
                // REPORT ERROR;
                $isValid = false;
                continue;
            }
            break;
        }
        return $isValid;
    }

    function isHexadecimalLiteralStart($text, $pos, $endOfFilePos) {
        // 0x  0X
        return
            isset($text[$pos+1]) &&
            $text[$pos] === "0" &&
            strtolower($text[$pos+1]) == "x";
    }

    function isBinaryLiteralStart($text, $pos, $endOfFilePos) {
        // 0b, 0B
        return
            isset($text[$pos+1]) &&
            $text[$pos] === "0" &&
            strtolower($text[$pos+1]) == "b";
    }

    function scanDecimalLiteral($text, & $pos, $endOfFilePos) {
        while ($pos < $endOfFilePos) {
            $char = $text[$pos];
            if ($this->isDigitChar($char)) {
                $pos++;
                continue;
            }
            return;
        }
    }

    private function scanOctalLiteral($text, & $pos, $endOfFilePos) {
        $isValid = true;
        while ($pos < $endOfFilePos) {
            $char = $text[$pos];

            if ($this->isOctalDigitChar($char)) {
                $pos++;
                continue;
            } else if ($this->isDigitChar($char)) {
                $pos++;
                $isValid = false;
                continue;
            }
            break;
        }
        return $isValid;
    }

     function scanFloatingPointLiteral($text, & $pos, $endOfFilePos) {
         $hasDot = false;
         $expStart = null;
         $hasSign = false;
         while ($pos < $endOfFilePos) {
             $char = $text[$pos];

             if ($this->isDigitChar($char)) {
                 $pos++;
                 continue;
             } else if ($char === ".") {
                 if ($hasDot || $expStart !== null) {
                     // Dot not valid, done scanning
                     break;
                 }
                 $hasDot = true;
                 $pos++;
                 continue;
             } else if ($char === "e" || $char === "E") {
                 if ($expStart !== null) {
                     // exponential not valid here, done scanning
                     break;
                 }
                 $expStart = $pos;
                 $pos++;
                 continue;
             } else if ($char === "+" || $char === "-") {
                 if ($expStart !== null && $expStart === $pos-1) {
                     $hasSign = true;
                     $pos++;
                     continue;
                 }
                 // sign not valid here, done scanning
                 break;
             }
             // unexpected character, done scanning
             break;
         }

         if ($expStart !== null) {
             $expectedMinPos = $hasSign ? $expStart + 3 : $expStart + 2;
             if ($pos >= $expectedMinPos) {
                 return true;
             }
             // exponential is invalid, reset position
             $pos = $expStart;
         }

         return $hasDot;
    }

    function scanStringLiteral($text, & $pos, $endOfFilePos) {
        // TODO validate with multiple character sets

        $isTerminated = false;
        while ($pos < $endOfFilePos) {
            $char = $text[$pos];
            if ($this->isNewLineChar($char)) {
                // unterminated string
                // TODO REPORT ERROR
                break;
            } else if ($this->isSingleQuoteEscapeSequence($text, $pos)) {
                $pos+=2;
                continue;
            } else if ($text[$pos] === "'") {
                $pos++;
                $isTerminated = true;
                break;
            } else {
                $pos++;
                continue;
            }
        }

        return $isTerminated;
    }

    function isSingleQuoteEscapeSequence($text, $pos) {
        return
            isset($text[$pos+1]) &&
            $text[$pos] === "\\" &&
            in_array($text[$pos+1], self::SQ_ESCAPE_SEQ_CHARS);

    }

    const SQ_ESCAPE_SEQ_CHARS = array(
        "'", "\\"
    );

    const DQ_ESCAPE_SEQ_CHARS = array(
        "\"", "\\", "$", 'e', "f", "n", "r", "t", "v"
    );

    function isDoubleQuoteEscapeSequence($text, $pos) {
        return
            isset($text[$pos+1]) &&
            $text[$pos] === "\\" &&
            in_array($text[$pos+1], self::DQ_ESCAPE_SEQ_CHARS);
    }

    function reScanTemplateToken($token): Token {
        $this->pos = $token->fullStart + $token->length;
        $start = $this->pos;
        $kind = $this->scanTemplateAndSetTokenValue($this->fileContents, $this->pos, $this->endOfFilePos, true);
        return new Token($kind, $start, $start, $this->pos-$start);
    }

    function scanTemplateAndSetTokenValue($text, & $pos, $endOfFilePos, $isRescan): int {
        $startedWithDoubleQuote = ord($text[$pos]) === CharacterCodes::_doubleQuote && !$isRescan;
        $isTerminated = false;

        if ($startedWithDoubleQuote) {
            $pos++;
        }

        while (true) {
            if ($pos >= $endOfFilePos) {
                // UNTERMINATED, report error
                return $startedWithDoubleQuote ? TokenKind::UnterminatedNoSubstitutionTemplateLiteral : TokenKind::UnterminatedTemplateStringEnd;
            }


            $char = ord($text[$pos]);

            // '"'
            if ($char === CharacterCodes::_doubleQuote) {
                $pos++;
                $isTerminated = true;
                return $startedWithDoubleQuote ? TokenKind::NoSubstitutionTemplateLiteral : TokenKind::TemplateStringEnd;
            }

            // '$' -> start of a variable
            if ($char === CharacterCodes::_dollar) {
                return $startedWithDoubleQuote ? TokenKind::TemplateStringStart : TokenKind::TemplateStringMiddle;
            }

            // Escape character
            if ($char === CharacterCodes::_backslash) {
                // TODO scan escape sequence
                $pos++;
                $this->scanDqEscapeSequence($text, $pos, $endOfFilePos);
                continue;
            }

            if ($char === ord("\n") || $char === ord("\r")) {
                // UNTERMINATED, report error
                return $startedWithDoubleQuote ? TokenKind::UnterminatedNoSubstitutionTemplateLiteral : TokenKind::UnterminatedTemplateStringEnd;
            }

            $pos++;
        }

        // TODO throw error
        return TokenKind::Unknown;
    }

    private function scanDqEscapeSequence($text, & $pos, $endOfFilePos) {
        if ($pos >= $endOfFilePos) {
            // ERROR
            return;
        }
        $char = ord($text[$pos]);
        switch ($char) {
            // dq-simple-escape-sequence
            case CharacterCodes::_doubleQuote:
            case CharacterCodes::_backslash:
            case CharacterCodes::_dollar:
            case CharacterCodes::e:
            case CharacterCodes::f:
            case CharacterCodes::r:
            case CharacterCodes::t:
            case CharacterCodes::v:
                $pos++;
                return;

            // dq-hexadecimal-escape-sequence
            case CharacterCodes::x:
            case CharacterCodes::X:
                $pos++;
                for ($i = 0; $i<2; $i++) {
                    if (isset($text[$pos]) && $this->isHexadecimalDigit($text[$pos])) {
                        $pos++;
                    }
                }
                return;

            // dq-unicode-escape-sequence
            case CharacterCodes::u:
                $pos++;
                if (isset($text[$pos]) && ord($text[$pos]) === CharacterCodes::_openBrace) {
                    $this->scanHexadecimalLiteral($text, $pos, $endOfFilePos);
                    if (isset($text[$pos]) && ord($text[$pos]) === CharacterCodes::_closeBrace) {
                        $pos++;
                        return;
                    }
                    // OTHERWISE ERROR

                }
                return;
            default:
                // dq-octal-digit-escape-sequence
                if ($this->isOctalDigitChar($text[$pos])) {
                    for ($i = $pos; $i < $pos + 3; $i++) {
                        if (!(isset($text[$i]) || $this->isOctalDigitChar($text[$i]))) {
                            return;
                        }
                        $pos++;
                        return;
                    }
                }

                $pos++;
                return;
        }
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
    "&&" => TokenKind::AmpersandAmpersandToken,
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