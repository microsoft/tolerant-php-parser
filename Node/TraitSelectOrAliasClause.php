<?php

namespace PhpParser\Node;
use PhpParser\NodeKind;
use PhpParser\Token;

class TraitSelectOrAliasClause extends Node {

    /** @var Token */
    public $name;

    /** @var Token */
    public $asOrInsteadOfKeyword;

    /** @var Token[] */
    public $modifiers;

    /** @var Token */
    public $targetName;

    public function __construct() {
        parent::__construct(NodeKind::TraitSelectOrAliasClause);
    }
}