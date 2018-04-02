<?php
/**
 * Router
 * Create and collect routes
 *
 * @package PsrRouter
 */
namespace PsrRouter;

use PsrRouter\Exception\RouterException,
    Psr\Http\Message\RequestInterface as Request,
    Psr\Http\Message\ResponseInterface as Response;

class PsrRouter {

	/**
	 * Methods supported by the router
	 * @var Array
	 */
	public static $_METHODS = array(
		'GET',
		'POST',
		'PUT',
		'DELETE',
		'OPTIONS',
		'PATCH'
	);

	/**
	 * Route collector
	 * @var Array
	 */
	private $routes = array();

	/**
	 * Parameters
	 * Strict routing (/-ending sensitive)
	 * Case sensitive
	 * @var Array
	 */
	private $params = array();

	/**
	 * Array of callables for HTTP error handling
	 * Implemented : 400, 404, 405
	 * @var Array
	 */
	private $httpErrors = array();

	/**
	 * Constructor of the router, the routes collector
	 * @param String $strictRouting
	 * @param bool $caseSensitive
	 */
	public function __construct(bool $strictRouting = false, bool $caseSensitive = false) {
		$this->httpErrors = array(
			'400' => function(Request $request, Response $response) {

				$body = $response->getBody();
				$body->write("<html>\n");
				$body->write("<head><title>400 Bad Request</title></head>\n<body>\n");
				$body->write("<h1>Bad Request</h1>\n");
				$body->write("<p>Your browser sent a request that this server could not understand (".htmlentities($request->getMethod()).").</p>\n");
				$body->write("</body>\n</html>\n");

				return $response->withStatus(400)->withBody($body);

			},
			'404' => function(Request $request, Response $response) {

				$body = $response->getBody();
				$body->write("<html>\n");
				$body->write("<head><title>404 Not Found</title></head>\n<body>\n");
				$body->write("<h1>Not found</h1>\n");
				$body->write("<p>The requested URL {$request->getRequestTarget()} was not found on this server.</p>\n");
				$body->write("</body>\n</html>\n");

				return $response->withStatus(404)->withBody($body);

			},
			'405' => function(Request $request, Response $response) {

				$body = $response->getBody();
				$body->write("<html>\n");
				$body->write("<head><title>405 Not Allowed</title></head>\n<body>\n");
				$body->write("<h1>Method Not Allowed</h1>\n");
				$body->write("<p>The requested method {$request->getMethod()} is not allowed for this URL ({$request->getRequestTarget()}).</p>\n");
				$body->write("</body>\n</html>\n");

				return $response->withStatus(405)->withBody($body);

			}
		);

		$this->setParam('STRICT_ROUTING', $strictRouting);
		$this->setParam('CASE_SENSITIVE', $caseSensitive);
	}

	/**
	 * Set a parameter for the router
	 * It can be a closure for handling a request error or a ReactRouter::params
	 * @param String $key
	 * @param mixed $value
	 * @return ReactRouter
	 *
	 * @throws RouterException
	 */
	public function setParam(String $key, $value) : self {
		if($key == '400' || $key == '404' || $key == '405') {
			if(!is_callable($value))
				throw new RouterException('Closure or callback function needed in second parameter in order to implement an HTTP error.');

			$this->httpErrors[$key] = $value;

		} elseif($key == 'STRICT_ROUTING' && is_bool($value)) {
			$this->params[$key] = $value;

		} elseif($key == 'CASE_SENSITIVE' && is_bool($value)) {
			$this->params[$key] = $value;

		} else throw new RouterException('STRICT_ROUTING or CASE_SENSITIVE keys are needed with string or bool in second parameter');

		return $this;
	}

	/**
	 * Get a parameter
	 * @param String
	 * @return mixed
	 */
	public function getParam(String $key) {

		return $this->params[$key] ?? $this->httpErrors[$key] ?? null;

	}

