<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

use Microsoft\PhpParser\Range;
use Microsoft\PhpParser\LineCharacterPosition;
use Microsoft\PhpParser\PositionUtilities;
use PHPUnit\Framework\TestCase;

class UtilitiesTest extends TestCase {
    const text = <<< 'PHP'
hello
there


awesome
PHP;

    public function getLineCharacterPositionFromPositionDataProvider(): array {
        return [
            [0, new LineCharacterPosition(0, 0)],
            [6, new LineCharacterPosition(1, 0)],
            [9, new LineCharacterPosition(1, 3)],

            // At EOL
            [5, new LineCharacterPosition(0, 5)],

            // At end
            [21, new LineCharacterPosition(4, 7)]
        ];
    }

    /**
     * @dataProvider getLineCharacterPositionFromPositionDataProvider
     */
    public function testGetLineCharacterPositionFromPosition($position, $lineCharPosition) {
        $this->assertEquals(
            $lineCharPosition,
            PositionUtilities::getLineCharacterPositionFromPosition($position, UtilitiesTest::text)
        );
    }

    public function testGetLineCharacterPositionFromPosition_Bounds() {
        $text = UtilitiesTest::text;

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


    public function testGetLineCharacterPositionFromPosition_AlwaysValid() {
        // Go past the bounds of the string - should still be valid
        for ($i=-3; $i < \strlen(UtilitiesTest::text) + 3; $i++) {
            $lineChar = PositionUtilities::getLineCharacterPositionFromPosition($i, UtilitiesTest::text);
            $this->assertGreaterThanOrEqual(0, $lineChar->line);
            $this->assertLessThanOrEqual(4, $lineChar->line);

            $this->assertGreaterThanOrEqual(0, $lineChar->character);
            $this->assertLessThanOrEqual(7, $lineChar->character);
        }
    }
}
