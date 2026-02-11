<?php
require_once('utils.php');

$pageTitle = "Login - Steel";

$client = getTwitterClient();

// if user is logged in already go back home
if ($client->isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// login form handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $apiUrl = $_POST['api_url'];
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password) || empty($apiUrl)) {
        setError("Please fill in all fields.");
    } else {
        $result = $client->authenticate($username, $password, $apiUrl, $remember);
        
        if ($result === true) {
            header("Location: index.php");
            exit;
        } else if (is_array($result) && isset($result['errors'])) {
            // account for errors (user can't type for shit OR api is down)
            $errorMessage = "Login failed: ";
            if (isset($result['errors'][0]['message'])) {
                $errorMessage .= $result['errors'][0]['message'];
            } else {
                $errorMessage .= "Invalid credentials or API URL.";
            }
            setError($errorMessage);
        } else {
            setError("Login failed. Please check your credentials and API URL.");
        }
    }
}

$error = getError();

include('header.php');
?>

<div class="login-box">
    <h2>Login to Steel</h2>
    
    <?php if (!empty($error)): ?>
    <div class="alert alert-error">
        <?php echo h($error); ?>
    </div>
    <?php endif; ?>
    
    <form method="post" action="login.php">
        <div class="form-group">
            <label for="api_url">API URL:</label>
            <input type="text" id="api_url" name="api_url" value="<?php echo isset($_POST['api_url']) ? h($_POST['api_url']) : ''; ?>" />
            <div class="help-text">Enter the Twitter API base URL</div>
        </div>
        
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? h($_POST['username']) : ''; ?>" />
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" />
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="remember" <?php echo (isset($_POST['remember']) ? 'checked="checked"' : ''); ?> />
                Remember me
            </label>
        </div>
        
        <div class="form-group">
            <input type="submit" name="login" value="Login" class="btn btn-primary" />
        </div>
    </form>
</div>

<?php include('footer.php'); ?>

