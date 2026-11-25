<?php
// Clone with readonly properties (PHP 8.5)
class Book {
    public function __construct(
        public readonly string $title,
        public readonly string $author
    ) {}
}

$book1 = new Book("1984", "George Orwell");
$book2 = clone($book1, ["title" => "Animal Farm"]);
