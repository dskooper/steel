<?php
require_once('utils.php');

requireLogin();

$client = getTwitterClient();
$pageTitle = "Settings";

// Get current profile
$profile = $client->getUserProfile();

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $website = $_POST['website'];
    
    if (empty($name)) {
        setError("Name cannot be empty.");
    } else {
        $result = $client->updateProfile($name, $description, $location, $website);
        
        if ($result && !isset($result['errors'])) {
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: settings.php");
            exit;
        } else {
            $errorMsg = "Failed to update profile.";
            if (isset($result['errors'][0]['message'])) {
                $errorMsg .= " " . $result['errors'][0]['message'];
            }
            setError($errorMsg);
        }
    }
}

// Handle PFP updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_image'])) {
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $tmpPath = $_FILES['profile_image']['tmp_name'];
        $result = $client->updateProfileImage($tmpPath);
        
        if ($result && !isset($result['errors'])) {
            $_SESSION['success'] = "Profile image updated successfully!";
            header("Location: settings.php");
            exit;
        } else {
            $errorMsg = "Failed to update profile image.";
            if (isset($result['errors'][0]['message'])) {
                $errorMsg .= " " . $result['errors'][0]['message'];
            }
            setError($errorMsg);
        }
    } else {
        setError("Please select an image file.");
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

<h3>Profile Settings</h3>

<?php if ($profile === false || !$profile): ?>
<div class="alert alert-error">
    Unable to load profile information.
</div>
<?php else: ?>

<div class="box" style="margin-bottom: 20px;">
    <div class="box-title">Edit Profile</div>
    <div class="box-content">
        <form method="post" action="settings.php">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo h($profile['name']); ?>" />
            </div>
            
            <div class="form-group">
                <label for="description">Bio:</label>
                <textarea id="description" name="description"><?php echo h($profile['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" id="location" name="location" value="<?php echo isset($profile['location']) ? h($profile['location']) : ''; ?>" />
            </div>
            
            <div class="form-group">
                <label for="website">Website:</label>
                <input type="text" id="website" name="website" value="<?php echo isset($profile['url']) ? h($profile['url']) : ''; ?>" />
            </div>
            
            <div class="form-group">
                <input type="submit" name="update_profile" value="Update Profile" class="btn btn-primary" />
            </div>
        </form>
    </div>
</div>

<div class="box">
    <div class="box-title">Profile Image</div>
    <div class="box-content">
        <div style="margin-bottom: 10px;">
            <strong>Current Image:</strong><br>
            <?php if (isset($profile['profile_image_url'])): ?>
            <img src="<?php echo h($profile['profile_image_url']); ?>" alt="Profile" style="width: 73px; height: 73px; border: 1px solid #ccc; margin-top: 5px;">
            <?php endif; ?>
        </div>
        
        <form method="post" action="settings.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_image">Upload New Image:</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*" />
                <div class="help-text">Supported formats: JPG, PNG, GIF</div>
            </div>
            
            <div class="form-group">
                <input type="submit" name="update_image" value="Upload Image" class="btn btn-primary" />
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<?php include('footer.php'); ?>
