<?php
namespace PhpParser;
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

        switch ($text[$pos++]) {
            case "#":
                scanSingleLineComment($text, $pos, $end);
                continue;

            case " ":
            case "\t":
            case "\r":
            case "\n":
                continue;

            case "/":
                // TODO trivia should prepend tokens
                if (isSingleLineComment($text, $pos, $end)) {
                    scanSingleLineComment($text, $pos, $end);
                    continue;
//                    return new Token(TokenKind::SingleLineComment, $startPos, $tokenPos, $pos-$startPos);
                } else if (isDelimitedComment($text, $pos, $end)) {
                    $pos++;
                    scanDelimitedComment($text, $pos, $end);
                    continue;
//                    return new Token(TokenKind::DelimitedComment, $startPos, $tokenPos, $pos-$startPos);
                } else if (isCompoundAssignment($text, $pos, $end)) {
                    $pos++;
                    return new Token(TokenKind::CompoundDivideAssignment, $startPos, $tokenPos, $pos-$startPos);
                }
                return new Token(TokenKind::DivideOperator, $startPos, $tokenPos, $pos-$startPos);

            default:
                return new Token(TokenKind::Unknown, $startPos, $tokenPos, $pos-$startPos);
        }
    }
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

