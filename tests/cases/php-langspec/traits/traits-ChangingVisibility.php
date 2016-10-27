/* Auto-generated from php/php-langspec tests */

trait T2a
{
	function f()
	{
		echo "Inside " . __TRAIT__ . "\n";
		echo "Inside " . __CLASS__ . "\n";
		echo "Inside " . __METHOD__ . "\n";
	}
}

trait T2b
{
//	function f($p1, $p2) // signatures not factored in when looking for name clashes
	function f()
	{
		echo "Inside " . __TRAIT__ . "\n";
		echo "Inside " . __CLASS__ . "\n";
		echo "Inside " . __METHOD__ . "\n";
	}
}

class C2Base
{
	public function f() { echo "Inside " . __METHOD__ . "\n"; }
}

class C2Derived extends C2Base
{
//	use T2a; use T2b;	// equivalent to use T2a, T2b;
//	use T2a, T2b;		// clash between two names f REGARDLESS of argument lists
	use T2a, T2b
	{	// with both below excepted, went to base, bypassing both traits!!
		T2a::f insteadof T2b;
//		T2b::f insteadof T2a;

		T2b::f as g;	// allow otherwise hidden T2B::f to be seen through alias g
		T2a::f as h;	// allow T2a::f to also be seen through alias h
						// don't need qualifier prefix if f is unambiguous
	}

//	public function f() { echo "Inside " . __METHOD__ . "\n"; }
}

$c2 = new C2Derived;

echo "-------\n";
$c2->f();		// call T2a::f

echo "-------\n";
$c2->g();		// call T2b::f via its alias g

echo "-------\n";
$c2->h();		// call T2a::f via its alias h

// confirmed that lookup starts with current class, then trait(s), then base classes

