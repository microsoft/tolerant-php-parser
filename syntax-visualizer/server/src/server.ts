/* --------------------------------------------------------------------------------------------
 * Copyright (c) Microsoft Corporation. All rights reserved.
 * Licensed under the MIT License. See License.txt in the project root for license information.
 * ------------------------------------------------------------------------------------------ */
'use strict';

import {
	IPCMessageReader, IPCMessageWriter,
	createConnection, IConnection, TextDocumentSyncKind,
	TextDocuments, TextDocument, Diagnostic, DiagnosticSeverity,
	InitializeParams, InitializeResult, TextDocumentPositionParams,
	CompletionItem, CompletionItemKind
} from 'vscode-languageserver';

var os = require('os');
var execSync = require('child_process').execSync;
var querystring = require('querystring');
var path = require('path');
var fs = require('fs');

// Create a connection for the server. The connection uses Node's IPC as a transport
let connection: IConnection = createConnection(new IPCMessageReader(process), new IPCMessageWriter(process));

// Create a simple text document manager. The text document manager
// supports full document sync only
let documents: TextDocuments = new TextDocuments();
// Make the text document manager listen on the connection
// for open, change and close text document events
documents.listen(connection);

// After the server has started the client sends an initilize request. The server receives
// in the passed params the rootPath of the workspace plus the client capabilites. 
let workspaceRoot: string;
connection.onInitialize((params): InitializeResult => {
	workspaceRoot = params.rootPath;
	return {
		capabilities: {
			// Tell the client that the server works in FULL text document sync mode
			textDocumentSync: documents.syncKind
		}
	}
});

documents.onDidOpen((change) => {
	validateTextDocument(change.document);
});

documents.onDidSave((change) => {
	validateTextDocument(change.document);
});

// The settings interface describe the server relevant settings part
interface PhpSettings {
	syntaxVisualizer: SyntaxVisualizerSettings;
}

interface SyntaxVisualizerSettings {
	parserPath: string;
}

// hold the parserSrc setting
let parserPath: string | null;
// The settings have changed. Is send on server activation
// as well.
connection.onDidChangeConfiguration((change) => {
	let syntaxVisualizerSettings = <SyntaxVisualizerSettings>change.settings.php.syntaxVisualizer;
	let fallbackParserPath = fs.existsSync(`${__dirname}/parser`)
		? `${__dirname}/parser/src`
		: `${__dirname}/../../../src`;
		
	parserPath = syntaxVisualizerSettings && syntaxVisualizerSettings.parserPath
		? syntaxVisualizerSettings.parserPath
		: fallbackParserPath;
	console.log(`parser path: ${parserPath}`);

	// Revalidate any open text documents
	documents.all().forEach(validateTextDocument);
});

function validateTextDocument(textDocument: TextDocument): void {
	var fileToRead = path.normalize(querystring.unescape(textDocument.uri)).substr(os.platform() === 'win32' ? 6 : 5);
	if (fileToRead.startsWith("x")) {
		return;
	}
	var cmd = fs.existsSync(`${__dirname}/parser`)
		? `php ${__dirname}/parse.php`
		: `php ${__dirname}/../../server/src/parse.php`;
	
	cmd += ` ${fileToRead} ${parserPath}`;
	var out = execSync(cmd).toString();
	var outErrors = JSON.parse(out);
	let diagnostics: Diagnostic[] = [];
	let lines = textDocument.getText().split(/\n/g);

	let allErrors = outErrors;

	for (var i = 0; i < allErrors.length; i++) {		
		let error = allErrors[i];
		diagnostics.push({
			severity: DiagnosticSeverity.Error,
			range: error["range"],
			message: error["message"],
			source: 'syntax-visualizer'
		});
	}
	// Send the computed diagnostics to VSCode.
	connection.sendDiagnostics({ uri: textDocument.uri, diagnostics });
}

connection.onDidChangeWatchedFiles((change) => {
	// Monitored files have change in VSCode
	connection.console.log('We recevied an file change event');
});


// Listen on the connection
connection.listen();