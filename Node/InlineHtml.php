<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\SkippedToken;
use PhpParser\Token;
use PhpParser\TokenKind;

class InlineHtml extends  StatementNode  {
    /** @var Token | null */
    public $scriptSectionEndTag;

    /** @var Token */
    public $text;

    /** @var Token | null */
    public $scriptSectionStartTag;

    public function __construct() {
        parent::__construct(NodeKind::InlineHtml);
    }
}