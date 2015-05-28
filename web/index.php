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

// Web handlers

$app->get('/', function() use($app) {
  if($app['debug'] === true) {
    $app['monolog']->addDebug('logging output.');
  }

  $api = new SpotifyWebAPI\SpotifyWebAPI();

  // TODO put this into a Service
  $url = parse_url("mysql://b1e1b2fbeaf53f:42dd4cda@us-cdbr-iron-east-02.cleardb.net/heroku_9bf566e09227697?reconnect=true");
  $server = $url["host"];
  $username = $url["user"];
  $password = $url["pass"];
  $db = substr($url["path"], 1);
  $conn = new mysqli($server, $username, $password, $db);
  if ($result = $conn->query("SELECT track, description FROM entries LIMIT 10")) {
    $tracks = [];
    while($row = $result->fetch_object()) {
      $row->track = $api->getTrack($row->track);
      $entries[] = $row;
    }
    $result->close();
    return $app['twig']->render('index.html', array(
      'entries' => $entries
    ));
  }
});

$app->get('/resetdatabase', function() use($app) {
  $config = include('../config.php');
  $url = parse_url($config['DB_URL']);
  $server = $url["host"];
  $username = $url["user"];
  $password = $url["pass"];
  $db = substr($url["path"], 1);
  $conn = new mysqli($server, $username, $password, $db);

  if($conn->query("CREATE TABLE IF NOT EXISTS entries (id INT NOT NULL AUTO_INCREMENT, track VARCHAR(100) NOT NULL , description VARCHAR(500) NOT NULL , PRIMARY KEY (id) ) ENGINE = InnoDB;") === TRUE) {
    echo "entries table created \n";
  }

  if($insert = $conn->query("INSERT entries (track, description) VALUES ('1LeItUMezKA1HdCHxYICed', 'Saw this song performed live Summer of 2014 with my friend from Salt Lake and it was an amazing show')")) {
    echo "data seeded \n";
  }

  if($insert = $conn->query("INSERT entries (track, description) VALUES ('5qWgGPylB0Al9IVq2HKTHE', 'Drove down to Mardi Gras from Virginia with no iPod jack and this was the only CD we had. Perfect song for driving through the night.')")) {
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
