<?php

require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../parser.php");
require_once(__DIR__ . "/../Token.php");

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

}