<?php
/**
 * Created by PhpStorm.
 * User: Itani
 * Date: 11/22/2016
 * Time: 10:11 PM
 */

namespace PhpParser\Node;


use PhpParser\NodeKind;
use PhpParser\Token;

class NamespaceAliasingClause extends Node {
    /** @var Token */
    public $asKeyword;
    /** @var Token */
    public $name;

    public function __construct() {
        parent::__construct(NodeKind::NamespaceAliasingClause);
    }
}