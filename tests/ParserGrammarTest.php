<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use Microsoft\PhpParser\Token;
use Microsoft\PhpParser\DiagnosticsProvider;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\BaseTestListener;
use PHPUnit\Framework\AssertionFailedError;

class ParserGrammarTest extends TestCase {
    public function run(TestResult $result = null) : TestResult {
        if (!isset($GLOBALS["GIT_CHECKOUT_PARSER"])) {
            $GLOBALS["GIT_CHECKOUT_PARSER"] = true;
            exec("git -C " . dirname(self::FILE_PATTERN) . " checkout *.php.tree *.php.diag");
        }

        $result->addListener(new class() extends BaseTestListener {
            function addFailure(Test $test, AssertionFailedError $e, $time) {
                if (isset($test->expectedTokensFile) && isset($test->tokens)) {
                    file_put_contents($test->expectedTokensFile, str_replace("\r\n", "\n", $test->tokens));
                }
                if (isset($test->expectedDiagnosticsFile) && isset($test->diagnostics)) {
                    file_put_contents($test->expectedDiagnosticsFile, str_replace("\r\n", "\n", $test->diagnostics));
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
    public function testOutputTreeClassificationAndLength($testCaseFile, $expectedTokensFile, $expectedDiagnosticsFile) {
        $this->expectedTokensFile = $expectedTokensFile;
        $this->expectedDiagnosticsFile = $expectedDiagnosticsFile;

        $fileContents = file_get_contents($testCaseFile);
        if (!file_exists($expectedTokensFile)) {
            file_put_contents($expectedTokensFile, $fileContents);
            exec("git add " . $expectedTokensFile);
        }

        if (!file_exists($expectedDiagnosticsFile)) {
            file_put_contents($expectedDiagnosticsFile, $fileContents);
            exec("git add " . $expectedDiagnosticsFile);
        }

        $parser = new \Microsoft\PhpParser\Parser();
        $sourceFileNode = $parser->parseSourceFile($fileContents);

        $expectedTokens = str_replace("\r\n", "\n", file_get_contents($expectedTokensFile));
        $expectedDiagnostics = str_replace("\r\n", "\n", file_get_contents($expectedDiagnosticsFile));

        $GLOBALS["SHORT_TOKEN_SERIALIZE"] = true;
        $tokens = str_replace("\r\n", "\n", json_encode($sourceFileNode, JSON_PRETTY_PRINT));
        $diagnostics = str_replace("\r\n", "\n", json_encode(\Microsoft\PhpParser\DiagnosticsProvider::getDiagnostics($sourceFileNode), JSON_PRETTY_PRINT));
        $GLOBALS["SHORT_TOKEN_SERIALIZE"] = false;

        $this->tokens = $tokens;
        $this->diagnostics = $diagnostics;

        $tokensOutputStr = "input doc:\r\n$fileContents\r\n\r\ninput: $testCaseFile\r\nexpected: $expectedTokensFile";
        $diagnosticsOutputStr = "input doc:\r\n$fileContents\r\n\r\ninput: $testCaseFile\r\nexpected: $expectedDiagnosticsFile";

        $this->assertEquals($expectedTokens, $tokens, $tokensOutputStr);
        $this->assertEquals($expectedDiagnostics, $diagnostics, $diagnosticsOutputStr);
    }

    const FILE_PATTERN = __DIR__ . "/cases/parser/*";
    const PHP74_FILE_PATTERN = __DIR__ . "/cases/parser74/*";

    public function treeProvider() {
        $testCases = glob(self::FILE_PATTERN . ".php");
        $skipped = json_decode(file_get_contents(__DIR__ . "/skipped.json"));

        $testProviderArray = array();
        foreach ($testCases as $testCase) {
            if (in_array(basename($testCase), $skipped)) {
                continue;
            }
            $testProviderArray[basename($testCase)] = [$testCase, $testCase . ".tree", $testCase . ".diag"];
        }

        if (PHP_VERSION_ID >= 70400) {
            // There are some test cases that depend on the php 7.3/php 7.4 lexer (e.g. the `??=` token).
            // If this project goes that route, these could be moved in the regular parser/ directory.
            // - It might be possible to emulate being able to parse this token instead (e.g. merge tokens if strpos($contents, `??=`) is not false.
            $testCases = glob(self::PHP74_FILE_PATTERN . ".php");
            foreach ($testCases as $testCase) {
                $testProviderArray[basename($testCase)] = [$testCase, $testCase . ".tree", $testCase . ".diag"];
            }
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

        $this->assertSame([], DiagnosticsProvider::getDiagnostics($sourceFile));
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
