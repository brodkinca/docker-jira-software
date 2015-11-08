<?php

require 'vendor/autoload.php';

define('FEED_CURRENT', 'https://my.atlassian.com/download/feeds/current/jira-software.json');

$client = new GuzzleHttp\Client();
try {
    $feed_current = $client->request('GET', FEED_CURRENT)->getBody();
} catch (Exception $e) {
    \cli\err('Could not retieve Atlasian download feed.');
    exit(1);
}

$current_a = json_decode(trim((string) $feed_current, 'downloads()'), true);

$current_a = array_filter($current_a, function ($version) {
    if (substr($version['zipUrl'], -7) !== '.tar.gz') {
        return false;
    }

    return $version;
});

$versions = [];
foreach ($current_a as $version) {
    $versions[$version['zipUrl']] = $version['version'];
}

uasort($versions, 'version_compare');

\cli\line(count($versions).' Versions Found:');
$tree = new \cli\Tree();
$tree->setData($versions);
$tree->setRenderer(new \cli\tree\Ascii);
$tree->display();

// Generate Dockerfile
$m = new Mustache_Engine;
$dockerfile = $m->render(
    file_get_contents('Dockerfile.tmpl'),
    ['version' => end($versions), 'url' => key($versions)]
);

file_put_contents('Dockerfile', $dockerfile);

echo PHP_EOL.'Done!'.PHP_EOL;