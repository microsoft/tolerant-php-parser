<?php
use PHPUnit\Framework\TestCase;

// TODO autoload classes
require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../Token.php");

class SanityTest extends TestCase {
    // TODO test w/ multiple files
    const FILENAME = __dir__ . "/fixtures/testfile.php";

    /**
     * TODO not actually a test - just a convenience during initial development
     */
    public function testSanityCheck() {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
        $tokensArray = PhpParser\getTokensArray(self::FILENAME);

        foreach ($tokensArray as $index=>$token) {
            if ($index == count($tokensArray) - 1) {
                $this->assertEquals($token->kind, 1);
            } else {
                $this->assertEquals($token->kind, 0);
            }
        }
    }
}