<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

require_once(__DIR__ . "/../src/TokenStreamProviderFactory.php");
require_once(__DIR__ . "/../src/Parser.php");
require_once(__DIR__ . "/../src/Token.php");

use Microsoft\PhpParser\Token;
use Microsoft\PhpParser\Diagnostics;
use PHPUnit\Framework\TestCase;

class ParserGrammarTest extends TestCase {

    public function run(PHPUnit_Framework_TestResult $result = null) : PHPUnit_Framework_TestResult {
        if (!isset($GLOBALS["GIT_CHECKOUT"])) {
            $GLOBALS["GIT_CHECKOUT"] = true;
            exec("git -C " . dirname(self::FILE_PATTERN) . " checkout *.php.tree");
        }

        $result->addListener(new class() extends PHPUnit_Framework_BaseTestListener  {
            function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
                if (isset($test->expectedTokensFile) && isset($test->tokens)) {
                    file_put_contents($test->expectedTokensFile, str_replace("\r\n", "\n", $test->tokens));
                }
                parent::addFailure($test, $e, $time);
            }
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
        $fileContents = file_get_contents($testCaseFile);
        $parser = new \Microsoft\PhpParser\Parser();
        $GLOBALS["SHORT_TOKEN_SERIALIZE"] = true;
        $tokens = str_replace("\r\n", "\n", json_encode($parser->parseSourceFile($fileContents), JSON_PRETTY_PRINT));
        $GLOBALS["SHORT_TOKEN_SERIALIZE"] = false;
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
    public function testSpecOutputTreeClassificationAndLength($testCaseFile, $expectedTreeFile) {
        $parser = new \Microsoft\PhpParser\Parser();
        $sourceFile = $parser->parseSourceFile(file_get_contents($testCaseFile));
        $tokens = str_replace("\r\n", "\n", json_encode($sourceFile, JSON_PRETTY_PRINT));
        file_put_contents($expectedTreeFile, $tokens);

        $this->assertEquals(0, iterator_count(Diagnostics::getDiagnostics($sourceFile)));
    }

    public function outTreeProvider() {
        $testCases = glob(__dir__ . "/cases/php-langspec/**/*.php");
        $skipped = json_decode(file_get_contents(__DIR__ . "/skipped.json"));
        
        $testProviderArray = array();
        foreach ($testCases as $case) {
            if (in_array(basename($case), $skipped)) {
                continue;
            }
            $testProviderArray[basename($case)] = [$case, $case . ".tree"];
        }

        return $testProviderArray;
    }
}