# How to Contribute
For contribute at this bot, open an Issue or a Pull request.

### Rules to Code

* For indentation, use Tabs instead Spaces (1 Tab = 4 Spaces)
* If possible, prefer the operator instead of the function (_i.e._ use `[]=` instead of `array_push()`)
* Use of `empty($var) === FALSE` instead of `isset($var)` if you want check if a variable is set, because `empty()` executes more controls
* Use `FALSE` and `TRUE` instead of `false` and `true`
* Comment all the function with extreme precision
* Use the following example to comment a function
	```
		/**
		* <description_of_the_function>
		*
		* @param <type> <name_of_the_variable> <description_of_the_variable>
		* @param <type> <name_of_the_variable> <description_of_the_variable>
		* ...
		*
		* @return <type> <description_of_the_result>
		*/
	```
* For declare a variable, use the symbol of the type instead of the constructor (_i.e._ use `[]` instead of `array()`)
* Into the functions, insert a space after the comma (_i.e._ use `func($var1, $var2)` instead of `func($var1,$var2)`)
* Specify the type of the parameter of a function (_i.e._ use `func($var1, int $var2)` instead of `func($var1, $var2)`)

### Util links

* [How to work with branches](https://www.robinwieruch.de/git-team-workflow)
