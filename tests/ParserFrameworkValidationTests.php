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


class ParserFrameworkValidationTests extends TestCase {

    public function frameworkErrorProvider() {
        $totalSize = 0;
        $frameworks = glob( __DIR__ . "/../validation/frameworks/*", GLOB_ONLYDIR);

        $testProviderArray = array();
        foreach ($frameworks as $frameworkDir) {
            $frameworkName = basename($frameworkDir);
            $iterator = new RecursiveDirectoryIterator(__DIR__ . "/../validation/frameworks/" . $frameworkName);

            foreach (new RecursiveIteratorIterator($iterator) as $file) {
                if (strpos($file, ".php") !== false) {
                    $totalSize += $file->getSize();
                    $testProviderArray[$frameworkName . "::" . $file->getBasename()] = [$file->getPathname(), $frameworkName];
                }
            }

        }
        return $testProviderArray;
    }

    /**
     * @dataProvider frameworkErrorProvider
     */
    public function testFramworkErrors($testCaseFile, $frameworkName) {
        $fileContents = file_get_contents($testCaseFile);
        $parser = new \PhpParser\Parser();
        $sourceFile = $parser->parseSourceFile($fileContents);

        $directory = __DIR__ . "/output/$frameworkName/";
        if (!file_exists($dir = __DIR__ . "/output")) {
            mkdir($dir);
        }
        if (!file_exists($directory)) {
            mkdir($directory);
        }
        $outFile = $directory . basename($testCaseFile);
        file_put_contents($outFile, $fileContents);

        foreach ($sourceFile->getDescendantNodesAndTokens() as $child) {
            if ($child instanceof Token) {
                $this->assertNotEquals(\PhpParser\TokenKind::Unknown, $child->kind, "input: $testCaseFile\r\nexpected: ");
                $this->assertNotTrue($child instanceof \PhpParser\SkippedToken, "input: $testCaseFile\r\nexpected: ");
                $this->assertNotTrue($child instanceof \PhpParser\MissingToken, "input: $testCaseFile\r\nexpected: ");
            }
        }

        unlink($outFile);
        // echo json_encode($parser->getErrors($sourceFile));
    }
}