<?php

require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../parser.php");
require_once(__DIR__ . "/../Token.php");

use PHPUnit\Framework\TestCase;


class ParserGrammarTest extends TestCase {

    /**
     * @dataProvider treeProvider
     */
    public function testOutputTreeClassificationAndLength($testCaseFile, $expectedTokensFile) {
        $expectedTokens = str_replace("\r\n", "\n", file_get_contents($expectedTokensFile));
        $parser = new \PhpParser\Parser($testCaseFile);
        $tokens = str_replace("\r\n", "\n", json_encode($parser->parseSourceFile(), JSON_PRETTY_PRINT));
        $this->assertEquals($expectedTokens, $tokens, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
    }

    public function treeProvider() {
        $testCases = glob(__dir__ . "/cases/parser/*.php");
        $tokensExpected = glob(__dir__ . "/cases/parser/*.php.tree");

        $testProviderArray = array();
        foreach ($testCases as $index=>$testCase) {
            $testProviderArray[basename($testCase)] = [$testCase, $tokensExpected[$index]];
        }

        return $testProviderArray;
    }

}