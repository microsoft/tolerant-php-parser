<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

require_once(__DIR__ . "/../src/bootstrap.php");

use Microsoft\PhpParser\LineCharacterPosition;
use Microsoft\PhpParser\PositionUtilities;
use PHPUnit\Framework\TestCase;

class UtilitiesTest extends TestCase {
    public function testGetLineCharacterPositionFromPosition() {
        $text = <<< 'PHP'
hello
there
awesome
PHP;

        $this->assertEquals(
            new LineCharacterPosition(0, 0),
            PositionUtilities::getLineCharacterPositionFromPosition(0, $text)
        );

        $this->assertEquals(
            new LineCharacterPosition(1, 0),
            PositionUtilities::getLineCharacterPositionFromPosition(6, $text)
        );

        $this->assertEquals(
            new LineCharacterPosition(1, 3),
            PositionUtilities::getLineCharacterPositionFromPosition(9, $text)
        );

        $this->assertEquals(
            PositionUtilities::getLineCharacterPositionFromPosition(\strlen($text), $text),
            PositionUtilities::getLineCharacterPositionFromPosition(\strlen($text)+1, $text),
            "Positions greater than text length should resolve to maximum position in text."
        );

        $this->assertEquals(
            PositionUtilities::getLineCharacterPositionFromPosition(0, $text),
            PositionUtilities::getLineCharacterPositionFromPosition(-1, $text),
            "Positions less than zero should resolve to minimum position in text."
        );
    }
}