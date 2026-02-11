<?php
require_once('utils.php');

// Require authentication
requireLogin();

$client = getTwitterClient();
$pageTitle = "Home Timeline";

// Handle new tweets
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tweet'])) {
    $status = $_POST['status'];
    $mediaPath = null;
    
    // Uploading images
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = array('image/jpeg', 'image/png', 'image/gif');
        $fileType = $_FILES['image']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            // Create temp file
            $uploadDir = sys_get_temp_dir();
            $mediaPath = $uploadDir . '/' . uniqid('tweet_') . '_' . basename($_FILES['image']['name']);
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $mediaPath)) {
                setError("Failed to upload image.");
                $mediaPath = null;
            }
        } else {
            setError("Invalid image type. Only JPEG, PNG, and GIF are allowed.");
        }
    }
    
    if (empty($status)) {
        setError("Tweet cannot be empty.");
        if ($mediaPath) unlink($mediaPath);
    } else if (strlen($status) > 140) {
        // Twitter's character limit is 280 but use 140 just in case
        setError("Tweet is too long. Maximum 140 characters.");
        if ($mediaPath) unlink($mediaPath);
    } else {
        $result = $client->postTweet($status, $mediaPath);
        
        // We don't need temp file anymore, kill it
        if ($mediaPath && file_exists($mediaPath)) {
            unlink($mediaPath);
        }
        
        if ($result && !isset($result['errors'])) {
            $_SESSION['success'] = "Tweet posted successfully!";
            header("Location: index.php");
            exit;
        } else {
            $errorMsg = "Failed to post tweet.";
            if (isset($result['errors'][0]['message'])) {
                $errorMsg .= " " . $result['errors'][0]['message'];
            }
            setError($errorMsg);
        }
    }
}

// Get timeline
$max_id = isset($_GET['max_id']) ? $_GET['max_id'] : null;
$tweets = $client->getHomeTimeline(20, $max_id);

if (!is_array($tweets)) {
    $tweets = array();
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

<div class="main-content">
    <div class="box" style="margin-bottom: 20px;">
        <div class="box-title">What's happening?</div>
        <div class="box-content">
            <form method="post" action="index.php" enctype="multipart/form-data">
                <div class="form-group">
                    <textarea name="status" id="tweet-text" maxlength="140" onkeyup="updateCharCount()"></textarea>
                    <div class="char-counter" id="char-count">140</div>
                </div>
                <div class="form-group">
                    <label for="image">Add Image:</label>
                    <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif" />
                </div>
                <div class="form-group">
                    <input type="submit" name="tweet" value="Tweet" class="btn btn-primary" />
                </div>
            </form>
        </div>
    </div>
    
    <h3>Home Timeline</h3>
    
    <?php if (empty($tweets)): ?>
    <div class="alert alert-info">
        No tweets to display. Follow some users or post your first tweet!
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
            <a href="compose.php?reply=<?php echo h($tweet['id']); ?>&amp;to=<?php echo h($tweet['user']['screen_name']); ?>">Reply</a>
            <a href="action.php?retweet=<?php echo h($tweet['id']); ?>" onclick="return confirm('Retweet this?');">Retweet</a>
            <a href="action.php?favorite=<?php echo h($tweet['id']); ?>">
                <?php echo isset($tweet['favorited']) && $tweet['favorited'] ? 'Unfavorite' : 'Favorite'; ?>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    
    <div class="pagination">
        <?php if (!empty($tweets)): ?>
            <?php $lastTweet = end($tweets); ?>
            <a href="index.php?max_id=<?php echo h($lastTweet['id']); ?>">Load More</a>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>
</div>

<script type="text/javascript">
function updateCharCount() {
    var text = document.getElementById('tweet-text').value;
    var count = 140 - text.length;
    var counter = document.getElementById('char-count');
    counter.innerHTML = count;
    
    if (count < 0) {
        counter.className = 'char-counter error';
    } else if (count < 20) {
        counter.className = 'char-counter warning';
    } else {
        counter.className = 'char-counter';
    }
}
</script>

<?php include('footer.php'); ?>
