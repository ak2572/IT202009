<link rel="stylesheet" href = "static/css/styles.css">
<?php
//we'll be including this on most/all pages so it's a good place to include anything else we want on those pages
require_once(__DIR__ . "/../lib/helpers.php");
?>
<nav>
    <ul class = "nav">
        <li><a href="<?php echo get_url("home.php"); ?>">Home</a></li>
        <?php if (!is_logged_in()): ?>
            <li><a href="<?php echo get_url("login.php"); ?>">Login</a></li>
            <li><a href="<?php echo get_url("register.php"); ?>">Register</a></li>
        <?php endif; ?>
        <?php if(has_role("Admin")): ?>
            <li><a href="<?php echo get_url("test/test_create_survey.php"); ?>">Create Survey</a></li>
            <li><a href="<?php echo get_url("test/test_list_survey.php"); ?>">View Surveys</a></li>
            <li><a href="<?php echo get_url("test/test_create_question.php"); ?>">Create Question</a></li>
            <li><a href="<?php echo get_url("test/test_list_questions.php"); ?>">View Questions</a></li>
        <?php endif; ?>
        <?php if (is_logged_in()): ?>
            <li><a href="<?php echo get_url("profile.php"); ?>">Profile</a></li>
            <li><a href="<?php echo get_url("logout.php"); ?>">Logout</a></li>
        <?php endif; ?>
    </ul>
<nav>
