<?php

require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../parser.php");
require_once(__DIR__ . "/../Token.php");

use PhpParser\Token;
use PHPUnit\Framework\TestCase;


class ParserGrammarTest extends TestCase {

    /**
     * @dataProvider treeProvider
     */
    public function testOutputTreeClassificationAndLength($testCaseFile, $expectedTokensFile) {
        $expectedTokens = str_replace("\r\n", "\n", file_get_contents($expectedTokensFile));
        $parser = new \PhpParser\Parser($testCaseFile);
        $tokens = str_replace("\r\n", "\n", json_encode($parser->parseSourceFile(), JSON_PRETTY_PRINT));
        // file_put_contents($expectedTokensFile, str_replace("\n", "\r\n", $tokens));
        $fileContents = file_get_contents($testCaseFile);
        $this->assertEquals($expectedTokens, $tokens, "input doc:\r\n$fileContents\r\n\r\ninput: $testCaseFile\r\nexpected: $expectedTokensFile");
    }

    const FILE_PATTERN = __DIR__ . "/cases/parser/*";

    public function treeProvider() {
        $testCases = glob(self::FILE_PATTERN . ".php");
        $tokensExpected = glob(self::FILE_PATTERN . ".php.tree");

        $testProviderArray = array();
        foreach ($testCases as $index=>$testCase) {
            $testProviderArray[basename($testCase)] = [$testCase, $tokensExpected[$index]];
        }

        return $testProviderArray;
    }

    /**
     * @dataProvider outTreeProvider
     */
    public function testSpecOutputTreeClassificationAndLength($testCaseFile, $expectedTokensFile) {
        $expectedTokens = str_replace("\r\n", "\n", file_get_contents($expectedTokensFile));
        $parser = new \PhpParser\Parser($testCaseFile);
        $sourceFile = $parser->parseSourceFile();
        $tokens = str_replace("\r\n", "\n", json_encode($sourceFile, JSON_PRETTY_PRINT));
        file_put_contents($expectedTokensFile, $tokens);

        echo file_get_contents($testCaseFile);
        foreach ($sourceFile->getAllChildren() as $child) {
            if ($child instanceof Token) {
                $this->assertNotEquals(\PhpParser\TokenKind::Unknown, $child->kind, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
                $this->assertNotEquals(\PhpParser\TokenKind::SkippedToken, $child->kind, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
                $this->assertNotEquals(\PhpParser\TokenKind::MissingToken, $child->kind, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
            }
        }
//        $tokens = str_replace("\r\n", "\n", json_encode($parser->parseSourceFile(), JSON_PRETTY_PRINT));
//        file_put_contents($expectedTokensFile, $tokens);
//        $this->assertEquals($expectedTokens, $tokens, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
    }

    public function outTreeProvider() {
        $testCases = glob(__dir__ . "/cases/php-langspec/**/*.php");
        $tokensExpected = glob(__dir__ . "/cases/php-langspec/**/*.php.tree");

        $testProviderArray = array();
        foreach ($testCases as $index=>$testCase) {
            $testProviderArray[basename($testCase)] = [$testCase, $tokensExpected[$index]];
        }

        return $testProviderArray;
    }
}