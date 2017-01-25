<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node\Statement;
use PhpParser\Node\StatementNode;
use PhpParser\Token;

class InlineHtml extends StatementNode  {
    /** @var Token | null */
    public $scriptSectionEndTag;

    /** @var Token */
    public $text;

    /** @var Token | null */
    public $scriptSectionStartTag;

    public function getNodeKindName() : string {
        return 'InlineHtml';
    }

}