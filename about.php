<?php
require_once('utils.php');

$pageTitle = "About Steel-1.1";

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
    <h2>About Steel-1.1</h2>
    
    <div class="box">
        <div class="box-content">
            <p>
                Steel-1.1 is a lightweight web frontend for compatible implementations of Twitter's v1.1 REST API. <br>
                For the v1 REST API version click <a href="../steel/login.php">here</a>. <br>
                Designed to support classic IE, it is surprisingly simple while providing as many core features as possible.
            </p>

            <br>
            
            <h3>Implemented</h3>
            <ul style="margin-left: 20px; margin-bottom: 10px;">
                <li>Home timeline and mentions</li>
                <li>Create and reply to tweets with images</li>
                <li>Search for and follow/unfollow users</li>
                <li>Send and receive direct messages</li>
                <li>Manage your profile and account settings</li>
            </ul>

            <br>
            
            <h3>Usage</h3>
            <p>
                <?php if ($client->isLoggedIn()): ?>
                    You are currently logged in. Head <a href="index.php">home</a> to get started!
                <?php else: ?>
                    To use Steel, you'll need to <b>provide your own API root</b>, including the protocol. <br>
                    <small>For example: http://example-twitter-api.net</small> <br>
                    Steel uses xAuth for authentication, which means almost any API implementation should work.
                <?php endif; ?>
            </p>

            <br>
            
            <h3>Security</h3>
            <p>
                Steel is a client-side application and therefore does not store credentials on the server. <br>
                All authentication and API requests are handled directly between the client and the configured REST API. <br>
                Additionally, Steel's cookies are configured to expire after 5 days, after which you will need to log in again.
            </p>

            <br>
            
            <h3>Compatibility</h3>
            <p>
                <ul style="margin-left: 20px; margin-bottom: 10px;">
                    <li>Absolute minimum: Internet Explorer 6, Firefox 1</li>
                    <li>Recommended minimum: Internet Explorer 7, Firefox 2, Chrome 1</li>
                </ul>
                If any issues arise (or you would like to suggest features), please report them by <a href="https://kooper.online/info.html">contacting me</a>. <br>
                <i><b>This does not include browsers such as IE6, Firefox 1 or pre-WebKit NetFront. Issues with these will not be fixed.</i></b>
            </p>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
