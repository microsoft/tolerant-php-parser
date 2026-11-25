<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use Microsoft\PhpParser\Token;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\BaseTestListener;
use PHPUnit\Framework\AssertionFailedError;

class LexicalGrammarTest extends TestCase {
    /**
     * @dataProvider lexicalProvider
     */
    public function testOutputTokenClassificationAndLength($testCaseFile, $expectedTokensFile) {
        $fileContents = file_get_contents($testCaseFile);

        $lexer = \Microsoft\PhpParser\TokenStreamProviderFactory::GetTokenStreamProvider($fileContents);
        $GLOBALS["SHORT_TOKEN_SERIALIZE"] = true;
        $tokens = str_replace("\r\n", "\n", json_encode($lexer->getTokensArray(), JSON_PRETTY_PRINT));
        $GLOBALS["SHORT_TOKEN_SERIALIZE"] = false;

        if (!file_exists($expectedTokensFile)) {
            file_put_contents($expectedTokensFile, $tokens);
            self::markTestSkipped('Snapshot generated');
        }

        $expectedTokens = str_replace("\r\n", "\n", file_get_contents($expectedTokensFile));

        $this->assertEquals(
            $expectedTokens,
            $tokens,
            "input: $testCaseFile\r\nexpected: $expectedTokensFile (delete expected to regenerate)"
        );
    }

    public function lexicalProvider() {
        $testCases = glob(__dir__ . "/cases/lexical/*.php");

        $skipped = json_decode(file_get_contents(__DIR__ . "/skipped.json"));

        $testProviderArray = [];
        foreach ($testCases as $testCase) {
            if (in_array(basename($testCase), $skipped)) {
                continue;
            }
            $testProviderArray[basename($testCase)] = [$testCase, $testCase . ".tokens"];
        }

        return $testProviderArray;
    }

    /**
     * @dataProvider lexicalSpecProvider
     */
    public function testSpecTokenClassificationAndLength($testCaseFile, $expectedTokensFile) {
        $lexer = \Microsoft\PhpParser\TokenStreamProviderFactory::GetTokenStreamProvider(file_get_contents($testCaseFile));
        $tokensArray = $lexer->getTokensArray();
        $tokens = str_replace("\r\n", "\n", json_encode($tokensArray, JSON_PRETTY_PRINT));
        file_put_contents($expectedTokensFile, $tokens);
        foreach ($tokensArray as $child) {
            if ($child instanceof Token) {
                $this->assertNotEquals(\Microsoft\PhpParser\TokenKind::Unknown, $child->kind, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
                $this->assertNotEquals(\Microsoft\PhpParser\TokenKind::SkippedToken, $child->kind, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
                $this->assertNotEquals(\Microsoft\PhpParser\TokenKind::MissingToken, $child->kind, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
            }
        }
//        $tokens = str_replace("\r\n", "\n", json_encode($tokens, JSON_PRETTY_PRINT));
//        $this->assertEquals($expectedTokens, $tokens, "input: $testCaseFile\r\nexpected: $expectedTokensFile");
    }

    public function lexicalSpecProvider() {
        $testCases = glob(__dir__ . "/cases/php-langspec/**/*.php");

        $testProviderArray = [];
        foreach ($testCases as $testCase) {
            $testProviderArray[basename($testCase)] = [$testCase, $testCase . ".tree"];
        }

        return $testProviderArray;
    }
}
