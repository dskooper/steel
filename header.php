<?php
if (!isset($pageTitle)) {
    $pageTitle = "Steel";
}

$client = getTwitterClient();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title><?php echo h($pageTitle); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Steel</h1>
            <?php if ($client->isLoggedIn()): ?>
            <div class="user-info">
                Logged in as: <strong>@<?php echo h($client->getScreenName()); ?></strong> | 
                <a href="logout.php">Logout</a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($client->isLoggedIn()): ?>
        <div class="nav">
            <a href="index.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'class="active"' : ''; ?>>Home</a>
            <a href="mentions.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'mentions.php') ? 'class="active"' : ''; ?>>Mentions</a>
            <a href="messages.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'messages.php') ? 'class="active"' : ''; ?>>Messages</a>
            <a href="search.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'search.php') ? 'class="active"' : ''; ?>>Search</a>
            <a href="profile.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php' && !isset($_GET['username'])) ? 'class="active"' : ''; ?>>Profile</a>
            <a href="settings.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'class="active"' : ''; ?>>Settings</a>
            <a href="about.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'class="active"' : ''; ?>>About</a>
        </div>
        <?php else: ?>
        <div class="nav">
            <a href="about.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'class="active"' : ''; ?>>About</a>
            <a href="login.php" <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'class="active"' : ''; ?>>Login</a>
        </div>
        <?php endif; ?>
        
        <div class="content">
