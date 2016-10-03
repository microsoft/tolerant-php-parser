<?php
namespace PhpParser;
use DoctrineTest\InstantiatorTestAsset\ExceptionAsset;
use \SplFixedArray;

require_once(__DIR__ . "/Token.php");

function getTokensArray($filename) {
    $fileContents = file_get_contents($filename);
    $end = strlen($fileContents);

    // TODO figure out how to optimize memory
    // $tokensArray = new SplFixedArray($strLen);
    $tokensArray = array();
    $token;
    $pos = 0;
    do {
        $token = scan($fileContents, $pos, $end);
        array_push($tokensArray, $token);
    } while ($token->kind != TokenKind::EndOfFileToken);

    return $tokensArray;
}

function scan($text, & $pos, $end) : Token {
    $startPos = $pos;

    while (true) {
        $tokenPos = $pos;
        if ($pos >= $end) {
            return new Token(TokenKind::EndOfFileToken, $startPos, $tokenPos, $pos-$startPos);
        }

        // TODO skip past <?php
        $char = $text[$pos];
        $pos++;

        switch ($char) {
            case "#":
                scanSingleLineComment($text, $pos, $end);
                continue;

            case " ":
            case "\t":
            case "\r":
            case "\n":
                continue;

            case "/":
                if (isSingleLineComment($text, $pos, $end)) {
                    scanSingleLineComment($text, $pos, $end);
                    continue;
                } else if (isDelimitedComment($text, $pos, $end)) {
                    $pos++;
                    scanDelimitedComment($text, $pos, $end);
                    continue;
                } else if (isCompoundAssignment($text, $pos, $end)) {
                    $pos++;
                    return new Token(TokenKind::CompoundDivideAssignment, $startPos, $tokenPos, $pos-$startPos);
                }
                return new Token(TokenKind::DivideOperator, $startPos, $tokenPos, $pos-$startPos);

            case "$":
                if (isName($text, $pos, $end)) {
                    $pos++;
                    scanName($text, $pos, $end);
                    return new Token(TokenKind::VariableName, $startPos, $tokenPos, $pos-$startPos);
                }
                throw new \Exception("Not implemented");

            default:
                if (isName($text, $pos-1, $end)) {
                    //$pos++;
                    scanName($text, $pos, $end);
                    $token = new Token(TokenKind::Name, $startPos, $tokenPos, $pos-$startPos);
                    if (isKeyword($token->getTextForToken($text))) {
                        $token->kind = TokenKind::Keyword;
                    }
                    return $token;
                }
                return new Token(TokenKind::Unknown, $startPos, $tokenPos, $pos-$startPos);
        }
    }
}

const KEYWORDS = array (
    "abstract", "and", "array", "as",
    "break","callable", "case", "catch", "class", "clone",
    "const", "continue", "declare", "default", "die", "do", "echo",
    "else", "elseif", "empty", "enddeclare", "endfor", "endforeach", "endif",
    "endswitch", "endwhile", "eval", "exit", "extends", "final", "finally",
    "for", "foreach", "function", "global", "goto", "if", "implements",
    "include", "include_once", "instanceof", "insteadof", "interface", "isset",
    "list", "namespace", "new", "or", "print", "private", "protected",
    "public", "require", "require_once", "return", "static", "switch",
    "throw", "trait", "try", "unset", "use", "var", "while", "xor", "yield from", "yield"

);

function isKeyword($text) {
    return in_array(strtolower($text), KEYWORDS);
}

function scanSingleLineComment($text, & $pos, $end) {
    while (true) {
        if ($pos >= $end || isNewLineChar($text[$pos])) {
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
        if (($pos + 1 < $end && $text[$pos] === "*" && $text[$pos+1] === "/")) {
            $pos+=2;
            return;
        }
        $pos++;
    }
    return;
}

function isCompoundAssignment($text, & $pos, $end) {
    if ($pos < $end ) {
        return $text[$pos] === "=";
    }
    return false;
}


function isName($text, $pos, $end) {
    if ($pos < $end) {
        return isNameNonDigit($text[$pos]);
    }
    return false;
}

function scanName($text, & $pos, $end) {
    while ($pos < $end) {
        $char = $text[$pos];
        if (isNameNonDigit($char) || isDigit($char)) {
            $pos++;
            continue;
        }
        return;
    }
}

function isNameNonDigit($char) : bool {
    return isNonDigit($char) || isValidNameUnicodeChar($char);
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