<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use PHPUnit\Framework\TestCase;

// TODO autoload classes
require_once(__DIR__ . "/../src/TokenStreamProviderFactory.php");
require_once(__DIR__ . "/../src/Parser.php");
require_once(__DIR__ . "/../src/Token.php");

class SanityTest extends TestCase {
    // TODO test w/ multiple files
    const FILENAME = __dir__ . "/cases/testfile.php";

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