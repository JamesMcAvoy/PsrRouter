# PsrRouter
Basic router developped with PSR interfaces.<br />
Other libraries wich support PSR interfaces are needed in order to use the router, $request and $response parameters.<br />
This router supports GET, POST, PUT, DELETE, OPTIONS, PATCH methods and can handle 400, 404 and 405 errors.<br />

### Installation
```Bash
composer require lordarryn/psrrouter
```

### Example :
For example, I use react/http server and the router. I created an other file where I wrote the routes and I include this file in the main program.<br />
routing.php :
```
<?php
//This is a basic to-do list with PSR interfaces
use PsrRouter\PsrRouter as Router;

$app = new Router();

$app->get('/', function($req, $res) {

	$body = $res->getBody();
	$body->write('<meta http-equiv="content-type" content="text/html; charset=UTF-8">'.PHP_EOL);
	$body->write('<form action="/add" method="post"><input name="add"><input name="post" value="Add something" type="submit"></form>'.PHP_EOL);

	if(!empty($req->getCookieParams()['todolist'])) {
		$values = explode('-', $req->getCookieParams()['todolist']);

		foreach($values as $key => $todo) {
			$body->write('<br /><a href="/del-'.$key.'">X</a> '.$todo.PHP_EOL);
		}
	}

	return $res->withBody($body);

});

//POST request : add a to-do item
$app->post('/add', function($req, $res) {

	if(!empty($req->getParsedBody()['add'])) {
		$add = htmlentities($req->getParsedBody()['add']);
		$values = !empty($req->getCookieParams()['todolist']) ? explode('-', $req->getCookieParams()['todolist']) : array();
		$values[] = $add;
		$cookieHeader = 'todolist='.implode('-', $values);
		return $res->withStatus(302)->withHeader('Location', '/')->withHeader('Set-Cookie', $cookieHeader);
	}

	return $res->withStatus(302)->withHeader('Location', '/');

});

//GET request with URL parameter : delete a to-do item
$app->get('/del-{id}', function($req, $res, $id) {

	if(isset($req->getCookieParams()['todolist'])) {
		$values = explode('-', $req->getCookieParams()['todolist']);

		if(isset($values[$id['id']]))
			unset($values[$id['id']]);

		$cookieHeader = 'todolist='.implode('-', $values);
		return $res->withStatus(302)->withHeader('Location', '/')->withHeader('Set-Cookie', $cookieHeader);
	}

	return $res->withStatus(302)->withHeader('Location', '/');

})->regex('/[0-9]{1,3}/');
?>
```
<br />
server.php :
```
<?php

//react/http server
use React\EventLoop\Factory,
	React\Http\Server,
	React\Http\Response,
	React\Socket\Server as SocketServer,
	Psr\Http\Message\ServerRequestInterface,
	Psr\Http\Message\ResponseInterface;

require __DIR__.'/vendor/autoload.php';

//include routing page
require 'routing.php';

$loop = Factory::create();

//use the Router variable
$server = new Server(function(ServerRequestInterface $request) use(&$app) {

	$response = new Response(
		200,
		array(
			'Content-Type' => 'text/html',
			'X-Powered-By' => 'PHP '.phpversion()
		)
	);

	//match HTTP request
	return $app->run($request, $response);

});

$socket = new SocketServer(8080, $loop);
$server->listen($socket);

$loop->run();
?>
```