<?php
require_once('utils.php');

$client = getTwitterClient();

// Get username
$username = isset($_GET['username']) ? $_GET['username'] : null;
$isOwnProfile = false;

if ($username) {
    $pageTitle = "@" . $username . " - Profile";
} else {
    // Viewing your own profile
    requireLogin();
    $username = $client->getScreenName();
    $isOwnProfile = true;
    $pageTitle = "My Profile";
}

// Get profile
$profile = $client->getUserProfile($username);

// Get tweets
$max_id = isset($_GET['max_id']) ? $_GET['max_id'] : null;
$tweets = $client->getUserTweets($username, 20, $max_id);

if (!is_array($tweets)) {
    $tweets = array();
}

// Check follow status if not own profile
$friendship = null;
$isFollowing = false;
if ($client->isLoggedIn() && !$isOwnProfile && $profile) {
    $friendship = $client->getFriendship($username);
    if ($friendship && isset($friendship['relationship']['source']['following'])) {
        $isFollowing = $friendship['relationship']['source']['following'];
    }
}

$error = getError();

include('header.php');
?>

<?php if (!empty($error)): ?>
<div class="alert alert-error">
    <?php echo h($error); ?>
</div>
<?php endif; ?>

<?php if ($profile === false || !$profile): ?>
<div class="alert alert-error">
    User not found or unable to load profile.
</div>
<?php else: ?>

<div class="profile-header clearfix">
    <div class="profile-avatar">
        <?php if (isset($profile['profile_image_url'])): ?>
        <img src="<?php echo h($profile['profile_image_url']); ?>" alt="Profile Picture">
        <?php else: ?>
        <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Avatar">
        <?php endif; ?>
    </div>
    
    <div class="profile-info">
        <div class="profile-name"><?php echo h($profile['name']); ?></div>
        <div class="profile-username">@<?php echo h($profile['screen_name']); ?></div>
        
        <?php if (isset($profile['description']) && !empty($profile['description'])): ?>
        <div class="profile-bio">
            <?php echo formatTweet($profile['description']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($profile['location']) && !empty($profile['location'])): ?>
        <div style="color: #657786; font-size: 11px; margin-bottom: 3px;">
            <strong>Location:</strong> <?php echo h($profile['location']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($profile['url']) && !empty($profile['url'])): ?>
        <div style="color: #657786; font-size: 11px; margin-bottom: 3px;">
            <strong>Website:</strong> <a href="<?php echo h($profile['url']); ?>"><?php echo h($profile['url']); ?></a>
        </div>
        <?php endif; ?>
        
        <div class="profile-stats">
            <div class="profile-stat">
                <strong><?php echo number_format($profile['statuses_count']); ?></strong>
                Tweets
            </div>
            <div class="profile-stat">
                <strong><?php echo number_format($profile['friends_count']); ?></strong>
                Following
            </div>
            <div class="profile-stat">
                <strong><?php echo number_format($profile['followers_count']); ?></strong>
                Followers
            </div>
        </div>
        
        <?php if ($client->isLoggedIn() && !$isOwnProfile): ?>
        <div style="margin-top: 10px;">
            <?php if ($isFollowing): ?>
            <a href="action.php?unfollow=<?php echo h($username); ?>" class="btn" onclick="return confirm('Unfollow @<?php echo h($username); ?>?');">Unfollow</a>
            <?php else: ?>
            <a href="action.php?follow=<?php echo h($username); ?>" class="btn btn-primary">Follow</a>
            <?php endif; ?>
            
            <a href="compose.php?to=<?php echo h($username); ?>" class="btn">Send Message</a>
        </div>
        <?php endif; ?>
        
        <?php if ($isOwnProfile): ?>
        <div style="margin-top: 10px;">
            <a href="settings.php" class="btn">Edit Profile</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<h3>Tweets</h3>

<?php if (empty($tweets)): ?>
<div class="alert alert-info">
    No tweets to display.
</div>
<?php else: ?>

<?php foreach ($tweets as $tweet): ?>
<div class="tweet clearfix">
    <div class="tweet-avatar">
        <?php if (isset($tweet['user']['profile_image_url'])): ?>
        <img src="<?php echo h($tweet['user']['profile_image_url']); ?>" alt="Avatar">
        <?php else: ?>
        <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Avatar">
        <?php endif; ?>
    </div>
    
    <div class="tweet-header">
        <span class="tweet-author">
            <a href="profile.php?username=<?php echo h($tweet['user']['screen_name']); ?>">
                <?php echo h($tweet['user']['name']); ?>
            </a>
        </span>
        <span class="tweet-username">
            @<?php echo h($tweet['user']['screen_name']); ?>
        </span>
        <span class="tweet-date">
            <?php echo formatDate($tweet['created_at']); ?>
        </span>
    </div>
    
    <div class="tweet-text" onclick="window.location.href='tweet.php?id=<?php echo h($tweet['id']); ?>';" style="cursor: pointer;">
        <?php echo formatTweet($tweet['text']); ?>
    </div>
    
    <?php echo renderTweetImages($tweet); ?>
    
    <div class="tweet-actions">
        <?php if ($client->isLoggedIn()): ?>
        <a href="compose.php?reply=<?php echo h($tweet['id']); ?>&amp;to=<?php echo h($tweet['user']['screen_name']); ?>">Reply</a>
        <a href="action.php?retweet=<?php echo h($tweet['id']); ?>" onclick="return confirm('Retweet this?');">Retweet</a>
        <a href="action.php?favorite=<?php echo h($tweet['id']); ?>">
            <?php echo isset($tweet['favorited']) && $tweet['favorited'] ? 'Unfavorite' : 'Favorite'; ?>
        </a>
        
        <?php if ($isOwnProfile): ?>
        <a href="action.php?delete=<?php echo h($tweet['id']); ?>" onclick="return confirm('Delete this tweet?');" style="color: #d9534f;">Delete</a>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<div class="pagination">
    <?php if (!empty($tweets)): ?>
        <?php $lastTweet = end($tweets); ?>
        <a href="profile.php?username=<?php echo h($username); ?>&amp;max_id=<?php echo h($lastTweet['id']); ?>">Load More</a>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php endif; ?>

<?php include('footer.php'); ?>
