<?php

/// FILE TO UPDATE

// Disable error reporting and display - production mode
error_reporting(0);
ini_set('display_errors', 0);

// Default configuration
$allow_download = true;
$bodyclass = '';
include('config/config.php'); // Import main configuration

// Check if base URL is configured
if (!$baseurl) {
    exit('Please configure : copy config/config-exemple.php to config/config.php and edit the settings');
}

// Create required directories if they don't exist
foreach (['audio', 'archive'] as $folder) {
    if (!is_dir($folder) && !mkdir($folder, 0755, true)) {
        die("error : can't create $folder folder");
    }
}

// Create stats file if it doesn't exist
if (!is_file('stats.csv')) {
    fopen('stats.csv', 'w');
}

require('include.php'); // Import additional functionality

// Get query parameters
$album = filter_input(INPUT_GET, 'a', FILTER_UNSAFE_RAW);      // Album name
$criteria = filter_input(INPUT_GET, 'c', FILTER_SANITIZE_URL); // Filter criteria (e.g. 'hidden')
$dir = 'audio/' . $album;

// Initialize variables
$elements = []; // Array to store album folders for homepage
$mp3 = [];      // Array to store mp3 files for album page
$n = 0;         // Counter for tracks without rank in filename

// Set defaults
$pagetitle = $album ? ucfirst($album) : 'Albums';
$text = '';     // Album description text (from .txt file)
$cover = $ogimage; // Default cover image
$iframe = false;   // Whether to display iframe visualization

// Determine if we're on homepage or album page
$homepage = empty($album);
if ($homepage) {
    $dir = 'audio'; // Root audio directory for homepage
} else {
    // Find album cover image
    if (is_file("$dir/cover.jpg")) {
        $cover = "$baseurl/$dir/cover.jpg";
    } elseif (is_file("$dir/cover.png")) {
        $cover = "$baseurl/$dir/cover.png";
    }
    // Check if album has iframe visualization
    if (is_dir("$dir/iframe")) {
        $iframe = true;
    } elseif (is_dir("$dir/iframeonly")) {
        $iframe = 'only';
    }
}

// Process directory contents
$nu = 0;
$nuvalid = 0;
if (is_dir($dir) && ($dh = opendir($dir))) {
    while (($file = readdir($dh)) !== false) {
        $censor = array('..', '.', 'iframe'); // Files/dirs to ignore
        $valid = 0; // Flag to determine if an item should be displayed
        $nu++;
        // Skip disabled files
        if (strstr($file, 'disabled')) {
            continue;
        }
        
        $filepath = "$dir/$file";
        $fileurl = "$baseurl/" . str_replace(' ', '%20', $filepath); // URL-encoded path
        
        if ($homepage) {
            // HOMEPAGE MODE - Display album folders
            if (is_dir($filepath) && !in_array($file, $censor)) {
                // Apply filtering based on criteria
                if (!empty($criteria)) {
                    // Filter by specific criteria (e.g., 'hidden')
                    if (strstr($file, $criteria)) {
                        $valid = 1;
                    }
                } else {
                    // Normal mode - hide albums with 'hidden' in name
                    if (!strstr($file, 'hidden')) {
                        $valid = 1;
                    }
                }
                
                if ($valid) {
					$nuvalid++;
                    // Parse album name format: "01-AlbumName" or "HIDDEN-AlbumName"
                    $titles = explode('-', $file, 2);
                    $title = ucfirst(trim($titles[1] ?? $file)); // Display name
                    
                    // Special handling for ranks
                    static $hiddenCounter = 10000; // Start with a high number for hidden albums
                    
                    // Check if this is a hidden album
                    if (stristr($file, 'hidden')) {
                        // Assign a unique numeric rank for each hidden album
                        $rank = $hiddenCounter--;
                    } else {
                        // For regular albums, use the existing rank or assign a new one
                        $rank = is_numeric(trim($titles[0] ?? '')) ? trim($titles[0]) : $hiddenCounter--;
                    }
                    
                    // Find album cover
                    $coverfile = is_file("$filepath/cover.jpg") ? "$baseurl/$filepath/cover.jpg" :
                               (is_file("$filepath/cover.png") ? "$baseurl/$filepath/cover.png" : $ogimage);
                    
                    // Store HTML for this album, indexed by rank for sorting
                    $elements[$rank] = "<div class='release'>
                        <a href='$baseurl/?a=" . urlencode($file) . "' class='folder'>
                        <img src='$coverfile' /><br/>$title</a></div>";
                }
            }
        } else {
            // ALBUM MODE - Display album contents
            if (is_file($filepath)) {
                // Process MP3 files
                if (preg_match('/\.(mp3)$/i', $file)) {
                    $n++; // Track counter
                    // Parse track name format: "01-TrackName.mp3"
                    $titles = explode('-', str_replace('.mp3', '', $file), 2);
                    $title = ucfirst(trim($titles[1] ?? $file)); // Display name
                    $rank = trim($titles[0] ?? $n);              // Sorting rank or counter
                    // Store track info, indexed by rank for sorting
                    $mp3[$rank] = ['url' => $fileurl, 'title' => $title, 'rank' => $rank];
                } 
                // Process cover images (not named "cover")
                elseif (preg_match('/\.(jpg|png)$/i', $file) && !strstr($file, 'cover')) {
                    $cover = $fileurl;
                } 
                // Process text description files
                elseif (preg_match('/\.(txt)$/i', $file)) {
                    $text = file_get_contents($filepath);
                    // Convert URLs to clickable links
                    $text = preg_replace('"\b(https?://\S+)"', '<a target="_blank" href="$1">$1</a>', $text);
                    $text = nl2br($text); // Convert newlines to <br>
                }
            } 
            // Handle iframe visualization folder
            else if ($file == 'iframe') {
                $bodyclass = 'iframed';
                $iframe = true;
            } 
            // Handle iframeonly visualization folder
            else if ($file == 'iframeonly') {
                $bodyclass = 'iframeonly';
                $iframe = 'only';
            }
        }
    }
    closedir($dh);
}

