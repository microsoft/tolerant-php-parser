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
    const ScriptSection = 17;
    const NamedLabelStatement = 18;
    const IfStatementNode = 19;
    const ElseIfClauseNode = 20;
    const ElseClauseNode = 21;
    const CaseStatementNode = 22;
    const DefaultStatementNode = 23;
    const SwitchStatementNode = 24;
    const WhileStatementNode = 25;
    const DoWhileStatementNode = 26;
    const ForStatementNode = 27;
    const ForeachStatementNode = 28;
    const ForeachKeyNode = 29;
    const ForeachValueNode = 30;
    const GotoStatementNode = 31;
    const BreakOrContinueStatementNode = 32;
    const ReturnStatement = 33;
    const ThrowStatement = 34;
    const CatchClauseNode = 35;
    const FinallyClauseNode = 36;
    const DeclareStatementNode = 37;
    const DeclareDirectiveNode = 38;
    const Variable = 39;
    const ExpressionStatement = 40;
    const EchoExpression = 41;
    const Literal = 42;
    const UnknownExpression = 43;
    const ListIntrinsicExpression = 44;
    const ArrayElement = 45;
    const ArrayIntrinsicExpression = 46;
    const EmptyIntrinsicExpression = 47;
    const EvalIntrinsicExpression = 48;
    const ParenthesizedExpression = 49;
    const ExitIntrinsicExpression = 50;
}