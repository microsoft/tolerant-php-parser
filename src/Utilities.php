<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser;

use PhpParser\Node;

class Utilities {
    public static function getDiagnostics($node) {
        $tokenKindToText = array_flip(array_merge(OPERATORS_AND_PUNCTUATORS, KEYWORDS, RESERVED_WORDS));

        if ($node instanceof SkippedToken) {
            // TODO - consider also attaching parse context information to skipped tokens
            // this would allow us to provide more helpful error messages that inform users what to do
            // about the problem rather than simply pointing out the mistake.
            return yield new Diagnostic(
                DiagnosticKind::Error,
                "Unexpected '" .
                (isset($tokenKindToText[$node->kind])
                    ? $tokenKindToText[$node->kind]
                    : Token::getTokenKindNameFromValue($node->kind)) .
                "'",
                $node->start,
                $node->getEnd() - $node->start
            );
        } elseif ($node instanceof MissingToken) {
            return yield new Diagnostic(
                DiagnosticKind::Error,
                "'" .
                (isset($tokenKindToText[$node->kind])
                    ? $tokenKindToText[$node->kind]
                    : Token::getTokenKindNameFromValue($node->kind)) .
                "' expected.",
                $node->start,
                $node->getEnd() - $node->start
            );
        }

        if ($node === null || $node instanceof Token) {
            return;
        }

        if ($node instanceof Node) {
            switch ($node->kind) {
                case NodeKind::MethodNode:
                    foreach ($node->modifiers as $modifier) {
                        if ($modifier->kind === TokenKind::VarKeyword) {
                            yield new Diagnostic(
                                DiagnosticKind::Error,
                                "Unexpected modifier '" . $tokenKindToText[$modifier->kind] . "'",
                                $modifier->start,
                                $modifier->getEnd() - $modifier->start
                            );
                        }
                    }
                    break;
            }
        }

        foreach ($node->getChildNodesAndTokens() as $child) {
            yield from Utilities::getDiagnostics($child);
        }
    }
    
    public static function getRangeFromPosition($pos, $length, $text) {
        $start = self::getLineCharacterPositionFromPosition($pos, $text);
        $end = self::getLineCharacterPositionFromPosition($pos + $length, $text);

        return new Range ($start, $end);
    }

    public static function getLineCharacterPositionFromPosition($pos, $text) {
        $newlinePositions = [];
        $newlinePos = -1;
        while ($newlinePos = strpos($text, "\n", $newlinePos + 1)) {        
            if ($newlinePos < $pos) {
                array_push($newlinePositions, $newlinePos);
                continue;
            }
            break;
        }

        $lastNewline = count($newlinePositions) - 1;
        $char = $pos - ($lastNewline >= 0 ? $newlinePositions[$lastNewline] : 0);
        $line = count($newlinePositions);

        return new LineCharacterPosition($line, $char);
    }
}

class Range {
    public $start;
    public $end;

    public function __construct(LineCharacterPosition $start, LineCharacterPosition $end) {
        $this->start = $start;
        $this->end = $end;
    }
}

class LineCharacterPosition {
    public $line;
    public $character;

    public function __construct(int $line, int $character) {
        $this->line = $line;
        $this->character = $character;
    }
}