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
    
    // Handle chunked uploads (from JavaScript)
    if (isset($_POST['chunked_upload_id']) && isset($_POST['chunked_file_name'])) {
        $uploadDir = sys_get_temp_dir() . '/chunked_uploads';
        $uploadId = $_POST['chunked_upload_id'];
        $fileName = $_POST['chunked_file_name'];
        $mediaPath = $uploadDir . '/' . $uploadId . '_final_' . $fileName;
        
        if (!file_exists($mediaPath)) {
            setError("Chunked upload file not found.");
            $mediaPath = null;
        }
    }
    // Traditional file upload
    else if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = array(
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv'
        );
        $fileType = $_FILES['media']['type'];
        
        // Additional validation for file extension
        $fileName = $_FILES['media']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = array('jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi', 'wmv');
        
        if (in_array($fileType, $allowedTypes) && in_array($fileExt, $allowedExts)) {
            // Check file size (max 15MB for videos, 5MB for images)
            $maxSize = (strpos($fileType, 'video/') === 0) ? 15 * 1024 * 1024 : 5 * 1024 * 1024;
            
            if ($_FILES['media']['size'] > $maxSize) {
                $sizeMB = round($maxSize / (1024 * 1024));
                setError("File is too large. Maximum size is {$sizeMB}MB.");
            } else {
                // Create temp file
                $uploadDir = sys_get_temp_dir();
                $mediaPath = $uploadDir . '/' . uniqid('tweet_') . '_' . basename($_FILES['media']['name']);
                
                if (!move_uploaded_file($_FILES['media']['tmp_name'], $mediaPath)) {
                    setError("Failed to upload media file.");
                    $mediaPath = null;
                }
            }
        } else {
            setError("Invalid file type. Supported formats: JPEG, PNG, GIF, MP4, MOV, AVI, WMV.");
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
                    <label for="media">Add Media:</label>
                    <input type="file" name="media" id="media" accept="image/jpeg,image/jpg,image/png,image/gif,video/mp4,video/quicktime,video/x-msvideo" />
                    <small style="display: block; margin-top: 5px; color: #666;">
                        Images (JPEG, PNG, GIF) up to 5MB, Videos (MP4, MOV, AVI) up to 15MB
                    </small>
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
            <img src="<?php echo h(forceHttpsImage($tweet['user']['profile_image_url'])); ?>" alt="Avatar">
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
            <?php echo formatTweet(getTweetText($tweet)); ?>
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
