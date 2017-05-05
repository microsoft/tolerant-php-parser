<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser;

class Lexer implements TokenStreamProviderInterface {
    private $pos;
    private $endOfFilePos;
    private $fileContents;
    private $token;

    private $inScriptSection = false;
    private $keywordOrReservedWordTokens;

    public function __construct($content) {
        $this->fileContents = $content;
        $this->endOfFilePos = strlen($this->fileContents);
        $this->pos = 0;
        $this->keywordOrReservedWordTokens = array_merge(TokenStringMaps::KEYWORDS, TokenStringMaps::RESERVED_WORDS);
    }

    public function getTokensArray() : array {

        // TODO figure out how to optimize memory
        // $tokensArray = new SplFixedArray($strLen);
        $tokensArray = array();

        do {
            $token = $this->scanNextToken();
            $tokensArray[] = $token;
        } while ($token->kind != TokenKind::EndOfFileToken);

        return $tokensArray;
    }

    public function scanNextToken() : Token {
        $this->token = $this->scan();
        return $this->token;
    }

    public function getCurrentPosition() : int {
        return $this->pos;
    }

    public function setCurrentPosition(int $pos) {
        $this->pos = $pos;
    }

    public function getEndOfFilePosition() : int {
        return $this->endOfFilePos;
    }

