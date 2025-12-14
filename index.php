<?php

/// FILE FORE 


error_reporting(0);
ini_set('display_errors', 0);

$allow_download = true;
$bodyclass = '';
include('config/config.php');
if (!$baseurl)
    exit('Please configure : copy config/config-exemple.php to config/config.php and edit the settings');


/* config checks */

if (!is_dir('audio')) {
    if (!mkdir('audio', 0755, true)) {
        die('error : cant create audio folder');
    }
}
if (!is_dir('archive')) {
    if (!mkdir('archive', 0755, true)) {
        die('error : cant create archive folder');
    }
}

if (!is_file('stats.csv')) {
    fopen('stats.csv', 'w');
}



require('include.php');
$dir = "audio";

$a = filter_input(INPUT_GET, 'a', FILTER_SANITIZE_STRING);
$dir2 = $a ? $a : '';
$dir = 'audio/' . $dir2;
$cover = $ogimage;
$mp3 = array();
$archives = array();
$elements = array();
$n = 0;
$is_hidden = 0;
$pagetitle = '';
$text = '';

$track = filter_input(INPUT_GET, 'track', FILTER_SANITIZE_NUMBER_INT);

$criteria = filter_input(INPUT_GET, 'c', FILTER_SANITIZE_URL);
if (strstr($a, 'hidden')) {
    $is_hidden = 1;
}




if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            $censor = array('..', '.', 'iframe');
            $valid = 0;

            if (!strstr($file, 'disabled')) {
                if (!empty($criteria)) {
                    if (!in_array($file, $censor) && strstr($file, $criteria)) {
                        $valid = 1;
                    }
                } else {
                    if (!in_array($file, $censor) && !strstr($file, 'hidden')) {
                        $valid = 1;
                    }
                }
            }

            if ($file == 'iframe') {
                $bodyclass = 'iframed';
                $elements[$file] = '<iframe id="visuframe" src="/' . $dir . '/iframe"></iframe>';
            }

            if ($file == 'iframeonly') {
                $bodyclass = 'iframed';
                echo '<!DOCTYPE html>
<html lang="en">
    <head></head><body style="margin:0;padding:0;border:0;"><iframe style="position:fixed; top:0; left:0; bottom:0; right:0; width:100%; height:100%; border:none; margin:0; padding:0; overflow:hidden; z-index:999999;" id="visuframe" src="/' . $dir . '/iframeonly"></iframe></body></html>';
               die();
            }

            if ($valid) {
                if (is_dir($dir . '/' . $file)) {
                    $titles = explode('-', $file);
                    $title = ucfirst(trim($titles[1]));
                    $rank = trim($titles[0]);
                    $coverfile = $ogimage;

                    if (is_file("./audio/$file/cover.jpg")) {
                        $coverfile = $baseurl . "/audio/$file/cover.jpg";
                    }
                    if (is_file("./audio/$file/cover.png")) {
                        $coverfile = $baseurl . "/audio/$file/cover.png";
                    }


                    $elements[$file] = "<div class='release'>"
                            . "<a href='$baseurl/album/$file.php' class='folder'>"
                            . "<img src='$coverfile' />"
                            . "<br/>$title</a>"
                            . "</div>";
                } else {
                    if (strstr($file, '.mp3')) {
                        $n++;
                        $url = $baseurl . '/' . $dir . '/' . $file;
                        if (strstr($file, '-')) {
                            $titles = explode('-', str_replace('.mp3', '', $file));
                            $title = trim($titles[1]);
                            $rank = trim($titles[0]);
                        } else {
                            $title = $file;
                            $rank = $n;
                        }

                        $mp3[$file] = array('url' => $url, 'title' => ucfirst($title), 'rank' => $rank);
                        if ($rank == $track) {
                            $first = $url;
                        }
                    }
                    if (strstr($file, '.jpg')) {
                        $cover = $baseurl . '/audio/' . $a . '/' . $file;
                    }
                    if (strstr($file, '.png')) {
                        $cover = $baseurl . '/audio/' . $a . '/' . $file;
                    }
                    if (strstr($file, '.txt')) {

                        $text = file_get_contents($dir . '/' . $file);
                        $text = preg_replace('"\b(https?://\S+)"', '<a target="_blank" href="$1">$1</a>', $text);
                        $text = nl2br($text);
                    }
                    if (strstr($file, '.html')) {

                        $requirehtml = $dir . '/' . $file;
                    }

                    if (strstr($file, '.rar')) {

                        $url = $dir . '/' . $file;
                        $rar = $url;
                    }

                    if (strstr($file, '.zip')) {
                        $url = $dir . '/' . $file;
                        $rar = $url;
                    }
                    if (strstr($file, '.config')) {
                        $config = file_get_contents($dir . '/' . $file);
                        if (strstr($config, 'NOZIP')) {
                            $nozip = 1;
                        }
                    }
                }
            }
        }
    }
    closedir($dh);
} else {
    $erreur = '404';
}

