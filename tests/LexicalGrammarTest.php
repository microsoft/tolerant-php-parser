<?php

require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../parser.php");
require_once(__DIR__ . "/../Token.php");

use PhpParser\Token;
use PHPUnit\Framework\TestCase;


class LexicalGrammarTest extends TestCase {

    /**
     * @dataProvider lexicalProvider
     */
    public function testOutputTokenClassificationAndLength($testCaseFile, $expectedTokensFile) {
        $expectedTokens = str_replace("\r\n", "\n", file_get_contents($expectedTokensFile));
        $lexer = new \PhpParser\Lexer($testCaseFile);
        $tokens = str_replace("\r\n", "\n", json_encode($lexer->getTokensArray(), JSON_PRETTY_PRINT));
        $this->assertEquals($expectedTokens, $tokens, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
    }

    public function lexicalProvider() {
        $testCases = glob(__dir__ . "/cases/lexical/*.php");
        $tokensExpected = glob(__dir__ . "/cases/lexical/*.php.tokens");

        $testProviderArray = array();
        foreach ($testCases as $index=>$testCase) {
            $testProviderArray[basename($testCase)] = [$testCase, $tokensExpected[$index]];
        }

        return $testProviderArray;
    }

    /**
     * @dataProvider lexicalSpecProvider
     */
    public function testSpecTokenClassificationAndLength($testCaseFile, $expectedTokensFile) {
        $expectedTokens = str_replace("\r\n", "\n", file_get_contents($expectedTokensFile));
        $lexer = new \PhpParser\Lexer($testCaseFile);
        $tokensArray = $lexer->getTokensArray();
        $tokens = str_replace("\r\n", "\n", json_encode($tokensArray, JSON_PRETTY_PRINT));
        file_put_contents($expectedTokensFile, $tokens);
        foreach ($tokensArray as $child) {
            if ($child instanceof Token) {
                $this->assertNotEquals(\PhpParser\TokenKind::Unknown, $child->kind, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
                $this->assertNotEquals(\PhpParser\TokenKind::SkippedToken, $child->kind, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
                $this->assertNotEquals(\PhpParser\TokenKind::MissingToken, $child->kind, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
            }
        }
//        $tokens = str_replace("\r\n", "\n", json_encode($tokens, JSON_PRETTY_PRINT));
//        $this->assertEquals($expectedTokens, $tokens, "input: $testCaseFile\r\nexpected: $expectedTokensFile");

    }

    public function lexicalSpecProvider() {
        $testCases = glob(__dir__ . "/cases/php-langspec/**/*.php");
        $tokensExpected = glob(__dir__ . "/cases/php-langspec/**/*.php.tree");

        $testProviderArray = array();
        foreach ($testCases as $index=>$testCase) {
            $testProviderArray[basename($testCase)] = [$testCase, $tokensExpected[$index]];
        }

        return $testProviderArray;
    }

}