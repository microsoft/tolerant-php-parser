<?php

namespace PhpParser;

class NodeKind {
    const SourceFileNode = 0;
    const ClassNode = 1;
    const BlockNode = 2;
    const CompoundStatementNode = 3;
    const MethodNode = 4;
    const StatementNode = 5;
    const ClassMembersNode = 6;
    const Count = 7;
    const TemplateExpression = 8;
    const EmptyStatement = 9;
    const FunctionNode = 10;
    const DelimitedList = 11;
    const Expression = 12;
    const Name = 13;
    const ParameterNode = 14;
    const QualifiedName = 15;
    const RelativeSpecifier = 16;
}