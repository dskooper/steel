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
 * Render tweet images from entities
 *
 * @param array $tweet The tweet object from API
 * @return string HTML for displaying images
 */
function renderTweetImages($tweet) {
    if (!isset($tweet['entities']) || !isset($tweet['entities']['media'])) {
        return '';
    }
    
    $html = '';
    foreach ($tweet['entities']['media'] as $media) {
        // for some fucking reason sometimes media type is "media" instead of "photo"
        // this can cause issues where images you post don't show up??? ok man 🥀
        if ($media['type'] === 'photo' || $media['type'] === 'media') {
            $imageUrl = isset($media['media_url_https']) ? $media['media_url_https'] : $media['media_url'];
            $safeUrl = htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
            $html .= '<div class="tweet-image">';
            $html .= '<a href="' . $safeUrl . '" target="_blank">';
            $html .= '<img src="' . $safeUrl . '" alt="Tweet image" />';
            $html .= '</a>';
            $html .= '</div>';
        }
    }
    
    return $html;
}
?> 
