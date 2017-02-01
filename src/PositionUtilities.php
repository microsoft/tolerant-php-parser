<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser;

class PositionUtilities {
    public static function getRangeFromPosition($pos, $length, $text) {
        $start = self::getLineCharacterPositionFromPosition($pos, $text);
        $end = self::getLineCharacterPositionFromPosition($pos + $length, $text);

        return new Range ($start, $end);
    }

    /**
     * Get's 0-indexed LineCharacterPosition from 0-indexed position into $text.
     *
     * Out of bounds positions are handled gracefully. Positions greater than the length of text length
     * are resolved to text length, and negative positions are resolved to 0.
     * TODO consider throwing exception instead.
     *
     * @param $pos
     * @param $text
     * @return LineCharacterPosition
     */
    public static function getLineCharacterPositionFromPosition($pos, $text) : LineCharacterPosition {
        if ($pos >= \strlen($text)) {
            $pos = \strlen($text);
        } elseif ($pos < 0) {
            $pos = 0;
        }

        $newlinePositions = [];
        $newlinePos = -1;
        while ($newlinePos = \strpos($text, "\n", $newlinePos + 1)) {
            if ($newlinePos < $pos) {
                $newlinePositions[] = $newlinePos;
                continue;
            }
            break;
        }

        $lastNewline = \count($newlinePositions) - 1;
        $char = $pos - ($lastNewline >= 0 ? $newlinePositions[$lastNewline] + 1 : 0);
        $line = \count($newlinePositions);

        return new LineCharacterPosition($line, $char);
    }
}