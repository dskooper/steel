<?php
require_once('utils.php');

// Require authentication
requireLogin();

$client = getTwitterClient();

// Handle actions

// Bundle actions together, slightly less readable but better than multiple if statements
$actions = [
    'favorite' => ['method' => 'favoriteTweet', 'message' => 'Tweet favorited!'],
    'unfavorite' => ['method' => 'unfavoriteTweet', 'message' => 'Tweet unfavorited!'],
    'retweet' => ['method' => 'repostTweet', 'message' => 'Tweet retweeted!'],
    'delete' => ['method' => 'deleteTweet', 'message' => 'Tweet deleted!'],
    'follow' => ['method' => 'followUser', 'message' => 'Now following @%s!'],
    'unfollow' => ['method' => 'unfollowUser', 'message' => 'Unfollowed @%s!']
];

foreach ($actions as $action => $config) {
    if (isset($_GET[$action])) {
        $param = $_GET[$action];
        $result = $client->{$config['method']}($param);
        
        if ($result && !isset($result['errors'])) {
            $_SESSION['success'] = strpos($config['message'], '%s') !== false 
                ? sprintf($config['message'], $param) 
                : $config['message'];
        } else {
            setError("Failed to " . str_replace('_', ' ', $action) . ".");
        }
        
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 
                   (in_array($action, ['follow', 'unfollow']) ? 'profile.php?username=' . urlencode($param) : 'index.php');
        header("Location: " . $referer);
        exit;
    }
}

// Otherwise just go home
header("Location: index.php");
exit;
?>
