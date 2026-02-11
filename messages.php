<?php
require_once('utils.php');

// Require authentication
requireLogin();

$client = getTwitterClient();
$pageTitle = "Direct Messages";

// Get recipient if the tweet had one
$with = isset($_GET['with']) ? $_GET['with'] : null;

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient = $_POST['recipient'];
    $message = $_POST['message'];
    
    if (empty($recipient) || empty($message)) {
        setError("Recipient and message cannot be empty.");
    } else {
        $result = $client->sendDirectMessage($recipient, $message);
        
        if ($result && !isset($result['errors'])) {
            $_SESSION['success'] = "Message sent!";
            header("Location: messages.php?with=" . urlencode($recipient));
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

<?php if ($with): ?>
    <!-- Conversation view -->
    <h3>Conversation with @<?php echo h($with); ?></h3>
    
    <div style="margin-bottom: 10px;">
        <a href="messages.php" class="btn">← Back to Messages</a>
    </div>
    
    <?php
    $conversation = $client->getDirectMessagesWithUser($with);
    if (!is_array($conversation)) {
        $conversation = array();
    }
    ?>
    
    <div class="message-container">
        <?php if (empty($conversation)): ?>
        <div class="alert alert-info">
            No messages in this conversation yet.
        </div>
        <?php else: ?>
        
        <?php foreach ($conversation as $message): ?>
        <?php
        $isSent = ($message['sender_screen_name'] === $client->getScreenName());
        ?>
        <div class="message <?php echo $isSent ? 'message-sent' : 'message-received'; ?>">
            <div class="message-text">
                <strong><?php echo $isSent ? 'You' : '@' . h($message['sender_screen_name']); ?>:</strong>
                <?php echo h($message['text']); ?>
            </div>
            <div class="message-date">
                <?php echo formatDate($message['created_at']); ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>
    
    <div class="box">
        <div class="box-content">
            <form method="post" action="messages.php?with=<?php echo h($with); ?>">
                <div class="form-group">
                    <input type="hidden" name="recipient" value="<?php echo h($with); ?>" />
                    <label for="message_text">Type your message:</label>
                    <textarea id="message_text" name="message" style="width: 95%;"></textarea>
                </div>
                <div class="form-group">
                    <input type="submit" name="send_message" value="Send Message" class="btn btn-primary" />
                </div>
            </form>
        </div>
    </div>
    
<?php else: ?>
    <!-- Conversations list -->
    <h3>Direct Messages</h3>
    
    <div class="box" style="margin-bottom: 20px;">
        <div class="box-title">New Message</div>
        <div class="box-content">
            <form method="post" action="messages.php">
                <div class="form-group">
                    <label>Recipient: <input type="text" name="recipient" style="width: 30%;" /></label>
                    <label>Message: <input type="text" name="message" style="width: 35%;" /></label>
                    <input type="submit" name="send_message" value="Send" class="btn btn-primary" />
                </div>
            </form>
        </div>
    </div>
    
    <?php
    $conversations = $client->getDirectMessagesConversations();
    
    // in the event that the user has no DMs, the API returns a value such that
    // you get a fucked entry with no name and the date set to 1970
    // fix
    if (is_array($conversations)) {
        $conversations = array_filter($conversations, function($conv, $screenName) {
            return !empty($screenName) && 
                   isset($conv['user']) && 
                   isset($conv['user']['screen_name']) && 
                   !empty($conv['user']['screen_name']);
        }, ARRAY_FILTER_USE_BOTH);
    }
    ?>
    
    <?php if (empty($conversations)): ?>
    <div class="alert alert-info">
        No messages yet. Start a conversation!
    </div>
    <?php else: ?>
    
    <div class="conversation-list">
        <?php foreach ($conversations as $screenName => $conv): ?>
        <div class="conversation-item clearfix" onclick="window.location='messages.php?with=<?php echo h($screenName); ?>';">
            <div class="user-avatar">
                <?php if (isset($conv['user']['profile_image_url'])): ?>
                <img src="<?php echo h($conv['user']['profile_image_url']); ?>" alt="Avatar">
                <?php else: ?>
                <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Avatar">
                <?php endif; ?>
            </div>
            
            <div style="margin-left: 45px;">
                <div style="font-weight: bold;">
                    <?php echo h($conv['user']['name']); ?>
                    <span style="color: #657786; font-weight: normal; font-size: 11px;">
                        @<?php echo h($screenName); ?>
                    </span>
                </div>
                <div style="font-size: 11px; color: #657786; margin-top: 3px;">
                    <?php 
                    $preview = h($conv['message']['text']);
                    echo strlen($preview) > 60 ? substr($preview, 0, 60) . '...' : $preview;
                    ?>
                </div>
                <div style="font-size: 10px; color: #8899a6; margin-top: 2px;">
                    <?php echo formatDate($conv['created_at']); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
    
<?php endif; ?>

<?php include('footer.php'); ?>