// Sort the elements by their keys (rank)
if ($homepage) {
    // For homepage, we need to ensure numeric sorting for the ranks
    uksort($elements, function($a, $b) {
        return (float)$b <=> (float)$a; // Reverse numeric sort (krsort equivalent)
    });
} else {
    ksort($mp3);       // Sort tracks by rank (track number)
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($pagetitle); ?></title>
    <link rel="stylesheet" href="<?= $baseurl; ?>/theme/mystyle.css" />
   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="<?= $baseurl; ?>/lib/audiojs/audio.min.js"></script>
    <meta name="description" content="<?= substr(strip_tags($text), 0, 100); ?>" />
    <meta property="og:image" content="<?= $cover; ?>" />
    <meta property="og:title" content="<?= htmlspecialchars($pagetitle); ?>" />
    <meta name="viewport" content="width=device-width, user-scalable=yes">
    <link rel="icon" href="<?= $baseurl; ?>/config/favicon.png" />
    <style>
        /* Iframe visualization styling */
        #visuframe {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            border: none;
        }
        
        /* Full-page iframe styling */
        #iframeonlyframe {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999;
            border: none;
        }
        
        /* Hide content when in iframeonly mode */
        body.iframeonly .container,
        body.iframeonly .footer {
            display: none;
        }
        <?php
        // Apply custom styling from config
        echo $background ? ' body{ background : ' . $background . '}' : '';
        echo $color ? ' body{ color : ' . $color . '}' : '';
        echo $linkcolor ? ' a{ color : ' . $linkcolor . '}' : '';
        ?>
    </style>
<script>
    // Update text when downloading album
    function updateDownloadText(event, element) {
        element.innerHTML = "Downloading... please wait...";
        element.style.pointerEvents = "none";
        element.style.opacity = "0.7";
        element.style.cursor = "default";
        // Prevent default action to avoid immediate navigation
        event.preventDefault();
        
        // Navigate to the download URL after a small delay
        setTimeout(function() {
            window.location.href = element.href;
        }, 100);
        
        return false;
    }
    </script>
</head>
<body class="<?php echo $iframe === 'only' ? 'iframeonly' : ($iframe ? 'iframed' : $bodyclass); ?>"><?php if ($iframe === 'only'): ?>
    <!-- Display iframeonly visualization (full page) -->
    <iframe id="iframeonlyframe" src="<?= $baseurl; ?>/audio/<?= $album; ?>/iframeonly/"></iframe>
<?php elseif ($iframe): ?>
    <!-- Display iframe visualization as background -->
    <iframe id="visuframe" src="<?= $baseurl; ?>/audio/<?= $album; ?>/iframe/"></iframe>
