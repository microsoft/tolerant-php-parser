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
            new PhpParser\Token(PhpParser\TokenKind::Name, 2, 2, 3),
//            new PhpParser\Token(PhpParser\TokenKind::Unknown, 3, 3, 1),
//            new PhpParser\Token(PhpParser\TokenKind::Unknown, 4, 4, 1),
            new PhpParser\Token(PhpParser\TokenKind::Name, 5, 56, 52),
            new PhpParser\Token(PhpParser\TokenKind::DivideOperator, 57, 58, 2),
            new PhpParser\Token(PhpParser\TokenKind::Name, 59, 59, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 60, 60, 1),
            new PhpParser\Token(PhpParser\TokenKind::Name, 61, 63, 3),
            new PhpParser\Token(PhpParser\TokenKind::CompoundDivideAssignment, 64, 64, 2),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 66, 66, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 67, 67, 1),
            new PhpParser\Token(PhpParser\TokenKind::VariableName, 68, 70, 6),
            new PhpParser\Token(PhpParser\TokenKind::Keyword, 74, 76, 5),
            new PhpParser\Token(PhpParser\TokenKind::EndOfFileToken, 79, 89, 10)
        );

        foreach ($tokensArray as $index=>$token) {
            $this->assertEquals($expected[$index], $token, "Awesome");
        }
    }
}