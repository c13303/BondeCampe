<?php
error_reporting(0);
ini_set('display_errors', 0);

$allow_download = true;
$bodyclass = '';

require 'config/config.php';
require 'include.php';

if (!$baseurl) {
    exit('Config missing');
}

foreach (['audio', 'archive'] as $d) {
    if (!is_dir($d)) mkdir($d, 0755, true);
}

if (!is_file('stats.csv')) {
    fopen('stats.csv', 'w');
}

$album = filter_input(INPUT_GET, 'a', FILTER_UNSAFE_RAW);
$criteria = filter_input(INPUT_GET, 'c', FILTER_SANITIZE_URL);

$homepage = !$album;
$dir = $homepage ? 'audio' : 'audio/' . $album;

$elements = [];
$tracks = [];
$text = '';
$iframe = false;
$cover = $ogimage;

$pagetitle = $album ? ucfirst($album) : 'Albums';

if (!$homepage) {
    if (is_file("$dir/cover.jpg")) $cover = "$baseurl/$dir/cover.jpg";
    elseif (is_file("$dir/cover.png")) $cover = "$baseurl/$dir/cover.png";

    if (is_dir("$dir/iframeonly")) $iframe = 'only';
    elseif (is_dir("$dir/iframe")) $iframe = true;
}

if (is_dir($dir)) {
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..' || str_contains($file, 'disabled')) continue;
        $path = "$dir/$file";

        if ($homepage && is_dir($path)) {
            if ($criteria && !str_contains($file, $criteria)) continue;
            if (!$criteria && str_contains($file, 'hidden')) continue;

            $parts = explode('-', $file, 2);
            $rank = is_numeric($parts[0]) ? (int)$parts[0] : 9999;
            $title = ucfirst(trim($parts[1] ?? $file));

            $c = "$path/cover.jpg";
            if (!is_file($c)) $c = "$path/cover.png";
            $c = is_file($c) ? "$baseurl/$c" : $ogimage;

            $elements[$rank] =
                "<div class='release'>
                    <a href='$baseurl/?a=" . urlencode($file) . "' class='folder'>
                        <img src='$c'><br>$title
                    </a>
                </div>";
        }

        if (!$homepage && is_file($path)) {
            if (preg_match('/\.mp3$/i', $file)) {
                $parts = explode('-', str_replace('.mp3', '', $file), 2);
                $rank = is_numeric($parts[0]) ? (int)$parts[0] : count($tracks);
                $tracks[$rank] = [
                    'url' => "$baseurl/$path",
                    'title' => ucfirst(trim($parts[1] ?? $file))
                ];
            }

            if (preg_match('/\.txt$/i', $file)) {
                $text = nl2br(
                    preg_replace(
                        '"\bhttps?://\S+"',
                        '<a target="_blank" href="$0">$0</a>',
                        file_get_contents($path)
                    )
                );
            }
        }
    }
}

if ($homepage) krsort($elements);
else ksort($tracks);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($pagetitle) ?></title>
<meta name="viewport" content="width=device-width">
<meta property="og:image" content="<?= $cover ?>">
<link rel="icon" href="<?= $baseurl ?>/config/favicon.png">
<link rel="stylesheet" href="<?= $baseurl ?>/theme/mystyle.css">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= $baseurl ?>/lib/audiojs/audio.min.js"></script>
</head>

<body class="<?= $iframe === 'only' ? 'iframeonly' : ($iframe ? 'iframed' : '') ?>">

<?php if ($iframe === 'only'): ?>
<iframe src="<?= $baseurl ?>/audio/<?= $album ?>/iframeonly/" id="iframeonlyframe"></iframe>
<?php elseif ($iframe): ?>
<iframe src="<?= $baseurl ?>/audio/<?= $album ?>/iframe/" id="visuframe"></iframe>
<?php endif; ?>

<div class="container <?= $homepage ? 'isDir' : '' ?>">
<header>
<h1>
<a href="<?= $baseurl ?>">Le Matin's BondeCampe</a>
<?= $criteria ? " > <a href='?c=$criteria'>$criteria</a>" : '' ?>
<?= $album ? " > $album" : '' ?>
</h1>
</header>

<?php if ($homepage): ?>

<div class="album-list"><?= implode('', $elements) ?></div>

<?php else: ?>

<div class="inline right">
<a href="<?= $cover ?>" class="popin">
<img src="<?= $cover ?>">
</a>
</div>

<div class="inline left">

<?php if ($tracks): ?>
<audio id="player"></audio>
<ol id="playlist">
<?php foreach ($tracks as $t): ?>
<li>
<a href="<?= $t['url'] ?>" data-src="<?= $t['url'] ?>">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
<path d="M152.443 136.417l207.114 119.573-207.114 119.593z" fill="#ccc"/>
</svg>
<?= $t['title'] ?>
</a>
</li>
<?php endforeach; ?>
</ol>
<?php endif; ?>

<?= $text ?>

<?php if ($tracks): ?>
<div class="monzip">
<a href="<?= $baseurl ?>/zip.php?dir=<?= urlencode($album) ?>" class="zip">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000">
<path d="M500 530.6L745 285.6H561.3V40.6H438.8V285.6H255z"/>
<path d="M722.7 430.4L654 499.1 903 591.9 500 742.1 97 591.9 345.9 499.1 277.2 430.4 10 530.6V775.6L500 959.4 990 775.6V530.6z"/>
</svg>
<br>full album
</a>
</div>
<?php endif; ?>

</div>

<script>
audiojs.events.ready(function () {
    const a = audiojs.createAll();
    const audio = a[0];
    const first = document.querySelector('#playlist a');
    if (first) audio.load(first.dataset.src);

    document.querySelectorAll('#playlist li').forEach(li => {
        li.onclick = e => {
            e.preventDefault();
            audio.load(li.querySelector('a').dataset.src);
            audio.play();
        };
    });
});

GLightbox({ selector: '.popin' });
</script>

<?php endif; ?>
</div>

<?php if (function_exists('logIt')) logIt($album); ?>

</body>
</html>
