<?php
namespace PhpParser;
use \SplFixedArray;

require_once("./Token.php");

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

        switch ($text[$pos++]) {
            case "#":
                scanSingleLineComment($text, $pos, $end);
                return new Token(TokenKind::SingleLineComment, $startPos, $tokenPos, $pos-$startPos);
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