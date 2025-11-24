<?php
require "controllers.php";
function nextPathSegment(): string
{
  static $i = 1;
  $SEGMENTS = explode('/', $_SERVER['REQUEST_URI']);
  if (!isset($SEGMENTS[$i])) {
    http_response_code(404);
    exit;
  }
  $i++;
  return $SEGMENTS[$i - 1];
}
const adminUser = 'root';
const adminPw = '1234';
function auth()
{
  $user = $_SERVER['PHP_AUTH_USER'] ?? null;
  $pw = $_SERVER['PHP_AUTH_PW'] ?? null;
  if (!$user || !$pw || $user !== adminUser || $pw !== adminPw) {
    http_response_code(401);
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic');
    exit;
  }
}
enum Method: int
{
  case GET = 1 << 0;
  case POST = 1 << 1;
  case PUT = 1 << 2;
  case DELETE = 1 << 3;
}
const ROUTES = [
  '',
  'home',
  'article',
  'admin',
  'new',
  'edit',
  'delete',
];
const ALLOWED_METHODS = [
  Method::GET->value,
  Method::GET->value, //home
  Method::GET->value | Method::POST->value, //article
  Method::GET->value, //admin
  Method::GET->value, //new
  Method::GET->value | Method::POST->value, //edit
  Method::POST->value, // delete
];
const NEEDS_AUTH = [
  0,
  0, //home
  Method::POST->value, //article
  Method::GET->value, //admin
  Method::GET->value, //new
  Method::GET->value | Method::POST->value, //edit
  Method::POST->value, //delete
];
$method = match ($_SERVER['REQUEST_METHOD']) {
  'GET' => Method::GET,
  'POST' => Method::POST,
  'PUT' => Method::PUT,
  'DELETE' => Method::DELETE,
  default => null,
};
if (is_null($method)) {
  http_response_code(405);
  exit;
}
$route = nextPathSegment();
$route_index = array_find_key(ROUTES, function (string $r) use ($route) {
  return $r === $route;
});
if (is_null($route_index)) {
  http_response_code(404);
  exit;
}
$isAllowed = $method->value & ALLOWED_METHODS[$route_index];
if (!$isAllowed) {
  http_response_code(405);
  exit;
}
$needsAuth = $method->value & NEEDS_AUTH[$route_index];
if ($needsAuth) {
  auth();
}
switch ($route) {
  case '':
  case 'home':
    getHome();
    break;
  case 'article':
    switch ($method) {
      case Method::GET:
        $id = nextPathSegment();
        getArticle($id);
        break;
      case Method::POST:
        ['title' => $title, 'date' => $date, 'content' => $content] = $_POST;
        if (is_null($title) || is_null($date) || is_null($content)) {
          http_response_code(400);
          exit;
        }
        postArticle($title, $date, $content);
        break;
    }
    break;
  case 'admin':
    getAdmin();
    break;
  case 'new':
    getNew();
    break;
  case 'edit':
    switch ($method) {
      case Method::GET:
        $id = nextPathSegment();
        getEdit($id);
        break;
      case Method::POST:
        ['id' => $id, 'title' => $title, 'date' => $date, 'content' => $content] = $_POST;
        if (!is_numeric($id) || is_null($title) || is_null($date) || is_null($content)) {
          http_response_code(400);
          exit;
        }
        postEdit($id, $title, $date, $content);
        break;
    }
    break;
  case 'delete':
    $id = nextPathSegment();
    postDelete($id);
    break;
  default:
    http_response_code(500);
    exit;
}
