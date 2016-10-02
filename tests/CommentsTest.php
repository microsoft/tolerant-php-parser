<?php
use PHPUnit\Framework\TestCase;

// TODO autoload classes
require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../Token.php");

class CommentsTest extends TestCase {
    // TODO test w/ multiple files
    const FILENAME = __dir__ . "/fixtures/commentsFile.php";

    /**
     * TODO not actually a test - just a convenience during initial development
     */
    public function testSanityCheck() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        $expected = array(
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 0, 0, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 1, 1, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 2, 2, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 3, 3, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 4, 4, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 5, 5, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 6, 6, 1),
            new PhpParser\Token(PhpParser\TokenKind::SingleLineComment, 7, 7, 9),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 16, 16, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 17, 17, 1),
            new PhpParser\Token(PhpParser\TokenKind::SingleLineComment, 18, 18, 8),
            new PhpParser\Token(PhpParser\TokenKind::EndOfFileToken, 26, 26, 0)
        );

        foreach ($tokensArray as $index=>$token) {
            $this->assertEquals($expected[$index], $token, "Awesome");
        }
    }
}