<?php endif; ?>
    <div class="container <?= $album ? '' : 'isDir'; ?>">
        <header>
            <h1>
                <!-- Navigation breadcrumbs -->
                <a href="<?= $baseurl; ?>">Bondecampe</a>
                <?php if ($criteria): ?>
                    > <a href="<?= $baseurl; ?>/?c=<?= $criteria; ?>"><?= ucfirst($criteria); ?></a>
                <?php endif; ?>
                <?php if ($album): ?>
                    > <a href="<?= $baseurl; ?>/?a=<?= urlencode($album); ?>"><?= htmlspecialchars($album); ?></a>
                <?php endif; ?>
            </h1>
        </header>
        <?php if ($homepage): ?>
            <!-- HOMEPAGE - Display album list -->
            <div class="album-list">
                <?php foreach ($elements as $html) echo $html; ?>
            </div>
        <?php else: ?>
            <!-- ALBUM PAGE - Display album contents -->
            <div class="inline right">
                <?php if ($cover && $cover != $ogimage): ?>
                    <!-- Display album cover -->
                    <img src="<?= $cover; ?>" />
                <?php endif; ?>
            </div>
            <div class="inline left">
                <?php if ($mp3): ?>
                    <!-- Audio player and playlist -->
                    <audio id="player" preload="auto"></audio>
                    <ol id="playlist">
                        <?php foreach ($mp3 as $rank => $track): ?>
                            <li data-rank="<?= $rank; ?>">
                                <a href="<?= $track['url']; ?>" data-src="<?= $track['url']; ?>">
                                    <!-- Play icon SVG -->
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="512" height="512" viewBox="0 0 512 512"><g></g><path d="M152.443 136.417l207.114 119.573-207.114 119.593z" fill="#ccc" /></svg>
                                    <?= $track['title']; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <p>Aucun fichier audio trouvé dans cet album.</p>
                <?php endif; ?>
                
                <!-- Display album description text -->
                <?php if (!empty($text)) echo $text; ?>
                
                <!-- Download album button -->
                <?php if (!empty($mp3)): ?>
                    <div class='monzip'>
                        <a href="<?= $baseurl; ?>/zip.php?dir=<?= urlencode($album); ?>" class='zip' onclick="updateDownloadText(event, this)">  
                            <!-- Download icon SVG -->
                            <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1000 1000" enable-background="new 0 0 1000 1000" xml:space="preserve"><g><g><path d="M500,530.6l245-245H561.3v-245H438.8v245H255L500,530.6z M722.7,430.4l-68.7,68.7L903,591.9L500,742.1L97,591.9l248.9-92.8l-68.7-68.7L10,530.6v245l490,183.8l490-183.8v-245L722.7,430.4z"/></g></g></svg>
                            <br/>full album zip (.mp3)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div style="clear: both;"></div>
            
            <script>
                audiojs.events.ready(function() {
                    // Setup the player to autoplay the next track
                    var a = audiojs.createAll({
                        trackEnded: function() {
                            var next = $('ol li.playing').next();
                            if (!next.length) {
                                // Do nothing when reaching the end
                            } else {
                                next.addClass('playing').siblings().removeClass('playing');
                                audio.load($('a', next).attr('data-src'));
                                audio.play();
                            }
                        }
                    });
                    
                    // Load in the first track
                    var audio = a[0];
                    first = $('ol a').first().attr('data-src');
                    
                    if (first) {
                        $('ol li').first().addClass('playing');
                        audio.load(first);
                    }
                    
                    // Load in a track on click
                    $('ol li').click(function(e) {
                        e.preventDefault();
                        $(this).addClass('playing').siblings().removeClass('playing');
                        audio.load($('a', this).attr('data-src'));
                        audio.play();
                    });
                    
                    // Keyboard shortcuts
                    $(document).keydown(function(e) {
                        var unicode = e.charCode ? e.charCode : e.keyCode;
                        // right arrow - next track
                        if (unicode == 39) {
                            var next = $('li.playing').next();
                            if (!next.length)
                                next = $('ol li').first();
                            next.click();
                        // left arrow - previous track
                        } else if (unicode == 37) {
                            var prev = $('li.playing').prev();
                            if (!prev.length)
                                prev = $('ol li').last();
                            prev.click();
                        // spacebar - play/pause
                        } else if (unicode == 32) {
                            audio.playPause();
                        }
                    });
                });
            </script>
        <?php endif; ?>
    </div>
    
    <!-- Footer with links -->
    <div class='footer'>
        <a target="_blank" href="<?= $authorUrl ?? '#'; ?>"><?= $author ?? 'Author'; ?></a> / 
        <a href="<?= $giturl ?? '#'; ?>" target="_blank">Git</a> / 
        <a href="mailto:<?= $email ?? ''; ?>">email</a> / 
        <!-- Hidden link - same color as background to make it invisible -->
        <a href="<?= $baseurl; ?>/?c=hidden" style="color:<?= $background ? $background : 'white'; ?> !important;">hidden</a>
    </div>
    
    <!-- Track page visits if logIt function exists -->
    <?php
    if (function_exists('logIt')) {
        logIt($album);
    }
    ?>
	

</body>
</html>