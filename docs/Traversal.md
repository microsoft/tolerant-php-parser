
# AST Traversal

All Nodes implement the `IteratorAggregate` interface, which means their immediate children can be directly traversed with `foreach`:

```php
foreach ($node as $key => $child) {
    var_dump($key)
    var_dump($child);
}
```

`$key` is set to the child name (e.g. `parameters`).
Multiple child nodes may have the same key.

The Iterator that is returned to `foreach` from `$node->getIterator()` implements the `RecursiveIterator` interface.
To traverse all descendant nodes, you need to "flatten" it with PHP's built-in `RecursiveIteratorIterator`:

```php
$it = new \RecursiveIteratorIterator($node, \RecursiveIteratorIterator::SELF_FIRST);
foreach ($it as $node) {
    var_dump($node);
}
```

The code above will walk all nodes and tokens depth-first.
Passing `RecursiveIteratorIterator::CHILD_FIRST` would traverse breadth-first, while `RecursiveIteratorIterator::LEAVES_ONLY` (the default) would only traverse terminal Tokens. 

## Exclude Tokens

To exclude terminal Tokens and only traverse Nodes, use PHP's built-in `ParentIterator`:

```php
$nodes = new \ParentIterator(new \RecursiveIteratorIterator($node, \RecursiveIteratorIterator::SELF_FIRST));
```

## Skipping child traversal

To skip traversal of certain Nodes, use PHP's `RecursiveCallbackIterator`.
Naive example of traversing all nodes in the current scope:

```php
// Find all nodes in the current scope
$nodesInScopeReIt = new \RecursiveCallbackFilterIterator($node, function ($current, $key, Iterator $iterator) {
    // Don't traverse into function nodes, they form a differnt scope
    return !($current instanceof Node\Expression\FunctionDeclaration);
});
// Convert the RecursiveIterator to a flat Iterator
$it = new \RecursiveIteratorIterator($nodesInScope, \RecursiveIteratorIterator::SELF_FIRST);
```

## Filtering

Building on that example, to get all variables in that scope us a non-recursive `CallbackFilterIterator`:

```php
// Filter out all variables
$vars = new \CallbackFilterIterator($it, function ($current, $key, $iterator) {
    return $current instanceof Node\Expression\Variable && $current->name instanceof Token;
});

foreach ($vars as $var) {
    echo $var->name . PHP_EOL;
}
```

## Converting to an array

You can convert your iterator to a flat array with

```php
$arr = iterator_to_array($it, true);
```

The `true` ensures that the array is indexed numerically and not by Iterator keys (otherwise Nodes later Nodes with the same key will override previous).