rsort($elements);
ksort($mp3);

$cover = str_replace(' ', '%20', $cover);
$metadescription = $pagetitle . ' ' . substr($text, 0, 100);
$albumurl = $baseurl . '/album/' . $a . '.php';
?><!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?= $pagetitle; ?> <?= $dir2; ?></title>
        <link rel="stylesheet" type="text/css" href="<?= $baseurl; ?>/theme/mystyle.css" />
        <style>
            p { clear: both; }
            <?php
            echo $background ? ' body{ background : ' . $background . '}' : '';
            echo $color ? ' body{ color : ' . $color . '}' : '';
            echo $linkcolor ? ' a{ color : ' . $linkcolor . '}' : '';
            ?>
        </style>
        <script src="<?= $baseurl; ?>/lib/jquery-3.1.1.min.js"></script>
        <meta name="description" content="<?= $metadescription; ?>" />
        <meta property="og:image" content="<?= $cover ? "$baseurl/$cover" : $ogimage; ?>" />
        <meta property="og:image:width" content="500" />
        <meta property="og:image:height" content="500" />
        <meta property="og:title" content="<?= $pagetitle; ?> <?= $dir2; ?>" />
        <meta property="og:url" content="<?= $albumurl; ?>" />
        <meta property="og:description" content="<?= $metadescription; ?>" />
        <meta name="viewport" content="width=device-width, user-scalable=yes">    

        <link rel="icon" href="<?= $baseurl; ?>/config/favicon.png" />
        <script src="<?= $baseurl; ?>/lib/audiojs/audio.min.js"></script>
		<script>
		function updateDownloadText(event, element) {
			   element.innerHTML = "Downloading... please wait...";

		}
		</script>
    </head>
    <body class="<?= $bodyclass; ?>">

        <div class="container <?= $a ? '' : 'isDir'; ?>">

            <header style="text-align: left;">
                <?php
                if ($is_hidden) {
                    $criteria = 'hidden';
                }
                ?>
                <h1><a href="<?= $baseurl; ?>">Bondecampe > <?= $artistname; ?> <?=
                        ($criteria) ? '> '
                                . '<a href="' . $baseurl . '/c/' . $criteria . '.php">' . ucfirst($criteria) . '</a>' : '';
                        ?></a> 
                    <?= $a ? ' > <a href="' . $baseurl . '/album/' . $a . '.php">' . $a . '</a>' : ''; ?>
                </h1>
            </header>


            <?php
            if (!empty($elements))
                foreach ($elements as $file => $html) {
                    echo $html;
                }
            ?>
            <?php
            if (!empty($erreur)) {
                echo $erreur;
            }
            ?>
            <?php if ($cover && $cover != $ogimage) : ?>
                <div class="right inline">
                    <img src="<?= $cover; ?>" />
                </div>
            <?php endif; ?>
            <div class="left inline">
                <script>
                    var myAudio;
                    audiojs.events.ready(function () {

                        // Setup the player to autoplay the next track
                        var a = audiojs.createAll({
                            trackEnded: function () {
                                var next = $('ol li.playing').next();
                                if (!next.length) {
                                    /*  next = $('ol li').first();*/
                                } else {
                                    next.addClass('playing').siblings().removeClass('playing');
                                    audio.load($('a', next).attr('data-src'));
                                    audio.play();
                                }
                            }
                        });
                        // Load in the first track
                        var audio = a[0];
                        myAudio = audio;
                        first = $('ol a').attr('data-src');

                        if (first) {
                            $('ol li').first().addClass('playing');
                            audio.load(first);
                        }

                        // Load in a track on click
                        $('ol li').click(function (e) {
                            e.preventDefault();
                            $(this).addClass('playing').siblings().removeClass('playing');
                            audio.load($('a', this).attr('data-src'));
                            audio.play();
                        });
                        // Keyboard shortcuts
                        $(document).keydown(function (e) {
                            var unicode = e.charCode ? e.charCode : e.keyCode;
                            // right arrow
                            if (unicode == 39) {
                                var next = $('li.playing').next();
                                if (!next.length)
                                    next = $('ol li').first();
                                next.click();
                                // back arrow
                            } else if (unicode == 37) {
                                var prev = $('li.playing').prev();
                                if (!prev.length)
                                    prev = $('ol li').last();
                                prev.click();
                                // spacebar
                            } else if (unicode == 32) {
                                audio.playPause();
                            }

                        })
                    });
                </script>
                <?php if ($mp3): ?>
                    <audio src="" id='player' preload='auto'></audio>

                    <ol id="playlist">
                        <?php
                        $ntrack = 0;
                        foreach ($mp3 as $file => $html) {
                            if (is_array($html) && $html['url']) {
                                $ntrack++;
                                ?>
                                <li <?php if ($html['rank'] == $track) echo 'class="active"'; ?> data-rank='<?= $html['rank']; ?>'>                                 
                                    <a href="<?= $html['url']; ?>" id="playtrack<?= $ntrack; ?>" data-src="<?= $html['url']; ?>">
                                        <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="512" height="512" viewBox="0 0 512 512"><g></g><path d="M152.443 136.417l207.114 119.573-207.114 119.593z" fill="#ccc" /></svg>
                                        <?= $html['title']; ?> 
                                    </a>                              
                                </li>

                                <?php
                            }
                        }
                        ?>
                    </ol>
                <?php endif; ?>

                <?php if (!empty($text)) echo $text; ?>
                <?php
                if (!empty($requirehtml)) {
                    require($requirehtml);
                }
                ?>


                <?php
                if (!empty($mp3) && empty($nozip)) {
                    ?>
                    <div class='monzip'><a href="<?= $baseurl; ?>/zip.php?dir=<?= $dir2; ?>" class='zip' onclick="updateDownloadText(event, this)" >  
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1000 1000" enable-background="new 0 0 1000 1000" xml:space="preserve"><g><g><path d="M500,530.6l245-245H561.3v-245H438.8v245H255L500,530.6z M722.7,430.4l-68.7,68.7L903,591.9L500,742.1L97,591.9l248.9-92.8l-68.7-68.7L10,530.6v245l490,183.8l490-183.8v-245L722.7,430.4z"/></g></g></svg>
                            <br/>full album zip (.mp3)</a></div>
                    <?php
                }
                ?>

                <?php
                if (!empty($rar) && empty($nozip)) {
                    ?>
                    <div class="monzip">
    <a href="<?= $baseurl; ?>/<?= $rar; ?>" class="rar" onclick="updateDownloadText(event, this)">
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1000 1000" enable-background="new 0 0 1000 1000" xml:space="preserve">
            <g>
                <g>
                    <path d="M500,530.6l245-245H561.3v-245H438.8v245H255L500,530.6z M722.7,430.4l-68.7,68.7L903,591.9L500,742.1L97,591.9l248.9-92.8l-68.7-68.7L10,530.6v245l490,183.8l490-183.8v-245L722.7,430.4z"/>
                </g>
            </g>
        </svg>
        <br/>full album rar (LOSSLESS .flac)
    </a>
</div>


                    <?php
                }
                ?>

            </div>
        </div>




        <div class='footer'>
            <a target="_blank" href="<?= $authorUrl; ?>"><?= $author; ?></a> / <a href="<?= $giturl; ?>" target="_blank">Git</a> / <a href="mailto:<?= $email; ?>">email</a> / <a href="/?c=hidden" style="color:<?= $background ? $background : 'white'; ?> !important;">hidden</a>
        </div>

        <?php
        logIt($a);
        ?>

    </body>
</html>