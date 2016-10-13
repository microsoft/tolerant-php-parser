<?php

namespace PhpParser;

class Node implements \JsonSerializable  {
    public $kind;
    public $parent;
    public $children;

    public function __construct(int $kind) {
        $this->kind = $kind;
    }

    public function getLength() {
        $length = 0;

        foreach ($this->children as $child) {
            if ($child instanceof Node) {
                $length += $child->getLength();
            } else if ($child instanceof Token) {
                $length += $child->length;
            }
        }
        return $length;
    }

    public function getAllChildren() {
        $allChildren = array();

        foreach ($this->children as $child) {
            if ($child instanceof Node) {
                array_push($allChildren, $child);
                foreach ($child->getAllChildren() as $subChild) {
                    array_push($allChildren, $subChild);
                }
            } else if ($child instanceof Token) {
                array_push($allChildren, $child);
            }
        }
        return $allChildren;
    }

    public function getStart() {
        $child = $this->children[0];
        if ($child instanceof Node) {
            return $child->getStart();
        } else if ($child instanceof Token) {
            return $child->start;
        }
        throw new \Exception("Unknown type in AST");
    }

    public function jsonSerialize() {
        $constants = (new \ReflectionClass("PhpParser\\NodeKind"))->getConstants();
        $kindName = $this->kind;
        foreach ($constants as $name=>$val) {
            if ($val == $this->kind) {
                $kindName = $name;
            }
        }

        return ["$kindName" => $this->children];
    }
}

class NodeKind {
    const SourceFileNode = 0;
    const ClassNode = 1;
    const BlockNode = 2;
    const MethodBlockNode = 3;
    const MethodNode = 4;
    const StatementNode = 5;
    const ClassMembersNode = 6;
    const Count = 7;
    const TemplateExpression = 8;
}