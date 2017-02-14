<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser;

use Microsoft\PhpParser\Node;

class DiagnosticsProvider {
    /**
     * Traverses AST to generate diagnostics.
     * @param \Microsoft\PhpParser\Node $node
     * @return \Generator | Diagnostic[]
     */
    public static function getDiagnostics($node) : \Generator {
        $tokenKindToText = \array_flip(\array_merge(
            TokenStringMaps::OPERATORS_AND_PUNCTUATORS,
            TokenStringMaps::KEYWORDS,
            TokenStringMaps::RESERVED_WORDS
        ));

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
                $node->getEndPosition() - $node->start
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
                $node->getEndPosition() - $node->start
            );
        }

        if ($node === null || $node instanceof Token) {
            return;
        }

        if ($node instanceof Node) {
            if ($node instanceof Node\MethodDeclaration) {
                foreach ($node->modifiers as $modifier) {
                    if ($modifier->kind === TokenKind::VarKeyword) {
                        yield new Diagnostic(
                            DiagnosticKind::Error,
                            "Unexpected modifier '" . $tokenKindToText[$modifier->kind] . "'",
                            $modifier->start,
                            $modifier->length
                        );
                    }
                }
            }
            elseif ($node instanceof Node\Statement\NamespaceUseDeclaration) {
                if (count($node->useClauses->children) > 1) {
                    foreach ($node->useClauses->children as $useClause) {
                        if($useClause instanceof Node\NamespaceUseClause && !is_null($useClause->openBrace)) {
                            yield new Diagnostic(
                                DiagnosticKind::Error,
                                "Semicolon expected.",
                                $useClause->getEndPosition(),
                                1
                            );
                        }
                    }
                }
            }
        }

        foreach ($node->getChildNodesAndTokens() as $child) {
            yield from DiagnosticsProvider::getDiagnostics($child);
        }
    }
}
