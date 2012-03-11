<?php

/**
 * Class to emulate some javascript functions.
 */
class JS {
	/**
	 * Method that output the javascript's document.write() syntax.
	 *
	 * @author Rio Astamal <me@rioastmal.net>
	 *
	 * @param string $str - String to print
	 * @return void
	 */
	public static function write($str) {
		printf("document.write('%s');\n", str_replace("'", "\'", $str));
	}
}
