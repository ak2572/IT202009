<?php require_once(__DIR__ . "/partials/nav.php"); ?>
<?php
if(!is_logged_in()) {
    //this will redirect to login and kill the rest of this script (prevent it from executing)
    flash("You don't have permission to access this page");
    die(header("Location: login.php"));
}
?>
<?php
//we'll put this at the top so both php block have access to it
if (isset($_GET["id"])) {
    $id = $_GET["id"];
}
?>
<?php
//fetching
$result = [];
if (isset($id)) {
    $db = getDB();
    $stmt = $db->prepare("SELECT title, description, category, visibility, user_id, username FROM Surveys JOIN Users ON Surveys.user_id = Users.id WHERE Surveys.id = :id");
    $r = $stmt->execute([":id" => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        $e = $stmt->errorInfo();
        flash($e[2]);
    }
    $survey_user_id = $result["user_id"];
    $user_id = get_user_id();
    $visibility = $result["visibility"];
    if($visibility == 0 && $user_id != $survey_user_id) {
        flash("You don't have permission to access this page");
        die(header("Location: public_surveys.php"));
    }
}
?>
<?php if (isset($result) && !empty($result)): ?>
    <div class="card" style="width: 25rem;">
        <div class="card-body">
            <div class="font-weight-bold">Title</div>
            <div><?php safer_echo($result["title"]); ?></div>
        </div>
    </div>
    <div class="card" style="width: 25rem;">
        <div class="card-body">
            <div class="font-weight-bold">Description</div>
            <div><?php safer_echo($result["description"]); ?></div>
        </div>
    </div>
    <div class="card" style="width: 25rem;">
        <div class="card-body">
            <div class="font-weight-bold">Category</div>
            <div><?php safer_echo($result["category"]); ?></div>
        </div>
    </div>
    <div class="card" style="width: 25rem;">
        <div class="card-body">
            <div class="font-weight-bold">Visibility</div>
            <div><?php get_visibility($result["visibility"]); ?></div>
        </div>
    </div>
    <div class="card" style="width: 25rem;">
        <div class="card-body">
            <div class="font-weight-bold">Created By</div>
            <div><?php safer_echo($result["username"]); ?></div>
        </div>
    </div>
<?php else: ?>
    <p>The survey ID could not be found</p>
<?php endif; ?>
<?php require(__DIR__ . "/partials/flash.php"); ?>
