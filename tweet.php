<?php
require_once('utils.php');

$client = getTwitterClient();

// Get tweet ID from URL parameter
$tweetId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$tweetId) {
    header("Location: index.php");
    exit;
}

// Get the tweet
$tweet = $client->getTweet($tweetId);

if (!$tweet || isset($tweet['errors'])) {
    $pageTitle = "Tweet Not Found";
    include('header.php');
    ?>
    <div class="alert alert-error">
        Tweet not found or unable to load.
    </div>
    <?php
    include('footer.php');
    exit;
}

$pageTitle = "Tweet by @" . $tweet['user']['screen_name'];

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

<h3>Tweet</h3>

<div class="tweet clearfix" style="border: 1px solid #e1e8ed; padding: 15px; margin-bottom: 15px;">
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
    
    <div class="tweet-text" style="font-size: 14px; line-height: 1.6;">
        <?php echo formatTweet($tweet['text']); ?>
    </div>
    
    <?php echo renderTweetImages($tweet); ?>
    
    <?php if ($client->isLoggedIn()): ?>
    <div class="tweet-actions" style="margin-top: 10px;">
        <a href="compose.php?reply=<?php echo h($tweet['id']); ?>&amp;to=<?php echo h($tweet['user']['screen_name']); ?>">Reply</a>
        <a href="action.php?retweet=<?php echo h($tweet['id']); ?>" onclick="return confirm('Retweet this?');">Retweet</a>
        <a href="action.php?favorite=<?php echo h($tweet['id']); ?>">
            <?php echo isset($tweet['favorited']) && $tweet['favorited'] ? 'Unfavorite' : 'Favorite'; ?>
        </a>
        
        <?php if ($client->isLoggedIn() && $tweet['user']['screen_name'] === $client->getScreenName()): ?>
        <a href="action.php?delete=<?php echo h($tweet['id']); ?>" onclick="return confirm('Delete this tweet?');" style="color: #d9534f;">Delete</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div style="margin-top: 20px;">
    <h4>Tweet Details</h4>
    <div class="box">
        <div class="box-content">
            <div style="margin-bottom: 8px;">
                <strong>Posted:</strong> <?php echo date("F j, Y, g:i a", strtotime($tweet['created_at'])); ?>
            </div>
            
            <?php if (isset($tweet['retweet_count']) && $tweet['retweet_count'] > 0): ?>
            <div style="margin-bottom: 8px;">
                <strong>Retweets:</strong> <?php echo number_format($tweet['retweet_count']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($tweet['favorite_count']) && $tweet['favorite_count'] > 0): ?>
            <div style="margin-bottom: 8px;">
                <strong>Favorites:</strong> <?php echo number_format($tweet['favorite_count']); ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($tweet['source'])): ?>
            <div style="margin-bottom: 8px;">
                <strong>Via:</strong> <?php echo $tweet['source']; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="margin-top: 20px;">
    <a href="javascript:history.back();" class="btn">← Back</a>
    <a href="profile.php?username=<?php echo h($tweet['user']['screen_name']); ?>" class="btn">View Profile</a>
</div>

<?php include('footer.php'); ?>
