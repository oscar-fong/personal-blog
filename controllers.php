<?php
# Utilities
$query = 'create table IF NOT EXISTS articles (id INTEGER PRIMARY KEY AUTO_INCREMENT, title VARCHAR(50), date DATE, content TEXT)';
$db =  new PDO('mysql::host=localhost;dbname=personal_blog', 'root', '1234');
$db->exec($query);

function articleList(): array
{
  global $db;
  $query = 'SELECT id, title, date, content FROM articles';
  $st = $db->prepare($query);
  $st->execute();
  $result = $st->fetchAll(PDO::FETCH_ASSOC);
  return $result;
}

function articleById(int $id): ?array
{
  global $db;
  $query = 'SELECT id, title, date, content FROM articles WHERE id = ?';
  $st = $db->prepare($query);
  $st->execute([$id]);
  $result = $st->fetch(PDO::FETCH_ASSOC);
  return $result ? $result : null;
}
# Controllers
function getHome()
{
  $template = file_get_contents('templates/home.html');
  $articles = articleList();
  $articleListItems = '';
  foreach ($articles as $article) {
    ['id' => $id, 'title' => $title, 'date' => $date] = $article;
    $articleListItems .= "<li><a href='article/$id'>$title    $date</a></li>";
  }
  printf($template, $articleListItems);
}
function getArticle(string $id)
{
  if (!is_numeric($id)) {
    http_response_code(400);
    exit();
  }
  $article = articleById($id);
  if (!is_null($article)) {
    ['title' => $title, 'date' => $date, 'content' => $content] = $article;
    $title = ucwords($title);
    $template = file_get_contents('templates/article.html');
    printf($template, $title, $title, $date, $content);
  } else {
    http_response_code(404);
    exit;
  }
}
function postArticle($title, $date, $content)
{
  global $db;
  $query = 'insert into articles (title, date, content) values (?, ?, ?)';
  $st = $db->prepare($query);
  $st->execute([$title, $date, $content]);
  header('Location: admin', true, 303);
}
function putArticle($id, $title, $date, $content) {}
function postDelete($id)
{
  if (!is_numeric($id)) {
    http_response_code(400);
    exit();
  }
  global $db;
  $query = 'delete from articles where id = ?';
  $st = $db->prepare($query);
  $st->execute([$id]);
  header('Location: /admin', true, 303);
}
function getNew()
{
  $template = file_get_contents('templates/new.html');
  echo $template;
}
function getEdit($id)
{
  if (!is_numeric($id)) {
    http_response_code(400);
    exit();
  }
  $article = articleById($id);
  if (!is_null($article)) {
    ['title' => $title, 'date' => $date, 'content' => $content] = $article;
    $template = file_get_contents('templates/edit.html');
    printf($template, $id, $title, $date, $content);
  } else {
    http_response_code(404);
    exit;
  }
}
function postEdit($id, $title, $date, $content)
{
  global $db;
  $query = 'update articles set title = ?, date = ?, content = ? where id = ?';
  $st = $db->prepare($query);
  $st->execute([$title, $date, $content, $id]);
  header('Location: /admin', true, 303);
}

function getAdmin()
{
  $template = file_get_contents('templates/admin.html');
  $articles = articleList();
  $articleListItems = '';
  foreach ($articles as $article) {
    ['id' => $id, 'title' => $title, 'date' => $date] = $article;
    $delete = "<form style=\"display:inline\" method=\"post\" action=\"delete/$id\"><input type=\"submit\" value=\"Delete\" style=\"background:none;border:none;padding:0;margin:0;color:blue;text-decoration:underline;cursor:pointer;font:inherit\"></form>";
    $articleListItems .= "<li><a href=\"article/$id\">$title</a> <a href=\"edit/$id\">Edit</a> $delete</li>";
  }
  printf($template, $articleListItems);
}
