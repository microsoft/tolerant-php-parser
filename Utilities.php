<?php

namespace PhpParser;

use PhpParser\Node\Node;

class Utilities {
    public static function getErrors($node) {
        if ($node instanceof SkippedToken || $node instanceof MissingToken) {
            return yield $node;
        }

        if ($node === null || $node instanceof Token) {
            return;
        }

        if ($node instanceof Node) {
            switch ($node->kind) {
                case NodeKind::MethodNode:
                    foreach ($node->modifiers as $modifier) {
                        if ($modifier->kind === TokenKind::VarKeyword) {
                            yield new SkippedToken($modifier);
                        }
                    }
                    break;
            }
        }

        foreach ($node->getChildNodesAndTokens() as $child) {
            yield from Utilities::getErrors($child);
        }
    }
}