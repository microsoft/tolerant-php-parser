<?php

namespace PhpParser\Node;

use PhpParser\NodeKind;
use PhpParser\Token;

class Script extends Node {
    /** @var ScriptSection[] */
    public $scriptSectionList;
    /** @var Token */
    public $endOfFileToken;

    public function __construct() {
        parent::__construct(NodeKind::SourceFileNode);
    }
}