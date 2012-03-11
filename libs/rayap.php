<?php

/** 
 * @copyright	: Rio Astamal <me@rioastamal.net>
 * @version		: 1.0
 * @homepage	: https://github.com/astasoft/rayap
 * @license		: 
 * 
 *      Redistribution and use in source and binary forms, with or without
 *      modification, are permitted provided that the following conditions are
 *      met:
 *      
 *      * Redistributions of source code must retain the above copyright
 *        notice, this list of conditions and the following disclaimer.
 *      * Redistributions in binary form must reproduce the above
 *        copyright notice, this list of conditions and the following disclaimer
 *        in the documentation and/or other materials provided with the
 *        distribution.
 *      * Neither the name of the  nor the names of its
 *        contributors may be used to endorse or promote products derived from
 *        this software without specific prior written permission.
 *      
 *      THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 *      "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 *      LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 *      A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 *      OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *      SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 *      LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 *      DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 *      THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *      (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 *      OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Object Oriented fashion for wrapping most of the curl function in PHP
 * 
 * Here is example of fetching single page.
 * 
 * // instance the class
 * $rayap = new Rayap();
 * // fetch a page
 * $result = $rayap->get_page('http://www.google.com/');
 * // close the connection
 * $rayap->close();
 * // print result
 * echo $result;
 *
 */
class Rayap {
	/**
	 * Public property
	 */
	public $curl = NULL;
	public $URL = '';
	public $method = 'GET';
	public $post_data = '';
	public $use_cookie = FALSE;
	public $cookie_file = '';
	public $show_header = FALSE;
	public $custom_header = array();
	public $custom_referer = '';
	public $always_follow = TRUE;
	public $user_agent = 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.4) Gecko/2008102920 Firefox/3.0.4';
	public $ssl_verify = FALSE;
	public $custom_certificate = FALSE;
	public $timeout = 30;
	public $errors = '';
	public $_custom_options = array();
	public $raw_post = FALSE;
	
	/**
	 * Class constructor
	 */
	public function Rayap($h='', $m='GET') {
		// -------- prepare everything -------------- //
		
		// make a curl object
		$this->curl = curl_init();
		
		// don't print ouput directly
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);

		// Host Target
		$this->URL = $h;
		$this->method = $m;
	}
	
	
	public function set_postdata($data, $multipart=FALSE) {
		if ($this->raw_post) {
			$this->post_data = $data;
			return ;
		}
		if (is_array($data)) {
			// multipart is used when we want to post as multipart/form-data request
			if ($multipart == TRUE) {
				$this->post_data = $data;
				return ;
			}
			$this->_set_postdata_array($data);
		} else {
			$this->post_data = urlencode($data);
		}
	}
	
	public function _set_postdata_array($data) {
		if ($data) {
			foreach ($data as $key => $val) {
				$this->post_data .= '&'.urlencode($key).'='.urlencode($val);
			}
			$this->post_data = substr($this->post_data, 1, strlen($this->post_data));
		}
	}
	
	public function _prepare() {
		// set target URL
		curl_setopt($this->curl, CURLOPT_URL, $this->URL);
		
		// time-out request in seconds
		curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
		
		// set method
		if ($this->method == 'GET') {
			curl_setopt($this->curl, CURLOPT_HTTPGET, TRUE);
		} elseif ($this->method == 'POST') {
			curl_setopt($this->curl, CURLOPT_POST, TRUE);
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->post_data);
		}
		// other than GET or POST use opt to build your own data
		
		// follow redirection?
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, $this->always_follow);

		// show header?
		curl_setopt($this->curl, CURLOPT_HEADER, $this->show_header);
		
		// send custom header
		if ($this->custom_header) {
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->custom_header);
		}
		
		// custom referer?
		if ($this->custom_referer) {
			curl_setopt($this->curl, CURLOPT_REFERER, $this->custom_referer);
		}
		
		// use cookie?
		if ($this->use_cookie == TRUE) {
			// make it relative to $HOME
			
			curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookie_file);
			// read cookie
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookie_file);
		}
		
		// user agent
		curl_setopt($this->curl, CURLOPT_USERAGENT, $this->user_agent);
		
		// use ssl?
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, $this->ssl_verify);
		if ($this->custom_certificate == TRUE) {
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);  
			// only check the existence of common name in SSL 
			curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, TRUE);  
		}
		
		// trigger the custom option
		foreach ($this->_custom_options as $curl_opt => $val) {
			curl_setopt($this->curl, $curl_opt, $val);
		}
	}
	
	public function opt($opt, $val) {
		$this->_custom_options[$opt] = $val;
	}
	
	public function reset_postdata() {
		$this->post_data = '';
	}
	
	public function get_page($url) {
		$this->method = 'GET';
		$this->URL = $url;
		return $this->exec();
	}
	
	public function exec() {
		// assign all values before executing
		$this->_prepare();
	
		$ret = curl_exec($this->curl);
		if (curl_errno($this->curl) != 0) {
			$this->errors .= curl_error($this->curl) . "\n";
		}
		
		return $ret;
	}
	
	public function dump_status() {
		return curl_getinfo($this->curl);
	}
	
	public function close() {
		curl_close($this->curl);
	}
}
