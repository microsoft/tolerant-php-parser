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

        return new Range($start, $end);
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
        $length = \strlen($text);
        if ($pos >= $length) {
            $pos = $length;
        } elseif ($pos < 0) {
            $pos = 0;
        }

        $lastNewlinePos = \strrpos($text, "\n", -($length - $pos));
        $char = $pos - ($lastNewlinePos === false ? 0 : $lastNewlinePos + 1);
        $line = $pos > 0 ? \substr_count($text, "\n", 0, $pos) : 0;
        return new LineCharacterPosition($line, $char);
    }
}
