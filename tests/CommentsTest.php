<?php
use PHPUnit\Framework\TestCase;

// TODO autoload classes
require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../Token.php");

class CommentsTest extends TestCase {
    // TODO test w/ multiple files
    const FILENAME = __dir__ . "/fixtures/commentsFile.php";
    const PARSER_POC_FILE = __dir__ . "/fixtures/parserPocFile.php";

    /**
     * TODO not actually a test - just a convenience during initial development
     */
    public function testCommentsFile() {
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);
        $expected = array(
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 0, 0, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 1, 1, 1),
            new PhpParser\Token(PhpParser\TokenKind::Name, 2, 2, 3),
            new PhpParser\Token(PhpParser\TokenKind::Name, 5, 56, 52),
            new PhpParser\Token(PhpParser\TokenKind::SlashToken, 57, 58, 2),
            new PhpParser\Token(PhpParser\TokenKind::Name, 59, 59, 1),
            new PhpParser\Token(PhpParser\TokenKind::SemicolonToken, 60, 60, 1),
            new PhpParser\Token(PhpParser\TokenKind::Name, 61, 63, 3),
            new PhpParser\Token(PhpParser\TokenKind::SlashEqualsToken, 64, 64, 2),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 66, 66, 1),
            new PhpParser\Token(PhpParser\TokenKind::SemicolonToken, 67, 67, 1),
            new PhpParser\Token(PhpParser\TokenKind::VariableName, 68, 70, 6),
            new PhpParser\Token(PhpParser\TokenKind::AndKeyword, 74, 76, 5),
            new PhpParser\Token(PhpParser\TokenKind::EndOfFileToken, 79, 89, 10)
        );

        foreach ($tokensArray as $index=>$token) {
            $this->assertEquals($expected[$index], $token, "Awesome");
        }
    }

    public function testParserPocFile() {
        $tokensArray = PhpParser\getTokensArray(self::PARSER_POC_FILE);
        $expected = array(
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 0, 0, 1),
            new PhpParser\Token(PhpParser\TokenKind::Unknown, 1, 1, 1),
            new PhpParser\Token(PhpParser\TokenKind::Name, 2, 2, 3),
            new PhpParser\Token(PhpParser\TokenKind::ClassKeyword, 5, 7, 7),
            new PhpParser\Token(PhpParser\TokenKind::Name, 12, 13, 2),
            new PhpParser\Token(PhpParser\TokenKind::OpenBraceToken, 14, 16, 3),

            new PhpParser\Token(PhpParser\TokenKind::FunctionKeyword, 17, 56, 47),
            new PhpParser\Token(PhpParser\TokenKind::Name, 64, 65, 4),
            new PhpParser\Token(PhpParser\TokenKind::OpenParenToken, 68, 68, 1),
            new PhpParser\Token(PhpParser\TokenKind::VariableName, 69, 69, 8),
            new PhpParser\Token(PhpParser\TokenKind::CloseParenToken, 77, 77, 1),
            new PhpParser\Token(PhpParser\TokenKind::OpenBraceToken, 78, 78, 1),
            new PhpParser\Token(PhpParser\TokenKind::EchoKeyword, 79, 88, 13),
            new PhpParser\Token(PhpParser\TokenKind::VariableName, 92, 92, 8),
            new PhpParser\Token(PhpParser\TokenKind::SemicolonToken, 100, 100, 1),
            new PhpParser\Token(PhpParser\TokenKind::PublicKeyword, 101, 107, 12),
            new PhpParser\Token(PhpParser\TokenKind::VariableName, 113, 114, 3),
            new PhpParser\Token(PhpParser\TokenKind::SemicolonToken, 116, 116, 1),
            new PhpParser\Token(PhpParser\TokenKind::EndOfFileToken, 117,117,0)
        );

        foreach ($tokensArray as $index=>$token) {
            $this->assertEquals($expected[$index], $token, "Awesome");
        }
    }
}