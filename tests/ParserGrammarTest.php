<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../parser.php");
require_once(__DIR__ . "/../Token.php");

use PhpParser\Token;
use PHPUnit\Framework\TestCase;

class ParserGrammarTest extends TestCase {

    public function run(PHPUnit_Framework_TestResult $result = null) : PHPUnit_Framework_TestResult {
        if (!isset($GLOBALS["GIT_CHECKOUT"])) {
            $GLOBALS["GIT_CHECKOUT"] = true;
            exec("git checkout " . __DIR__ . "/cases/parser/*.php.tree");
//            $GLOBALS["SKIPPED"] = [];
//            unlink("skipped.json");
        }

        $result->addListener(new class() extends PHPUnit_Framework_BaseTestListener  {
            function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
                if (isset($test->expectedTokensFile) && isset($test->tokens)) {
                    file_put_contents($test->expectedTokensFile, str_replace("\r\n", "\n", $test->tokens));
                }
                parent::addFailure($test, $e, $time);
            }

//            function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
//                if (!in_array($test->dataDescription(), $GLOBALS["SKIPPED"])) {
//                    array_push($GLOBALS["SKIPPED"], $test->dataDescription());
//                    file_put_contents("skipped.json", json_encode($GLOBALS["SKIPPED"], JSON_PRETTY_PRINT));
//                }
//                parent::addSkippedTest($test, $e, $time);
//            }
        });

        $result = parent::run($result);
        return $result;
    }

    /**
     * @dataProvider treeProvider
     */
    public function testOutputTreeClassificationAndLength($testCaseFile, $expectedTokensFile) {
        $this->expectedTokensFile = $expectedTokensFile;

        $expectedTokens = str_replace("\r\n", "\n", file_get_contents($expectedTokensFile));
        $parser = new \PhpParser\Parser($testCaseFile);
        $GLOBALS["SHORT_TOKEN_SERIALIZE"] = true;
        $tokens = str_replace("\r\n", "\n", json_encode($parser->parseSourceFile(), JSON_PRETTY_PRINT));
        $GLOBALS["SHORT_TOKEN_SERIALIZE"] = false;
        $fileContents = file_get_contents($testCaseFile);
        $this->tokens = $tokens;

        $outputStr = "input doc:\r\n$fileContents\r\n\r\ninput: $testCaseFile\r\nexpected: $expectedTokensFile";

        $this->assertEquals($expectedTokens, $tokens, $outputStr);
    }

    const FILE_PATTERN = __DIR__ . "/cases/parser/*";

    public function treeProvider() {
        $testCases = glob(self::FILE_PATTERN . ".php");
        $tokensExpected = glob(self::FILE_PATTERN . ".php.tree");
        $skipped = json_decode(file_get_contents(__DIR__ . "/skipped.json"));

        $testProviderArray = array();
        foreach ($testCases as $index=>$testCase) {
            if (in_array(basename($testCase), $skipped)) {
                continue;
            }
            $testProviderArray[basename($testCase)] = [$testCase, $tokensExpected[$index]];
        }

        return $testProviderArray;
    }

    /**
     * @dataProvider outTreeProvider
     */
    public function testSpecOutputTreeClassificationAndLength($testCaseFile, $expectedTokensFile) {
        $parser = new \PhpParser\Parser($testCaseFile);
        $sourceFile = $parser->parseSourceFile();
        $tokens = str_replace("\r\n", "\n", json_encode($sourceFile, JSON_PRETTY_PRINT));
        file_put_contents($expectedTokensFile, $tokens);

//        echo file_get_contents($testCaseFile);
        foreach ($sourceFile->getAllChildren() as $child) {
            if ($child instanceof Token) {
                $this->assertNotEquals(\PhpParser\TokenKind::Unknown, $child->kind, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
                $this->assertNotTrue($child instanceof \PhpParser\SkippedToken, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
                $this->assertNotTrue($child instanceof \PhpParser\MissingToken, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
            }
        }
    }

    public function outTreeProvider() {
        $testCases = glob(__dir__ . "/cases/php-langspec/**/*.php");
        foreach ($testCases as $case) {
             $tokensExpected[] = $filename = dirname($case) . "/" . basename($case) . ".tree";
        }

        $testProviderArray = array();
        foreach ($testCases as $index=>$testCase) {
            $testProviderArray[basename($testCase)] = [$testCase, $tokensExpected[$index]];
        }

        return $testProviderArray;
    }
}