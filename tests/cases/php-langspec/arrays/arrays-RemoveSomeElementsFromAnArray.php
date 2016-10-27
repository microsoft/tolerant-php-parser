/* Auto-generated from php/php-langspec tests */

$v = array("red" => TRUE, 123, 9 => 34e12, "Hello");
var_dump($v);
unset($v[0], $v["red"]);
var_dump($v);
