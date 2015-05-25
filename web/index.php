<?php

require('../vendor/autoload.php');

$app = new Silex\Application();
$app['debug'] = false;
$app['twig.path'] = array(__DIR__ . '/../app/views/');

$app->register(new Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/../app/views',
));

// Register the monolog logging service

if($app['debug'] === true) {
  $app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../log/development.log',
  ));
}

// Our web handlers

$app->get('/', function() use($app) {
  if($app['debug'] === true) {
    $app['monolog']->addDebug('logging output.');
  }

  // TODO put this into a Service
  $api = new SpotifyWebAPI\SpotifyWebAPI();
  $trackIds = array(
    '1LeItUMezKA1HdCHxYICed',
    '7EjyzZcbLxW7PaaLua9Ksb',
    '3HwePAJXjBJSaFICfPWYUl'
  );
  $tracks = [];
  foreach ($trackIds as $track) {
    array_push($tracks, $api->getTrack($track));
  }

  return $app['twig']->render('index.html', array(
    'tracks' => $tracks
  ));
});

$app->get('/testdatabase', function() use($app) {
  $url = parse_url("mysql://b1e1b2fbeaf53f:42dd4cda@us-cdbr-iron-east-02.cleardb.net/heroku_9bf566e09227697?reconnect=true");
  $server = $url["host"];
  $username = $url["user"];
  $password = $url["pass"];
  $db = substr($url["path"], 1);
  $conn = new mysqli($server, $username, $password, $db);

  if($conn->query("CREATE TABLE IF NOT EXISTS entries (id INT NOT NULL AUTO_INCREMENT, track VARCHAR(100) NOT NULL , description VARCHAR(500) NOT NULL , PRIMARY KEY (id) ) ENGINE = InnoDB;") === TRUE) {
    echo "entries table created \n";
  }

  if($insert = $conn->query("INSERT entries (track, description) VALUES ('1LeItUMezKA1HdCHxYICed', 'Song from my childhood')")) {
    echo "data seeded \n";
  }

  if ($result = $conn->query("SELECT track, description FROM entries LIMIT 10")) {
    printf("Select returned %d rows.\n", $result->num_rows);
    $result->close();
  }

  $mysqli->close();

  return true;
});

// API handlers

$app->get('/api/mockdata', function() use($app) {
  $api = new SpotifyWebAPI\SpotifyWebAPI();
  $track = $api->getTrack('7EjyzZcbLxW7PaaLua9Ksb');

  return $app->json($track);
});

$app->run();

?>
