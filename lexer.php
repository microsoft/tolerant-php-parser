<?php
namespace PhpParser;
use \SplFixedArray;

require_once("./Token.php");

function getTokensArray($filename) {
    $tokenKind = new TokenKind;
    $fileContents = file_get_contents($filename);
    $strLen = strlen($fileContents);

    // TODO figure out how to optimize memory
    $tokensArray = new SplFixedArray($strLen);

    for ($i = 0; $i < $strLen; $i++) {
        $char = $fileContents[$i];
        switch ($char) {
            default:
                $tokensArray[$i] = new Token($tokenKind::Error, $i, $i, 1);
                break;
        }
    }
    return $tokensArray;
}
