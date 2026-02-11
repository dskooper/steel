<?php
require_once('utils.php');

// Require authentication
requireLogin();

$client = getTwitterClient();
$pageTitle = "Compose";

// Handle parameters
$replyTo = isset($_GET['to']) ? $_GET['to'] : null;
$replyId = isset($_GET['reply']) ? $_GET['reply'] : null;
$defaultText = '';

if ($replyTo) {
    $defaultText = '@' . $replyTo . ' ';
}

// Handle DM parameters
$sendTo = isset($_GET['to']) ? $_GET['to'] : null;

// Form sumbissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tweet'])) {
        $status = $_POST['status'];
        $mediaPath = null;
        
        // Uploading images
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = array('image/jpeg', 'image/png', 'image/gif');
            $fileType = $_FILES['image']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
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
            // Most APIs still enforce 140 character limit, so we'll do it client-side
            // This also prevents us from uploading media if the tweet is too long
            setError("Tweet is too long. Maximum 140 characters.");
            if ($mediaPath) unlink($mediaPath);
        } else {
            if ($replyId) {
                $result = $client->replyToTweet($status, $replyId);
            } else {
                $result = $client->postTweet($status, $mediaPath);
            }
            
            // Clean up temporary file
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
    } else if (isset($_POST['send_dm'])) {
        $recipient = $_POST['recipient'];
        $message = $_POST['message'];
        
        if (empty($recipient) || empty($message)) {
            setError("Recipient and message cannot be empty.");
        } else {
            $result = $client->sendDirectMessage($recipient, $message);
            
            if ($result && !isset($result['errors'])) {
                $_SESSION['success'] = "Message sent successfully!";
                header("Location: messages.php");
                exit;
            } else {
                $errorMsg = "Failed to send message.";
                if (isset($result['errors'][0]['message'])) {
                    $errorMsg .= " " . $result['errors'][0]['message'];
                }
                setError($errorMsg);
            }
        }
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

<h3><?php echo $replyId ? 'Reply to Tweet' : 'Compose Tweet'; ?></h3>

<div class="box">
    <div class="box-content">
        <form method="post" action="compose.php<?php echo $replyId ? '?reply=' . h($replyId) . '&amp;to=' . h($replyTo) : ''; ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="status">Your Tweet:</label>
                <textarea name="status" id="tweet-text" maxlength="140" onkeyup="updateCharCount()"><?php echo h($defaultText); ?></textarea>
                <div class="char-counter" id="char-count">140</div>
            </div>
            <div class="form-group">
                <label for="image">Add Image (optional):</label>
                <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif" />
            </div>
            <div class="form-group">
                <input type="submit" name="tweet" value="Tweet" class="btn btn-primary" />
                <a href="index.php" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php if ($sendTo): ?>
<h3>Send Direct Message</h3>

<div class="box">
    <div class="box-content">
        <form method="post" action="compose.php?to=<?php echo h($sendTo); ?>">
            <div class="form-group">
                <label for="recipient">To:</label>
                <input type="text" name="recipient" id="recipient" value="<?php echo h($sendTo); ?>" readonly="readonly" />
            </div>
            <div class="form-group">
                <label for="message">Message:</label>
                <textarea name="message" id="dm-text"></textarea>
            </div>
            <div class="form-group">
                <input type="submit" name="send_dm" value="Send Message" class="btn btn-primary" />
                <a href="profile.php?username=<?php echo h($sendTo); ?>" class="btn">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

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

// init character count
updateCharCount();
</script>

<?php include('footer.php'); ?>
