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
    public function testCommentsFile() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        $expected = array(
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 0, 0, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 1, 1, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 2, 2, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 3, 3, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 4, 4, 1),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 5, 5, 1),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 6, 6, 1),

            new PhpParser\Token(PhpParser\TokenKind::SingleLineComment, 7, 7, 9),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 16, 16, 1),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 17, 17, 1),

            new PhpParser\Token(PhpParser\TokenKind::SingleLineComment, 18, 18, 8),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 26, 26, 1),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 27, 27, 1),

            new PhpParser\Token(PhpParser\TokenKind::SingleLineComment, 28, 28, 10),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 38, 38, 1),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 39, 39, 1),

            new PhpParser\Token(PhpParser\TokenKind::DelimitedComment, 40, 40, 14),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 54, 54, 1),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 55, 55, 1),

            new PhpParser\Token(PhpParser\TokenKind::Unknown, 56, 56, 1),
            new PhpParser\Token(PhpParser\TokenKind::Whitespace, 57, 57, 1),
            new PhpParser\Token(PhpParser\TokenKind::DivideOperator, 58, 58, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 59, 59, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 60, 60, 1),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 61, 61, 1),
            new PhpParser\Token(PhpParser\TokenKind::Newline, 62, 62, 1),

            new PhpParser\Token(PhpParser\TokenKind::Unknown, 63, 63, 1),
            new PhpParser\Token(PhpParser\TokenKind::CompoundDivideAssignment, 64, 64, 2),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 66, 66, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 67, 67, 1),
            new PhpParser\Token(PhpParser\TokenKind::EndOfFileToken, 68, 68, 0)
        );

        foreach ($tokensArray as $index=>$token) {
            $this->assertEquals($expected[$index], $token, "Awesome");
        }
    }
}