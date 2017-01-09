<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;

use PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class DelimitedList extends Node {
    /** @var Token[]|Node[] */
    public $children;

    public function __construct($nodeKind = NodeKind::DelimitedList) {
        parent::__construct($nodeKind);
    }

    public function getValues() {
        $i = 0;
        foreach($this->children as $value) {
            if ($i++ % 2 == 1) {
                yield $value;
            }
        }
    }

    public function addToken($node) {
        if ($node === null) {
            return;
        }
        if (!isset($this->children)) {
            $this->children = array();
        }
        array_push($this->children, $node);
    }
}