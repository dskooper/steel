<?php
require_once('utils.php');

$client = getTwitterClient();
$pageTitle = "Search Users";

// Get the search query
$query = isset($_GET['q']) ? $_GET['q'] : '';
$userResults = array();

if (!empty($query)) {
    $userResults = $client->searchUsers($query, 20);
    if (!is_array($userResults)) {
        $userResults = array();
    }
}

include('header.php');
?>

<h3>Search Users</h3>

<div class="box" style="margin-bottom: 20px;">
    <div class="box-content">
        <form method="get" action="search.php">
            <div class="form-group">
                <label for="search_query">Search for users:</label>
                <input type="text" id="search_query" name="q" value="<?php echo h($query); ?>" style="width: 70%;" />
                <input type="submit" value="Search" class="btn btn-primary" />
            </div>
        </form>
    </div>
</div>

<?php if (empty($query)): ?>
<div class="alert alert-info">
    Enter a username or name to search for users.
</div>
<?php else: ?>

<?php if (empty($userResults)): ?>
<div class="alert alert-info">
    No users found matching your search.
</div>
<?php else: ?>

<?php foreach ($userResults as $user): ?>
    <div class="user-item clearfix">
        <div class="user-avatar">
            <?php if (isset($user['profile_image_url'])): ?>
            <img src="<?php echo h($user['profile_image_url']); ?>" alt="Avatar">
            <?php else: ?>
            <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Avatar">
            <?php endif; ?>
        </div>
        
        <div style="margin-left: 45px;">
            <div class="user-name">
                <a href="profile.php?username=<?php echo h($user['screen_name']); ?>">
                    <?php echo h($user['name']); ?>
                </a>
            </div>
            <div class="user-username">
                @<?php echo h($user['screen_name']); ?>
            </div>
            <?php if (isset($user['description']) && !empty($user['description'])): ?>
            <div style="font-size: 11px; color: #657786; margin-top: 3px;">
                <?php echo h(substr($user['description'], 0, 100)); ?>
                <?php echo strlen($user['description']) > 100 ? '...' : ''; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php endif; ?>
    
<?php endif; ?>

<?php include('footer.php'); ?>
