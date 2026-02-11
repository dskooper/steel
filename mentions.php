<?php
require_once('utils.php');

// Require authentication
requireLogin();

$client = getTwitterClient();
$pageTitle = "Mentions";

// Get timeline
$max_id = isset($_GET['max_id']) ? $_GET['max_id'] : null;
$mentions = $client->getMentionsTimeline(20, $max_id);

if (!is_array($mentions)) {
    $mentions = array();
}

$error = getError();
$success = '';
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

include('header.php');
?>

<?php if (!empty($error)): ?>
<div class="alert alert-error">
    <?php echo h($error); ?>
</div>
<?php endif; ?>

<?php if (!empty($success)): ?>
<div class="alert alert-success">
    <?php echo h($success); ?>
</div>
<?php endif; ?>

<h3>Mentions</h3>

<?php if (empty($mentions)): ?>
<div class="alert alert-info">
    No mentions to display.
</div>
<?php else: ?>

<?php foreach ($mentions as $tweet): ?>
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
        <a href="compose.php?reply=<?php echo h($tweet['id']); ?>&amp;to=<?php echo h($tweet['user']['screen_name']); ?>">Reply</a>
        <a href="action.php?retweet=<?php echo h($tweet['id']); ?>" onclick="return confirm('Retweet this?');">Retweet</a>
        <a href="action.php?favorite=<?php echo h($tweet['id']); ?>">
            <?php echo isset($tweet['favorited']) && $tweet['favorited'] ? 'Unfavorite' : 'Favorite'; ?>
        </a>
    </div>
</div>
<?php endforeach; ?>

<div class="pagination">
    <?php if (!empty($mentions)): ?>
        <?php $lastTweet = end($mentions); ?>
        <a href="mentions.php?max_id=<?php echo h($lastTweet['id']); ?>">Load More</a>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php include('footer.php'); ?>
