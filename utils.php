<?php
require_once('TwitterClient.php');

/**
 * Get the Twitter client instance
 *
 * @return TwitterClient The Twitter client
 */
function getTwitterClient() {
    static $client = null;
    if ($client === null) {
        $client = new TwitterClient();
    }
    return $client;
}

/**
 * Get any error message from the session
 *
 * @return string The error message
 */
function getError() {
    $error = '';
    if (isset($_SESSION['error'])) {
        $error = $_SESSION['error'];
        unset($_SESSION['error']);
    }
    return $error;
}

/**
 * Set an error message in the session
 *
 * @param string $message The error message
 */
function setError($message) {
    $_SESSION['error'] = $message;
}

/**
 * Ensure user is logged in, redirect to login if not
 */
function requireLogin() {
    $client = getTwitterClient();
    if (!$client->isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Get tweet text (handles both standard and extended mode)
 *
 * @param array $tweet The tweet object
 * @return string The tweet text
 */
function getTweetText($tweet) {
    // Extended mode uses 'full_text', standard mode uses 'text'
    if (isset($tweet['full_text'])) {
        return $tweet['full_text'];
    }
    return isset($tweet['text']) ? $tweet['text'] : '';
}

/**
 * HTML escape function
 *
 * @param string $string The string to escape
 * @return string The escaped string
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format tweet text by converting @mentions, #hashtags, and URLs to links
 * 
 * @param string $text The tweet text to format
 * @return string Formatted text with HTML links
 */
function formatTweet($text) {
    // Use a placeholder approach that safely preserves special characters
    $placeholders = array();
    $placeholder_i = 0;
    
    // Function to create a placeholder
    $createPlaceholder = function() use (&$placeholder_i) {
        return "PLACEHOLDER_" . ($placeholder_i++) . "_PLACEHOLDER";
    };
    
    // Safely escape text using a custom function that preserves apostrophes
    $safeEscape = function($str) {
        return htmlspecialchars($str, ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    };
    
    // Extract URLs and replace with placeholders
    $urlPattern = '/(https?:\/\/\S+)/i';
    $text = preg_replace_callback($urlPattern, function($matches) use (&$placeholders, $createPlaceholder, $safeEscape) {
        $url = $matches[1];
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $placeholder = $createPlaceholder();
        $placeholders[$placeholder] = '<a href="' . $safeUrl . '">' . $safeUrl . '</a>';
        return $placeholder;
    }, $text);
    
    // Handle RT @mentions at the beginning of retweets
    $text = preg_replace_callback('/^RT (@[a-zA-Z0-9._-]{1,64}):/', function($matches) use (&$placeholders, $createPlaceholder, $safeEscape) {
        $mention = $matches[1];
        $username = substr($mention, 1); // Remove the @ symbol
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $placeholder = $createPlaceholder();
        $placeholders[$placeholder] = 'RT <a href="profile.php?username=' . $safeUsername . '">@' . $safeUsername . '</a>:';
        return $placeholder;
    }, $text);
    
    // Extract @mentions and replace with placeholders
    $mentionPattern = '/\B@([a-zA-Z0-9._-]{1,64})(?=[\s:!?]|$)/';
    $text = preg_replace_callback($mentionPattern, function($matches) use (&$placeholders, $createPlaceholder, $safeEscape) {
        $username = $matches[1];
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $placeholder = $createPlaceholder();
        $placeholders[$placeholder] = '<a href="profile.php?username=' . $safeUsername . '">@' . $safeUsername . '</a>';
        return $placeholder;
    }, $text);
    
    // Extract #hashtags and replace with placeholders
    $hashtagPattern = '/\B#([a-zA-Z0-9_]+)\b/';
    $text = preg_replace_callback($hashtagPattern, function($matches) use (&$placeholders, $createPlaceholder, $safeEscape) {
        $hashtag = $matches[1];
        $safeHashtag = htmlspecialchars($hashtag, ENT_QUOTES, 'UTF-8');
        $placeholder = $createPlaceholder();
        $placeholders[$placeholder] = '<span style="color: #FF3284;">#' . $safeHashtag . '</span>';
        return $placeholder;
    }, $text);
    
    // Escape the remaining text (preserving apostrophes)
    $text = $safeEscape($text);
    
    // Replace placeholders with their HTML content
    foreach ($placeholders as $placeholder => $html) {
        $text = str_replace($placeholder, $html, $text);
    }
    
    return $text;
}

/**
 * Format a date for display
 *
 * @param string $date The date string
 * @return string The formatted date
 */
function formatDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return "just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . " min" . ($mins != 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours != 1 ? "s" : "") . " ago";
    } else {
        return date("j M Y", $timestamp);
    }
}

/**
 * Force HTTPS for image URLs to prevent mixed content issues
 * Only converts to HTTPS if the current page is also HTTPS
 *
 * @param string $url The image URL
 * @return string The HTTPS URL (if page is HTTPS) or original URL
 */
function forceHttpsImage($url) {
    if (empty($url)) {
        return $url;
    }
    
    // Only force HTTPS if the current page is accessed via HTTPS
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || $_SERVER['SERVER_PORT'] == 443
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    if ($isHttps) {
        return str_replace('http://', 'https://', $url);
    }
    
    return $url;
}

/**
 * Check if the browser supports modern video codecs (H.264, MPEG4)
 * Requires browsers from ~2011 onwards
 *
 * @return bool True if browser supports modern codecs
 */
function supportsModernVideoCodecs() {
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    if (strpos($userAgent, 'Haiku')) { // Haiku doesn't support the required codecs
        return false;
    } else if (preg_match('/Chrome\/(\d+)/', $userAgent, $matches)) { // Check for Chrome 25+ (2013)
        return intval($matches[1]) >= 25;
    } else if (preg_match('/Firefox\/(\d+)/', $userAgent, $matches)) { // Check for Firefox 21+ (2013)
        return intval($matches[1]) >= 21;
    } else if (preg_match('/Version\/(\d+).*Safari/', $userAgent, $matches)) { // Check for Safari 6+ (2012)
        return intval($matches[1]) >= 6;
    } else if (preg_match('/MSIE (\d+)/', $userAgent, $matches)) { // Check for IE 9+ (2011)
        return intval($matches[1]) >= 9;
    } else if (strpos($userAgent, 'Edge/') !== false || strpos($userAgent, 'Edg/') !== false) { // Check for Edge (all versions support modern codecs)
        return true;
    } else if (preg_match('/OPR\/(\d+)/', $userAgent, $matches)) { // Check for Opera 15+ (2013, when it switched to Blink)
        return intval($matches[1]) >= 15;
    } else if (preg_match('/iPhone OS (\d+)/', $userAgent, $matches) || preg_match('/iPad.*CPU OS (\d+)/', $userAgent, $matches)) { // For mobile devices, check iOS 6+ and Android 4.0+
        return intval($matches[1]) >= 6;
    } else if (preg_match('/Android (\d+)/', $userAgent, $matches)) { // Default to false for unknown browsers (safer approach)
        return intval($matches[1]) >= 4;
    } else {
        return false;
    }
}

/**
 * Render tweet images from entities
 *
 * @param array $tweet The tweet object from API
 * @return string HTML for displaying images
 */
function renderTweetImages($tweet) {
    // Twitter API v1.1 puts videos in extended_entities, not entities
    $mediaArray = null;
    
    if (isset($tweet['extended_entities']['media'])) {
        $mediaArray = $tweet['extended_entities']['media'];
    } else if (isset($tweet['entities']['media'])) {
        $mediaArray = $tweet['entities']['media'];
    }
    
    if (!$mediaArray) {
        return '';
    }
    
    $html = '';
    foreach ($mediaArray as $media) {
        $type = isset($media['type']) ? $media['type'] : 'photo';
        
        if ($type === 'photo' || $type === 'media') {
            // Static images
            $imageUrl = isset($media['media_url_https']) ? $media['media_url_https'] : $media['media_url'];
            $imageUrl = forceHttpsImage($imageUrl);
            
            $safeUrl = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
            $html .= '<div class="tweet-image">';
            $html .= '<a href="' . $safeUrl . '" target="_blank">';
            $html .= '<img src="' . $safeUrl . '" alt="Tweet image" />';
            $html .= '</a>';
            $html .= '</div>';
        } else if ($type === 'video' || $type === 'animated_gif') {
            // Videos and animated GIFs
            $videoUrl = '';
            $posterUrl = isset($media['media_url_https']) ? $media['media_url_https'] : $media['media_url'];
            
            // Get the video URL from video_info
            if (isset($media['video_info']) && isset($media['video_info']['variants'])) {
                // Find the best quality MP4 variant
                $mp4Variants = array_filter($media['video_info']['variants'], function($v) {
                    return isset($v['content_type']) && $v['content_type'] === 'video/mp4';
                });
                
                if (!empty($mp4Variants)) {
                    // Sort by bitrate (highest first)
                    usort($mp4Variants, function($a, $b) {
                        $bitrateA = isset($a['bitrate']) ? $a['bitrate'] : 0;
                        $bitrateB = isset($b['bitrate']) ? $b['bitrate'] : 0;
                        return $bitrateB - $bitrateA;
                    });
                    
                    $videoUrl = $mp4Variants[0]['url'];
                }
            }
            
            // Check if browser supports modern video codecs
            $browserSupportsVideo = supportsModernVideoCodecs();
            
            if ($videoUrl && $browserSupportsVideo) {
                $safeVideoUrl = htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8');
                $safePosterUrl = htmlspecialchars(forceHttpsImage($posterUrl), ENT_QUOTES, 'UTF-8');
                
                $html .= '<div class="tweet-video">';
                $html .= '<video controls';
                if ($type === 'animated_gif') {
                    $html .= ' loop autoplay muted';
                }
                $html .= ' poster="' . $safePosterUrl . '">';
                $html .= '<source src="' . $safeVideoUrl . '" type="video/mp4">';
                $html .= 'Your browser does not support the video tag. ';
                $html .= '<a href="' . $safeVideoUrl . '" download>Download video</a>';
                $html .= '</video>';
                $html .= '</div>';
            } else if ($videoUrl) {
                // Fallback: show download link if browser doesn't support modern codecs or video can't load
                $safeVideoUrl = htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8');
                $html .= '<div class="tweet-video" style="padding: 10px; text-align: center; background: #f5f5f5">';
                $html .= '<p style="margin: 0 0 10px 0; color: #666;">Your browser or system lacks the necessary codecs for video playback within Steel. <br>To play this video, you will have to download it and use any applicable media player.</p>';
                $html .= '<a href="' . $safeVideoUrl . '" download style="display: inline-block; padding: 4px 8px; background: #7f7f7f; color: white;">Download Video</a>';
                $html .= '</div>';
            } else {
                // No video URL available at all - show download link message
                $html .= '<div class="tweet-video" style="padding: 5px; text-align: center; background: #f5f5f5">';
                $html .= '<p style="margin: 0; color: #666;">There was supposed to be a video here, however Steel failed to load it for an unknown reason.</p>';
                $html .= '</div>';
            }
        }
    }
    
    return $html;
}
?> 
