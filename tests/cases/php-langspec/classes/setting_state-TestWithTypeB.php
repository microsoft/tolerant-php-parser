/* Auto-generated from php/php-langspec tests */

$b = new B(10);
$v = var_export($b, TRUE);
var_dump($v);

$r = eval('$z = ' . $v . ";");
var_dump($z);

