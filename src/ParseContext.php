<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser;

class ParseContext {
    const SourceElements = 0;
    const BlockStatements = 1;
    const ClasslikeMembers = 2;
    const IfClause2Elements = 3;
    const SwitchStatementElements = 4;
    const CaseStatementElements = 5;
    const WhileStatementElements = 6;
    const ForStatementElements = 7;
    const ForeachStatementElements = 8;
    const DeclareStatementElements = 9;
    const Count = 10;
}
