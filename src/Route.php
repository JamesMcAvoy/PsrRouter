<?php
/**
 * Instances of routes
 *
 * @package PsrRouter
 */
namespace PsrRouter;

use Psr\Http\Message\RequestInterface as Request,
	Psr\Http\Message\ResponseInterface as Response;

class Route {

	/**
	 * Request method
	 * @var String
	 */
	private $method;


	/**
	 * Request target
	 * @var String
	 */
	private $path;


	/**
	 * Callback function
	 * @var String
	 */
	private $callable;


	/**
	 * Parameters (matches) used for the callback
	 * @var Array
	 */
	private $slugs = array();


	/**
	 * Regex for the parameters => regex()
	 * Stocked in the same order as the parameters in the URL
	 * @var Array
	 */
	private $regex = array();


	/**
	 * Constructor
	 * @param String $method HTTP method
	 * @param String $path
	 * @param String $callback function
	 */
	public function __construct(String $method, String $path, Callable $callback) {

		$this->method = $method;
		$this->path = $path;
		$this->callable = $callback;

	}


	/**
	 * Return the request method of the route
	 * @param void
	 * @return String
	 */
	public function getMethod() : String {

		return $this->method;

	}


	/**
	 * Return the path of the route
	 * @param void
	 * @return String
	 */
	public function getPath() : String {

		return $this->path;

	}


	/**
	 * Call the callback function of the route
	 * @param RequestInterface
	 * @param ResponseInterface
	 * @return mixed
	 */
	public function call(Request $request, Response $response) : Response {

		return call_user_func($this->callable, $request, $response, $this->slugs);

	}


	/**
	 * Add arguments for the parameters in the URL if used
	 * @example $app->get('/{slug}', function($req, $res){ return $res })->regex('/[a-z]{1,3}/');
	 * @param String $regex
	 * @return Route
	 */
	public function regex(String $regex) : self {

		array_push($this->regex, $regex);
		return $this;
		
	}


	/**
	 * Parse a regex  : delete if needed the first and last character and add parenthesis
	 * @param String $reg
	 * @return String
	 */
	private function preg(String $reg) : String {

		if($reg['0'] == $reg[strlen($reg)-1] && ($reg['0'] == '/' || $reg['0'] == '#'))
			$reg = trim($reg, $reg['0']);
		if($reg['0'] != '(')
			$reg = '('.$reg;
		if($reg[strlen($reg)-1] != ')')
			$reg .= ')';
		return $reg;

	}


	/**
	 * Match the parameters in the URL
	 * @param String $url
	 * @param bool $strict Strict routing
	 * @param bool $case Case sensitive
	 * @return bool
	 *
	 * @throws RouterException
	 */
	public function match(String $url, bool $strict, bool $case) {

		//If no strict routing, removing last / of $url if $this->path does not have it
		if(!$strict && $this->path[strlen($this->path) - 1] != '/')
			$url = rtrim($url, '/');

		if($case) 	$case = '';
		else 		$case = 'i';

		//Parameters name
		preg_match_all('/{([\w]+)}/', $this->path, $params);
		$params = $params['1'];

		//Replace default regex and brackets
		$path = preg_replace('/{([\w]+)}/', '([^/{}]+)', $this->path);
		$path = str_replace('\{', '{', $path);
		$path = str_replace('\}', '}', $path);

		//Add the regex if needed (regex array and params array not empty)
		$areRegex = !empty($this->regex) || !empty($params);
		if($areRegex) {
			//Explode the path
			$arrayPath = explode('([^/{}]+)', $path);
			$c = count($arrayPath);
			$path = '';

			//Between each '([^/{}]+)' add the path and the regex
			for($i = 0; $i<$c; $i++) {
				$path .= $arrayPath[$i];

				//The end of the path exploded is ""
				if($c-1 != $i) {
					if(isset($this->regex[$i]))
						$path .= $this->preg($this->regex[$i]);
					elseif(empty($this->regex))
						$path .= '([^/{}]+)';
					else
						$path .= $this->preg($this->regex[0]);
				}
			}
		}

		//Create global regex to match url
		$reg = "#^$path$#$case";

		if(!preg_match($reg, $url, $matches))
			return false;

		//Create an array with the parameters name and the value
		$slugs = array();
		if($areRegex) {
			array_shift($matches);
			$c = count($params);

			for($i = 0; $i<$c; $i++) {
				$slugs[$params[$i]] = $matches[$i];
			}
		}

		$this->slugs = $slugs;

		return true;

	}
	
}