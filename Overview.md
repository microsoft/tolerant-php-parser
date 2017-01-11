# Overview

At a high level, the parser accepts source code as an input, and
produces a syntax tree as an output.

If you're familiar with Roslyn and TypeScript, many of the concepts presented here will be familiar
(albeit adapted, to account for the unique runtime characteristics of PHP.)

## Syntax Tree
A syntax tree is literally a tree data structure, where non-terminal structural 
elements parent other elements. Each syntax tree is made up of Nodes (represented by circles), 
Tokens (represented by squares), and trivia (not represented, below, but attached to each Token).

![image](https://cloud.githubusercontent.com/assets/762848/19092929/e10e60aa-8a3d-11e6-8b90-51eabe5d1d8e.png)

Syntax trees have two key attributes.
1. The first attribute is that Syntax trees hold all the source information in full fidelity. 
This means that the syntax tree contains every piece of information 
found in the source text, every grammatical construct, every lexical 
token, and everything else in between including whitespace, comments, 
and preprocessor directives. For example, each literal mentioned in 
the source is represented exactly as it was typed. The syntax trees 
also represent errors in source code when the program is incomplete 
or malformed, by representing skipped or missing tokens in the syntax tree.

2. This enables the second attribute of syntax trees. A syntax tree obtained 
from the parser is completely round-trippable back to the text it was parsed 
from. From any syntax node, it is possible to get the text representation of 
the sub-tree rooted at that node. This means that syntax trees can be used 
as a way to construct and edit source text. By creating a tree you have by 
implication created the equivalent text, and by editing a syntax tree, 
making a new tree out of changes to an existing tree, you have effectively 
edited the text.

The syntax tree is composed of Nodes (represented by circles), 
Tokens (represented by squares), and Trivia (not represented directly, but attached to 
individual Tokens)



### Nodes
Syntax nodes are one of the primary elements of syntax trees. These nodes represent 
syntactic constructs such as declarations, statements, clauses, and expressions. 
Each category of syntax nodes is represented by a separate class derived from SyntaxNode. 
The set of node classes is not extensible.

All syntax nodes are non-terminal nodes in the syntax tree, which means they always have 
other nodes and tokens as children. As a child of another node, each node has a parent node
 that can be accessed through the Parent property. Because nodes and trees are immutable, 
 the parent of a node never changes. The root of the tree has a null parent.

Each node has a ChildNodes method, which returns a list of child nodes in sequential order 
based on its position in the source text. This list does not contain tokens. Each node also
has a collection of Descendant methods - such as DescendantNodes, DescendantTokens, or 
DescendantTrivia - that represent a list of all the nodes, tokens, or trivia that exist in 
the sub-tree rooted by that node.

In addition, each syntax node subclass exposes all the same children through 
properties. For example, a BinaryExpressionSyntax node class has three additional properties 
specific to binary operators: Left, OperatorToken, and Right.

Some syntax nodes have optional children. For example, an IfStatementSyntax has an optional 
ElseClauseSyntax. If the child is not present, the property returns null.

### Tokens
Syntax tokens are the terminals of the language grammar, representing the smallest syntactic 
fragments of the code. They are never parents of other nodes or tokens. Syntax tokens 
consist of keywords, identifiers, literals, and punctuation.

For efficiency purposes, unlike syntax nodes, there is only one structure for all 
kinds of tokens with a mix of properties that have meaning depending on the kind 
of token that is being represented.

### Trivia
Syntax trivia represent the parts of the source text that are largely insignificant for 
normal understanding of the code, such as whitespace, comments, and preprocessor directives.
Because trivia are not part of the normal language syntax and can appear anywhere between 
any two tokens, they are not included in the syntax tree as a child of a node. Yet, because 
they are important when implementing a feature like refactoring and to maintain full 
fidelity with the source text, they do exist as part of the syntax tree.

You can access trivia by inspecting a token's LeadingTrivia. 
When source text is parsed, sequences of trivia are associated with tokens. 

### Kinds
Each node, token, or trivia has a RawKind property (represented by a numeric literal), 
that identifies the exact syntax element represented.

The RawKind property allows for easy disambiguation of syntax node types that share the 
same node class. For tokens and trivia, this property is the only way to distinguish 
one type of element from another.

### Errors
Even when the source text contains syntax errors, a full syntax tree that is round-trippable
to the source is exposed. When the parser encounters code that does not conform to the 
defined syntax of the language, it uses one of two techniques to create a syntax tree.

First, if the parser expects a particular kind of token, but does not find it, it may 
insert a missing token into the syntax tree in the location that the token was expected. 
A missing token represents the actual token that was expected, but it has an empty span.

Second, the parser may skip tokens until it finds one where it can continue parsing. 
In this case, the skipped tokens that were skipped are attached as a trivia node with 
the kind SkippedTokens.

Note that the parser produces trees in a tolerant fashion, and will not produce errors for
all incorrect constructs (e.g. including a non-constant expression as the default value of
a method parameter). Instead, it attaches these errors on a post-parse walk of the tree.

### Positional Information
Each node, token, or trivia knows its position within the source text and the number of 
characters it consists of. A text position is represented as a 32-bit integer, which is 
a zero-based Unicode character index. A TextSpan object is the beginning position and a 
count of characters, both represented as integers. If TextSpan has a zero length, it refers
to a location between two characters.

The position refers to the absolute position within the text, but a helper function is available
if you require Line/Column information. 

## Next Steps
Check out the [Documentation](GettingStarted.md) section for more information on how consume
the parser, or the [How It Works](HowItWorks.md) section if you want to dive deeper into the implementation.