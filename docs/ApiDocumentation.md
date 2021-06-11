# API Documentation
> Note: This documentation was auto-generated using this parser to help dogfood the API. It may be incomplete. Please contribute fixes to
`tools/PrintApiDocumentation.php` and suggest API improvements.
<hr>

## Node
### Node::getNodeKindName
> TODO: add doc comment

```php
public function getNodeKindName ( ) : string
```
### Node::getStartPosition
Gets start position of Node, not including leading comments and whitespace.
```php
public function getStartPosition ( ) : int
```
### Node::getFullStartPosition
Gets start position of Node, including leading comments and whitespace
```php
public function getFullStartPosition ( ) : int
```
### Node::getParent
Gets parent of current node (returns null if has no parent)
```php
public function getParent ( )
```
### Node::getFirstAncestor
Gets first ancestor that is an instance of one of the provided classes. Returns null if there is no match.
```php
public function getFirstAncestor ( ...$classNames )
```
### Node::getFirstChildNode
Gets first child that is an instance of one of the provided classes. Returns null if there is no match.
```php
public function getFirstChildNode ( ...$classNames )
```
### Node::getFirstDescendantNode
Gets first descendant node that is an instance of one of the provided classes. Returns null if there is no match.
```php
public function getFirstDescendantNode ( ...$classNames )
```
### Node::getRoot
Gets root of the syntax tree (returns self if has no parents)
```php
public function getRoot ( ) : Node
```
### Node::getDescendantNodesAndTokens
Gets generator containing all descendant Nodes and Tokens.
```php
public function getDescendantNodesAndTokens ( callable $shouldDescendIntoChildrenFn = null )
```
### Node::getDescendantNodes
Gets a generator containing all descendant Nodes.
```php
public function getDescendantNodes ( callable $shouldDescendIntoChildrenFn = null )
```
### Node::getDescendantTokens
Gets generator containing all descendant Tokens.
```php
public function getDescendantTokens ( callable $shouldDescendIntoChildrenFn = null )
```
### Node::getChildNodesAndTokens
Gets generator containing all child Nodes and Tokens (direct descendants). Does not return null elements.
```php
public function getChildNodesAndTokens ( ) : \Generator
```
### Node::getChildNodes
Gets generator containing all child Nodes (direct descendants)
```php
public function getChildNodes ( ) : \Generator
```
### Node::getChildTokens
Gets generator containing all child Tokens (direct descendants)
```php
public function getChildTokens ( )
```
### Node::getChildNames
Gets array of declared child names (cached). This is used as an optimization when iterating over nodes: For direct iteration PHP will create a properties hashtable on the object, thus doubling memory usage. We avoid this by iterating over just the names instead.
```php
public function getChildNames ( )
```
### Node::getWidth
Gets width of a Node (not including comment / whitespace trivia)
```php
public function getWidth ( ) : int
```
### Node::getFullWidth
Gets width of a Node (including comment / whitespace trivia)
```php
public function getFullWidth ( ) : int
```
### Node::getText
Gets string representing Node text (not including leading comment + whitespace trivia)
```php
public function getText ( ) : string
```
### Node::getFullText
Gets full text of Node (including leading comment + whitespace trivia)
```php
public function getFullText ( ) : string
```
### Node::getLeadingCommentAndWhitespaceText
Gets string representing Node's leading comment and whitespace text.
```php
public function getLeadingCommentAndWhitespaceText ( ) : string
```
### Node::jsonSerialize
> TODO: add doc comment

```php
public function jsonSerialize ( )
```
### Node::getEndPosition
Get the end index of a Node.
```php
public function getEndPosition ( )
```
### Node::getFileContents
> TODO: add doc comment

```php
public function getFileContents ( ) : string
```
### Node::getUri
> TODO: add doc comment

```php
public function getUri ( ) : string
```
### Node::getLastChild
> TODO: add doc comment

```php
public function getLastChild ( )
```
### Node::getDescendantNodeAtPosition
Searches descendants to find a Node at the given position.
```php
public function getDescendantNodeAtPosition ( int $pos )
```
### Node::getDocCommentText
Gets leading PHP Doc Comment text corresponding to the current Node. Returns last doc comment in leading comment / whitespace trivia, and returns null if there is no preceding doc comment.
```php
public function getDocCommentText ( )
```
### Node::__toString
> TODO: add doc comment

