<?php
namespace PhpParser;

require_once(__DIR__ . "/Token.php");

class Lexer {

    function getTokensArray($filename) {
        $fileContents = file_get_contents($filename);
        $end = strlen($fileContents);

        // TODO figure out how to optimize memory
        // $tokensArray = new SplFixedArray($strLen);
        $tokensArray = array();

        $pos = 0;
        do {
            $token = $this->scan($fileContents, $pos, $end);
            array_push($tokensArray, $token);
        } while ($token->kind != TokenKind::EndOfFileToken);

        return $tokensArray;
    }

    public function scan($text, & $pos, $end) : Token {
        $startPos = $pos;

        while (true) {
            $tokenPos = $pos;
            if ($pos >= $end) {
                return new Token(TokenKind::EndOfFileToken, $startPos, $tokenPos, $pos - $startPos);
            }

            // TODO skip past <?php
            $char = $text[$pos];
            $pos++;

            switch ($char) {
                case "#":
                    $this->scanSingleLineComment($text, $pos, $end);
                    continue;

                case " ":
                case "\t":
                case "\r":
                case "\n":
                    continue;

                // TODO Potential-compound
                case ".":
                case "*":
                case "+":
                case "-":
                case "!":
                case "%":
                case "<":
                case ">":
                case "^":
                case "|":
                case "&":
                case "?":
                case ":":
                case "=":
                case ",":

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
                    $tokenKind = OPERATORS_AND_PUNCTUATORS[$char];
                    return new Token($tokenKind, $startPos, $tokenPos, $pos - $startPos);

                case "/":
                    if ($this->isSingleLineComment($text, $pos, $end)) {
                        $this->scanSingleLineComment($text, $pos, $end);
                        continue;
                    } else if ($this->isDelimitedComment($text, $pos, $end)) {
                        $pos++;
                        $this->scanDelimitedComment($text, $pos, $end);
                        continue;
                    } else if ($this->isCompoundAssignment($text, $pos, $end)) {
                        $pos++;
                        return new Token(TokenKind::SlashEqualsToken, $startPos, $tokenPos, $pos - $startPos);
                    }
                    return new Token(TokenKind::SlashToken, $startPos, $tokenPos, $pos - $startPos);

                case "$":
                    if ($this->isName($text, $pos, $end)) {
                        $pos++;
                        $this->scanName($text, $pos, $end);
                        return new Token(TokenKind::VariableName, $startPos, $tokenPos, $pos - $startPos);
                    }
                    return new Token(TokenKind::DollarToken, $startPos, $tokenPos, $pos - $startPos);

                default:
                    if ($this->isName($text, $pos - 1, $end)) {
                        //$pos++;
                        $this->scanName($text, $pos, $end);
                        $token = new Token(TokenKind::Name, $startPos, $tokenPos, $pos - $startPos);
                        $tokenText = $token->getTextForToken($text);
                        if ($this->isKeyword($tokenText)) {
                            $token->kind = KEYWORDS[$tokenText];
                        }
                        return $token;
                    }
                    return new Token(TokenKind::Unknown, $startPos, $tokenPos, $pos - $startPos);
            }
        }
    }

    function isKeyword($text) {
        return array_key_exists(strtolower($text), KEYWORDS);
    }

    function isOperatorOrPunctuator($text) {
        return in_array(strtolower($text), OPERATORS_AND_PUNCTUATORS);
    }

    function scanSingleLineComment($text, & $pos, $end) {
        while (true) {
            if ($pos >= $end || $this->isNewLineChar($text[$pos])) {
                return;
            }
            $pos++;
        }
    }

    function isNewLineChar($char) {
        return $char === "\n" || $char === "\r";
    }

    function isSingleLineComment($text, & $pos, $end) {
        if ($pos >= $end) {
            return false;
        }
        if ($text[$pos] === "/") {
            return true;
        }

        return false;
    }

    function isDelimitedComment($text, $pos, $end) {
        if ($pos >= $end) {
            return false;
        }
        if ($text[$pos] === "*") {
            return true;
        }
        return false;
    }

    function scanDelimitedComment($text, & $pos, $end) {
        while ($pos < $end) {
            if (($pos + 1 < $end && $text[$pos] === "*" && $text[$pos + 1] === "/")) {
                $pos += 2;
                return;
            }
            $pos++;
        }
        return;
    }

    function isCompoundAssignment($text, & $pos, $end) {
        if ($pos < $end) {
            return $text[$pos] === "=";
        }
        return false;
    }


    function isName($text, $pos, $end) {
        if ($pos < $end) {
            return $this->isNameNonDigit($text[$pos]);
        }
        return false;
    }

    function scanName($text, & $pos, $end) {
        while ($pos < $end) {
            $char = $text[$pos];
            if ($this->isNameNonDigit($char) || $this->isDigit($char)) {
                $pos++;
                continue;
            }
            return;
        }
    }

    function isNameNonDigit($char) : bool {
        return $this->isNonDigit($char) || $this->isValidNameUnicodeChar($char);
    }

    /**
     * valid chars: U+0080–U+00ff
     * @param $char
     * @return bool
     */
    function isValidNameUnicodeChar($char) {
        return $char >= "\u{0080}" && $char <= "\u{00ff}";
    }

    /**
     * NonDigit is defined as '_' or 'a-z' or 'A-Z'
     * @param $char
     * @return bool
     */
    function isNonDigit($char) : bool {
        $asciiCode = ord($char);
        return ($asciiCode >= 65 && $asciiCode <= 90)
        || ($asciiCode >= 97 && $asciiCode <= 122)
        || ($asciiCode === 95);
    }

    function isDigit($char) : bool {
        $asciiCode = ord($char);
        return ($asciiCode >= 48 && $asciiCode <= 57);
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