	/**
	 * Return all defined routes with their method
	 * @return Array
	 **/
	public function getRoutes() : Array {

		$return = array();
		foreach($this->routes as $route) {
			array_push($return, [$route->getPath(), $route->getMethod()]);
		}
		return $return;

	}

	/**
	 * Route collector
	 * @param String $method
	 * @param String $path
	 * @param Callable $callback
	 *
	 * @throws RouterException if path + method already defined
	 * @return Route
	 */
	private function collectRoute(String $method, String $path, Callable $callback) : Route {

		if(in_array([$path, $method], $this->getRoutes()))
			throw new RouterException('Route already defined.');

		$route = new Route($method, $path, $callback);
		array_push($this->routes, $route);
		return $route;

	}

	/**
	 * Create and collect a new Route with GET request
	 * @param String $path
	 * @param mixed $callback callback or closure function
	 * @return Route
	 */
	public function get(String $path, Callable $callback) : Route {

		return $this->collectRoute('GET', $path, $callback);

	}

	/**
	 * Create and collect a new Route with POST request
	 * @param String $path
	 * @param mixed $callback callback or closure function
	 * @return Route
	 */
	public function post(String $path, Callable $callback) : Route {

		return $this->collectRoute('POST', $path, $callback);

	}

	/**
	 * Create and collect a new Route with PUT request
	 * @param String $path
	 * @param mixed $callback callback or closure function
	 * @return Route
	 */
	public function put(String $path, Callable $callback) : Route {

		return $this->collectRoute('PUT', $path, $callback);

	}

	/**
	 * Create and collect a new Route with DELETE request
	 * @param String $path
	 * @param mixed $callback callback or closure function
	 * @return Route
	 */
	public function delete(String $path, Callable $callback) : Route {

		return $this->collectRoute('DELETE', $path, $callback);

	}

	/**
	 * Create and collect a new Route with OPTIONS request
	 * @param String $path
	 * @param mixed $callback callback or closure function
	 * @return Route
	 */
	public function options(String $path, Callable $callback) : Route {

		return $this->collectRoute('OPTIONS', $path, $callback);

	}

	/**
	 * Create and collect a new Route with PATCH request
	 * @param String $path
	 * @param mixed $callback callback or closure function
	 * @return Route
	 */
	public function patch(String $path, Callable $callback) : Route {

		return $this->collectRoute('PATCH', $path, $callback);

	}
	
	/**
	 * Run the router
	 * @param RequestInterface  : Handling HTTP request from client
	 * @param ResponseInterface : Handling HTTP response
	 * @return ResponseInterface
	 */
	public function run(Request $request, Response $response) : Response {

		$strict = $this->getParam('STRICT_ROUTING');
		$case = $this->getParam('CASE_SENSITIVE');
		$method = $request->getMethod();
		$url = $request->getUri()->getPath();

		//Matching
		foreach($this->routes as $route) {
			if($route->match($url, $strict, $case) && $method == $route->getMethod()) {
				return $route->call($request, $response);
			} elseif($route->match($url, $strict, $case) && $method == 'HEAD') {
				$body = $response->getBody();
				$body->close();
				$response = $response->withBody($body);
				return $response;
			}
		}

		//Error
		$methods = array();
		foreach($this->routes as $route) {
			array_push($methods, $route->getMethod());
			if($route->match($url, $strict, $case) && $method != $route->getMethod() && in_array($method, self::$_METHODS))
				return call_user_func($this->httpErrors['405'], $request, $response);

			elseif($route->match($url, $strict, $case) && $method != $route->getMethod() && !in_array($method, self::$_METHODS))
				return call_user_func($this->httpErrors['400'], $request, $response);
		}

		if(!in_array($method, $methods) && !in_array($method, self::$_METHODS))
			return call_user_func($this->httpErrors['400'], $request, $response);
		else
			return call_user_func($this->httpErrors['404'], $request, $response);
		
	}
}