```php
public function __toString ( )
```
### Node::getImportTablesForCurrentScope
> TODO: add doc comment

```php
public function getImportTablesForCurrentScope ( )
```
### Node::getNamespaceDefinition
Gets corresponding NamespaceDefinition for Node. Returns null if in global namespace.
```php
public function getNamespaceDefinition ( )
```
### Node::getPreviousSibling
> TODO: add doc comment

```php
public function getPreviousSibling ( )
```
## Token
### Token::__construct
> TODO: add doc comment

```php
public function __construct ( $kind, $fullStart, $start, $length )
```
### Token::getLeadingCommentsAndWhitespaceText
> TODO: add doc comment

```php
public function getLeadingCommentsAndWhitespaceText ( string $document ) : string
```
### Token::getText
> TODO: add doc comment

```php
public function getText ( string $document = null )
```
### Token::getFullText
> TODO: add doc comment

```php
public function getFullText ( string & $document ) : string
```
### Token::getStartPosition
> TODO: add doc comment

```php
public function getStartPosition ( )
```
### Token::getFullStartPosition
> TODO: add doc comment

```php
public function getFullStartPosition ( )
```
### Token::getWidth
> TODO: add doc comment

```php
public function getWidth ( )
```
### Token::getFullWidth
> TODO: add doc comment

```php
public function getFullWidth ( )
```
### Token::getEndPosition
> TODO: add doc comment

```php
public function getEndPosition ( )
```
### Token::getTokenKindNameFromValue
> TODO: add doc comment

```php
public static function getTokenKindNameFromValue ( $kind )
```
### Token::jsonSerialize
> TODO: add doc comment

```php
public function jsonSerialize ( )
```
## Parser
### Parser::__construct
> TODO: add doc comment

```php
public function __construct ( )
```
### Parser::parseSourceFile
Generates AST from source file contents. Returns an instance of SourceFileNode, which is always the top-most Node-type of the tree.
```php
public function parseSourceFile ( string $fileContents, string $uri = null ) : SourceFileNode
```
## Associativity
## DiagnosticsProvider
### DiagnosticsProvider::checkDiagnostics
> TODO: add doc comment

```php
public static function checkDiagnostics ( $node )
```
### DiagnosticsProvider::getDiagnostics
> TODO: add doc comment

```php
public static function getDiagnostics ( Node $n ) : array
```
## PositionUtilities
### PositionUtilities::getRangeFromPosition
Gets a Range from 0-indexed position into $text. Out of bounds positions are handled gracefully. Positions greater than the length of text length are resolved to the end of the text, and negative positions are resolved to the beginning.
```php
public static function getRangeFromPosition ( $pos, $length, $text ) : Range
```
### PositionUtilities::getLineCharacterPositionFromPosition
Gets 0-indexed LineCharacterPosition from 0-indexed position into $text. Out of bounds positions are handled gracefully. Positions greater than the length of text length are resolved to text length, and negative positions are resolved to 0. TODO consider throwing exception instead.
```php
public static function getLineCharacterPositionFromPosition ( $pos, $text ) : LineCharacterPosition
```
## LineCharacterPosition
### LineCharacterPosition::__construct
> TODO: add doc comment

```php
public function __construct ( int $line, int $character )
```
## MissingToken
### MissingToken::__construct
> TODO: add doc comment

```php
public function __construct ( int $kind, int $fullStart )
```
### MissingToken::jsonSerialize
> TODO: add doc comment

```php
public function jsonSerialize ( )
```
## SkippedToken
### SkippedToken::__construct
> TODO: add doc comment

```php
public function __construct ( Token $token )
```
### SkippedToken::jsonSerialize
> TODO: add doc comment

```php
public function jsonSerialize ( )
```
## Node types
> TODO: complete documentation - in addition to the helper methods on the Node base class,
every Node object has properties specific to the Node type. Browse `src/Node/` to explore these properties.