    private function scan() : Token {
        $pos = & $this->pos;
        $endOfFilePos = & $this->endOfFilePos;
        $text = & $this->fileContents;
        $fullStart = $pos;

        while (true) {
            $start = $pos;
            if ($pos >= $endOfFilePos) {
                // TODO manage lookaheads w/ script section state
                $token = $this->inScriptSection
                    ? new Token(TokenKind::EndOfFileToken, $fullStart, $start, $pos-$fullStart)
                    : new Token(TokenKind::InlineHtml, $fullStart, $fullStart, $pos-$fullStart);
                $this->inScriptSection = true;
                // TODO WAT
                if ($token->kind === TokenKind::InlineHtml && $pos-$fullStart === 0) {
                    continue;
                }
                return $token;
            }

            if (!$this->inScriptSection) {
                // Keep scanning until we hit a script section start tag
                if (!$this->isScriptStartTag($text, $pos, $endOfFilePos)) {
                    $pos++;
                    continue;
                }
                
                // Mark that a script section has begun, and return the scanned text as InlineHtml
                $this->inScriptSection = true;
                if ($pos-$fullStart === 0) {
                    continue;
                }
                
                return new Token(TokenKind::InlineHtml, $fullStart, $fullStart, $pos-$fullStart);
            }
            
            $charCode = ord($text[$pos]);

            switch ($charCode) {
                case CharacterCodes::_hash:
                    // Trivia (like comments) prepends a scanned Token
                    $this->scanSingleLineComment($text, $pos, $endOfFilePos);
                    continue;

                case CharacterCodes::_space:
                case CharacterCodes::_tab:
                case CharacterCodes::_return:
                case CharacterCodes::_newline:
                    $pos++;
                    continue;

                // Potential 3-char compound
                case CharacterCodes::_dot: // ..., .=, . // TODO also applies to floating point literals
                    if (isset($text[$pos+1]) && $this->isDigitChar(ord($text[$pos+1]))) {
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
                case CharacterCodes::_question: // ??, ?, end-tag

                case CharacterCodes::_colon: // : (TODO should this actually be treated as compound?)
                case CharacterCodes::_comma: // , (TODO should this actually be treated as compound?)

                // Non-compound
                case CharacterCodes::_at: // @
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
                    // TODO
                    for ($tokenEnd = 6; $tokenEnd >= 0; $tokenEnd--) {
                        if ($pos + $tokenEnd >= $endOfFilePos) {
                            continue;
                        }

                        // TODO get rid of strtolower for perf reasons
                        $textSubstring = strtolower(substr($text, $pos, $tokenEnd + 1));
                        if ($this->isOperatorOrPunctuator($textSubstring)) {
                            $tokenKind = TokenStringMaps::OPERATORS_AND_PUNCTUATORS[$textSubstring];
                            $pos += $tokenEnd + 1;

                            if ($tokenKind === TokenKind::ScriptSectionEndTag) {
                                $this->inScriptSection = false;
                            }

                            return new Token($tokenKind, $fullStart, $start, $pos - $fullStart);
                        }
                    }

                    throw new \Exception("Unknown token kind");

                case CharacterCodes::_slash:
                    if ($this->isSingleLineCommentStart($text, $pos, $endOfFilePos)) {
                        $this->scanSingleLineComment($text, $pos, $endOfFilePos);
                        continue;
                    } elseif ($this->isDelimitedCommentStart($text, $pos, $endOfFilePos)) {
                        $this->scanDelimitedComment($text, $pos, $endOfFilePos);
                        continue;
                    } elseif (isset($text[$pos+1]) && $text[$pos+1] === "=") {
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
                        $tokenText = $token->getText($text);
                        $lowerText = strtolower($tokenText);
                        if ($this->isKeywordOrReservedWordStart($lowerText)) {
                            $token = $this->getKeywordOrReservedWordTokenFromNameToken($token, $lowerText, $text, $pos, $endOfFilePos);
                        }
                        return $token;
                    } elseif ($this->isDigitChar(ord($text[$pos]))) {
                        $kind = $this->scanNumericLiteral($text, $pos, $endOfFilePos);
                        return new Token($kind, $fullStart, $start, $pos - $fullStart);
                    }
                    $pos++;
                    return new Token(TokenKind::Unknown, $fullStart, $start, $pos - $fullStart);
            }
        }
    }

    private function getKeywordOrReservedWordTokenFromNameToken($token, $lowerKeywordStart, $text, & $pos, $endOfFilePos) {
        $token->kind = $this->keywordOrReservedWordTokens[$lowerKeywordStart];
        if ($token->kind === TokenKind::YieldKeyword) {
            $savedPos = $pos;
            $nextToken = $this->scanNextToken();
            if (preg_replace('/\s+/', '', strtolower($nextToken->getFullText($text))) === "from") {
                $token->kind = TokenKind::YieldFromKeyword;
                $token->length = $pos - $token->fullStart;
            } else {
                $pos = $savedPos;
            }
        }
        return $token;
    }

    private function isKeywordOrReservedWordStart($lowerText) : bool {
        return isset($this->keywordOrReservedWordTokens[$lowerText]);
    }

    private function isOperatorOrPunctuator($text): bool {
        return isset(TokenStringMaps::OPERATORS_AND_PUNCTUATORS[$text]);
    }

    private function isSingleLineCommentStart($text, $pos, $endOfFilePos) : bool {
        return
            $pos+1 < $endOfFilePos &&
            $text[$pos] === "/" &&
            $text[$pos+1] === "/";
    }

    private function scanSingleLineComment($text, & $pos, $endOfFilePos) {
        while ($pos < $endOfFilePos) {
            if ($this->isNewLineChar(ord($text[$pos])) || $this->isScriptEndTag($text, $pos, $endOfFilePos)) {
                return;
            }
            $pos++;
        }
    }

    private function isDelimitedCommentStart($text, $pos, $endOfFilePos) : bool {
        return
            $pos + 1 < $endOfFilePos &&
            $text[$pos] === "/" &&
            $text[$pos+1] === "*";
    }

    private function scanDelimitedComment($text, & $pos, $endOfFilePos) {
        while ($pos < $endOfFilePos) {
            if (($pos + 1 < $endOfFilePos && $text[$pos] === "*" && $text[$pos + 1] === "/")) {
                $pos += 2;
                return;
            }
            $pos++;
        }
    }

    private function isNameStart($text, $pos, $endOfFilePos) : bool {
        return
            $pos < $endOfFilePos &&
            $this->isNameNonDigitChar(ord($text[$pos]));
    }

    private function scanName($text, & $pos, $endOfFilePos) {
        while ($pos < $endOfFilePos) {
            $charCode = ord($text[$pos]);
            if ($this->isNameNonDigitChar($charCode) || $this->isDigitChar($charCode)) {
                $pos++;
                continue;
            }
            return;
        }
    }

    private function isNewLineChar($charCode) : bool {
        return
            $charCode === CharacterCodes::_newline ||
            $charCode === CharacterCodes::_return;
    }

    private function isNameNonDigitChar($charCode) : bool {
        return
            $this->isNonDigitChar($charCode) ||
            $this->isValidNameUnicodeChar($charCode);
    }

    /**
     * valid chars: U+0080–U+00ff
     * @param $char
     * @return bool
     */
    private function isValidNameUnicodeChar($char) : bool {
        // TODO implement
        return false;
//        return
//            $char >= "\u{0080}" &&
//            $char <= "\u{00ff}";
    }

    /**
     * NonDigit is defined as '_' or 'a-z' or 'A-Z'
     * @param $char
     * @return bool
     */
    private function isNonDigitChar($charCode) : bool {
        return
            ($charCode >= CharacterCodes::a && $charCode <= CharacterCodes::z) ||
            ($charCode >= CharacterCodes::A && $charCode <= CharacterCodes::Z) ||
            $charCode === CharacterCodes::_underscore;
    }

    private function isDigitChar($charCode) : bool {
        //        $charCode = ord($char);
        return
            $charCode >= CharacterCodes::_0 &&
            $charCode <= CharacterCodes::_9;
    }

    private function isNonzeroDigitChar($charCode) : bool {
        return
            $charCode >= CharacterCodes::_1 &&
            $charCode <= CharacterCodes::_9;
    }

    private function isOctalDigitChar($charCode) : bool {
        return
            $charCode >= CharacterCodes::_0 &&
            $charCode <= CharacterCodes::_7;
    }

    private function isBinaryDigitChar($charCode) : bool {
        return
            $charCode === CharacterCodes::_0 ||
            $charCode === CharacterCodes::_1;
    }

    private function isHexadecimalDigit($charCode) {
        // 0  1  2  3  4  5  6  7  8  9
        // a  b  c  d  e  f
        // A  B  C  D  E  F
        return
            $charCode >= CharacterCodes::_0 && $charCode <= CharacterCodes::_9 ||
            $charCode >= CharacterCodes::a && $charCode <= CharacterCodes::f ||
            $charCode >= CharacterCodes::A && $charCode <= CharacterCodes::F;
    }

    private function scanNumericLiteral($text, & $pos, $endOfFilePos) : int {
        if ($this->isBinaryLiteralStart($text, $pos, $endOfFilePos)) {
            $pos+=2;
            $prevPos = $pos;
            $isValidBinaryLiteral = $this->scanBinaryLiteral($text, $pos, $endOfFilePos);
            if ($prevPos === $pos || !$isValidBinaryLiteral) {
                // invalid binary literal
                return TokenKind::InvalidBinaryLiteral;
            }
            return TokenKind::BinaryLiteralToken;
        } elseif ($this->isHexadecimalLiteralStart($text, $pos, $endOfFilePos)) {
            $pos += 2;
            $prevPos = $pos;
            $isValidHexLiteral = $this->scanHexadecimalLiteral($text, $pos, $endOfFilePos);
            if ($prevPos === $pos || !$isValidHexLiteral) {
                return TokenKind::InvalidHexadecimalLiteral;
                // invalid hexadecimal literal
            }
            return TokenKind::HexadecimalLiteralToken;
        } elseif ($this->isDigitChar(ord($text[$pos])) || $text[$pos] === ".") {
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

    private function isDecimalLiteralStart($text, $pos, $endOfFilePos) {
        // nonzero-digit
        return $this->isNonzeroDigitChar(ord($text[$pos]));
    }

    private function isOctalLiteralStart($text, $pos, $endOfFilePos) {
        // 0
        // need to lookahead to resolve ambiguity w/ hexadecimal literal
        return
            $text[$pos] === "0";
    }

    private function scanBinaryLiteral($text, & $pos, $endOfFilePos) {
        $isValid = true;
        while ($pos < $endOfFilePos) {
            $charCode = ord($text[$pos]);
            if ($this->isBinaryDigitChar($charCode)) {
                $pos++;
                continue;
            } elseif ($this->isDigitChar($charCode)) {
                $pos++;
                // REPORT ERROR;
                $isValid = false;
                continue;
            }
            break;
        }
        return $isValid;
    }

    private function scanHexadecimalLiteral($text, & $pos, $endOfFilePos) {
        $isValid = true;
        while ($pos < $endOfFilePos) {
            $charCode = ord($text[$pos]);
            if ($this->isHexadecimalDigit($charCode)) {
                $pos++;
                continue;
            } elseif ($this->isDigitChar($charCode) || $this->isNameNonDigitChar($charCode)) {
                $pos++;
                // REPORT ERROR;
                $isValid = false;
                continue;
            }
            break;
        }
        return $isValid;
    }

    private function isHexadecimalLiteralStart($text, $pos, $endOfFilePos) {
        // 0x  0X
        return
            isset($text[$pos+1]) &&
            $text[$pos] === "0" &&
            strtolower($text[$pos+1]) == "x";
    }

    private function isBinaryLiteralStart($text, $pos, $endOfFilePos) {
        // 0b, 0B
        return
            isset($text[$pos+1]) &&
            $text[$pos] === "0" &&
            strtolower($text[$pos+1]) == "b";
    }

    private function scanDecimalLiteral($text, & $pos, $endOfFilePos) {
        while ($pos < $endOfFilePos) {
            $charCode = ord($text[$pos]);
            if ($this->isDigitChar($charCode)) {
                $pos++;
                continue;
            }
            return;
        }
    }

    private function scanOctalLiteral($text, & $pos, $endOfFilePos) {
        $isValid = true;
        while ($pos < $endOfFilePos) {
            $charCode = ord($text[$pos]);

            if ($this->isOctalDigitChar($charCode)) {
                $pos++;
                continue;
            } elseif ($this->isDigitChar($charCode)) {
                $pos++;
                $isValid = false;
                continue;
            }
            break;
        }
        return $isValid;
    }

    private function scanFloatingPointLiteral($text, & $pos, $endOfFilePos) {
        $hasDot = false;
        $expStart = null;
        $hasSign = false;
        while ($pos < $endOfFilePos) {
            $char = $text[$pos];

            if ($this->isDigitChar(ord($char))) {
                $pos++;
                continue;
            } elseif ($char === ".") {
                if ($hasDot || $expStart !== null) {
                    // Dot not valid, done scanning
                     break;
                }
                $hasDot = true;
                $pos++;
                continue;
            } elseif ($char === "e" || $char === "E") {
                if ($expStart !== null) {
                    // exponential not valid here, done scanning
                     break;
                }
                $expStart = $pos;
                $pos++;
                continue;
            } elseif ($char === "+" || $char === "-") {
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

    private function scanStringLiteral($text, & $pos, $endOfFilePos) {
        // TODO validate with multiple character sets

        $isTerminated = false;
        while ($pos < $endOfFilePos) {
            $char = $text[$pos];
            if ($this->isSingleQuoteEscapeSequence($text, $pos)) {
                $pos+=2;
                continue;
            } elseif ($text[$pos] === "'") {
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

    private function isSingleQuoteEscapeSequence($text, $pos) {
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

    private function isDoubleQuoteEscapeSequence($text, $pos) {
        return
            isset($text[$pos+1]) &&
            $text[$pos] === "\\" &&
            in_array($text[$pos+1], self::DQ_ESCAPE_SEQ_CHARS);
    }

    private function reScanTemplateToken($token): Token {
        $this->pos = $token->fullStart + $token->length;
        $start = $this->pos;
        $kind = $this->scanTemplateAndSetTokenValue($this->fileContents, $this->pos, $this->endOfFilePos, true);
        return new Token($kind, $start, $start, $this->pos-$start);
    }

    private function scanTemplateAndSetTokenValue($text, & $pos, $endOfFilePos, $isRescan): int {
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
                return $startedWithDoubleQuote ? TokenKind::NoSubstitutionTemplateLiteral : TokenKind::TemplateStringEnd;
            }

            // TODO temporarily disabled template string matching - will re-enable when it's implemented properly
            // '$' -> start of a variable
//            if ($char === CharacterCodes::_dollar) {
//                return $startedWithDoubleQuote ? TokenKind::TemplateStringStart : TokenKind::TemplateStringMiddle;
//            }

            // Escape character
            if ($char === CharacterCodes::_backslash) {
                // TODO scan escape sequence
                $pos++;
                $this->scanDqEscapeSequence($text, $pos, $endOfFilePos);
                continue;
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
                    if (isset($text[$pos]) && $this->isHexadecimalDigit(ord($text[$pos]))) {
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
                if ($this->isOctalDigitChar(ord($text[$pos]))) {
                    for ($i = $pos; $i < $pos + 3; $i++) {
                        if (!(isset($text[$i]) || $this->isOctalDigitChar(ord($text[$i])))) {
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

    private function isScriptStartTag($text, $pos, $endOfFilePos) {
        if (ord($text[$pos]) === CharacterCodes::_lessThan && // TODO use regex to detect newline or whitespace char
            (isset($text[$pos+5]) && strtolower(substr($text, $pos, 5)) === "<?php" &&  in_array($text[$pos+5], ["\n", "\r", " ", "\t"])) ||
            (isset($text[$pos+2]) && substr($text, $pos, 3) === "<?=")) {
            return true;
        }
        return false;
    }

    private function isScriptEndTag($text, $pos, $endOfFilePos) {
        if ($this->inScriptSection &&
            ord($text[$pos]) === CharacterCodes::_question &&
            isset($text[$pos+1]) && ord($text[$pos+1]) === CharacterCodes::_greaterThan) {
            return true;
        }
        return false;
    }
}
