<?php
/*---------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class DelimitedList extends Node {
    /** @var Node[] */
    public $children;

    public function __construct() {
        parent::__construct(NodeKind::DelimitedList);
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