<?php
require_once('utils.php');

$pageTitle = "Login - Steel-1.1";

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

    // input validation
    // all we should check for (apiUrl) if its in the format http(s)://domain.tld WITHOUT /
    if (!preg_match('/^https?:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}(\/.*)?$/', $apiUrl)) {
        setError("Please enter a valid API URL (must start with http:// or https:// and be a valid domain).");
    } else if (substr($apiUrl, -1) === '/') {
        setError("API URL should not end with a slash (/). Please remove it.");
    } else if (strlen($username) > 128 || strlen($password) > 128 || strlen($apiUrl) > 128) {
        setError("Input values are too long. Please limit to 128 characters.");
    } else if (preg_match('/[^\w\-\.@]/', $username) || preg_match('/[^\w\-\.@\/:]/', $apiUrl) || strpos($apiUrl, ' ') !== false) {
        setError("Input contains invalid characters.");
    } else if (empty($username) || empty($password) || empty($apiUrl)) {
        setError("Please fill in all fields.");
    } else {
        // First, check if the API supports v1.1 endpoints
        $versionCheck = $client->checkApiVersion($apiUrl);
        
        if (!$versionCheck['success']) {
            setError($versionCheck['error']);
        } else {
            $result = $client->authenticate($username, $password, $apiUrl, $remember);
            
            if ($result === true) {
                header("Location: index.php");
                exit;
            } else {
                $apiError = $result['errors'][0]['message'];

                // Detect HTTP status codes properly
                if (preg_match('/HTTP Code (\d+)/', $apiError, $matches)) {
                    $statusCode = (int)$matches[1];
                
                    if ($statusCode === 401 || $statusCode === 403) {
                        $errorMessage = "Incorrect username or password.";
                    } elseif ($statusCode >= 500) {
                        $errorMessage = "The API server is unreachable or down. Make sure you typed the URL correctly.";
                    } else {
                        $errorMessage .= $apiError;
                    }
                
                } elseif (strpos($apiError, 'cURL Error') !== false) {
                    $errorMessage = "The API server is unreachable or down.";
                } else {
                    $errorMessage .= $apiError;
                }
                setError($errorMessage);
            }
        }
    }
}

$error = getError();

include('header.php');
?>

<div class="login-box">
    <h2>Login to Steel-1.1</h2>

    <p>
        This version of Steel is for the v1.1 REST API <b>only.</b> <br>
        For the v1 REST API version click <a href="../steel/login.php">here</a>. <br>
    </p>
    
    <br>
    
    <?php if (!empty($error)): ?>
    <div class="alert alert-error">
        <?php echo h($error); ?>
    </div>
    <?php endif; ?>
    <br>
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

