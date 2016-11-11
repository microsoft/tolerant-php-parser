<?php
namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class ArrayElement extends Node {

    /** @var Expression | null */
    public $elementKey;

    /** @var Token | null */
    public $arrowToken;

    /** @var Token | null */
    public $byRef;

    /** @var Expression */
    public $elementValue;

    public function __construct() {
        parent::__construct(NodeKind::ArrayElement);
    }
}