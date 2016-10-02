<?php
use PHPUnit\Framework\TestCase;

// TODO autoload classes
require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../Token.php");


class LexerInvariantsTest extends TestCase {
    // TODO test w/ multiple files
    const FILENAME = __dir__ . "/testfile.php";
    
    public function testInvariants() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);

        foreach ($tokensArray as $token) {
            $this->assertEquals($token->kind, 0);
        }
    }

    public function testTokenLengthSum() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        
        $tokenLengthSum = 0;
        foreach ($tokensArray as $token) {
            $tokenLengthSum += $token->length;
        }

        $this->assertEquals(
            filesize(self::FILENAME), $tokenLengthSum,
            "Invariant: Sum of the lengths of all the tokens should be equivalent to the length of the document.");
    }

    public function testTokenStartGeqFullStart() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        
        foreach ($tokensArray as $token) {
            $this->assertGreaterThanOrEqual(
                $token->fullStart, $token->start,
                "Invariant: A token's Start is always >= FullStart.");
        }
    }

    public function testTokenContentMatchesFileSpan() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        $fileContents = file_get_contents(self::FILENAME);
        foreach ($tokensArray as $token) {
            $this->assertEquals(
                substr($fileContents, $token->fullStart, $token->length),
                $token->getFullTextForToken($fileContents),
                "Invariant: A token's content exactly matches the range of the file its span specifies"
            );
        }
    }

    public function testTokenFullTextMatchesTriviaPlusText() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        $fileContents = file_get_contents(self::FILENAME);
        foreach ($tokensArray as $token) {
            $this->assertEquals(
                $token->getFullTextForToken($fileContents),
                $token->getTriviaForToken($fileContents) . $token->getTextForToken($fileContents),
                "Invariant: FullText of each token matches Trivia plus Text"
            );
        }
    }

    public function testTokenFullTextConcatenationMatchesDocumentText() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        $fileContents = file_get_contents(self::FILENAME);

        $tokenFullTextConcatenation = "";
        foreach ($tokensArray as $token) {
            $tokenFullTextConcatenation .= $token->getFullTextForToken($fileContents);
        }

        $this->assertEquals(
            $fileContents,
            $tokenFullTextConcatenation,
            "Invariant: Concatenating FullText of each token returns the document"
        );
    }

    public function testGetTokenFullTextLengthMatchesLength() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        $fileContents = file_get_contents(self::FILENAME);
        
        foreach ($tokensArray as $token) {
            $this->assertEquals(
                $token->length,
                strlen($token->getFullTextForToken($fileContents)),
                "Invariant: a token's FullText length is equivalent to Length"
            );
        }
    }

    public function testTokenTextLengthMatchesLengthMinusStartPlusFullStart() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        $fileContents = file_get_contents(self::FILENAME);
        
        foreach ($tokensArray as $token) {
            $this->assertEquals(
                $token->length - ($token->start - $token->fullStart),
                strlen($token->getTextForToken($fileContents)),
                "Invariant: a token's FullText length is equivalent to Length - (Start - FullStart)"
            );
        }
    }

    public function testTokenTriviaLengthMatchesStartMinusFullStart() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        $fileContents = file_get_contents(self::FILENAME);
        
        foreach ($tokensArray as $token) {
            $this->assertEquals(
                $token->start - $token->fullStart,
                strlen($token->getTriviaForToken($fileContents)),
                "Invariant: a token's Trivia length is equivalent to (Start - FullStart)"
            );
        }
    }

    public function testWithDifferentEncodings() {
        // TODO test with different encodings
        throw new Exception("Not implemented");
    }

}