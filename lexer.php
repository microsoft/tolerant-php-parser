<?php
namespace PhpParser;
use \SplFixedArray;

require_once("./Token.php");

function getTokensArray($filename) {
    $tokenKind = new TokenKind;
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
    } while ($token->kind != $tokenKind::EndOfFileToken);

    return $tokensArray;
}

function scan($text, & $pos, $end) : Token {
    $startPos = $pos;
    $tokenKind = new TokenKind;
    while (true) {
        $tokenPos = $pos;
        if ($pos >= $end) {
            return new Token($tokenKind::EndOfFileToken, $startPos, $tokenPos, $pos-$startPos);
        }

        switch ($text[$pos++]) {
            default:
                return new Token($tokenKind::Unknown, $startPos, $tokenPos, $pos-$startPos);
        }
    }
}
