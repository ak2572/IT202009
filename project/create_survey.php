<?php require_once(__DIR__ . "/partials/nav.php"); ?>
<?php
if (!is_logged_in()) {
    //this will redirect to login and kill the rest of this script (prevent it from executing)
    flash("You don't have permission to access this page");
    die(header("Location: login.php"));
}
?>
<?php
if (isset($_POST["submit"])) {
    //echo "<pre>" . var_export($_POST, true) . "</pre>";
    //TODO this isn't going to be the best way to parse the form, and probably not the best form setup
    //so just use this as an example rather than what you should do.
    //this is based off of naming conversions used in Python WTForms (I like to try to see if I can get some
    //php equivalents implemented (to a very, very basic degree))
    $min_check = true; // will remain true if a survey has at least one question, and if each question has at least two answers
    $min_check_message = "";
    $title = $_POST["title"];
    if (strlen($title) > 0) {
        //make sure we have a title
        $description = $_POST["description"];
        $category = $_POST["category"];
        $visibility = $_POST["visibility"];
        //TODO here's where it gets a tad hacky and there are better ways to do it.
        $index = 0;
        $assumed_max_questions = 100;//this isn't a realistic limit, it's just to ensure
        $questions = [];
        //we don't get stuck in an infinite loop since while(true) is dangerous if not handled appropriately
        for ($index = 0; $index < $assumed_max_questions; $index++) {
            $question = false;
            if (isset($_POST["question_$index"])) {
                $question = $_POST["question_$index"];
            }
            if ($question) {
                $assumed_max_answers = 100;//same as $assumed_max_questions (var sits here so it resets each loop)
                $answers = [];//reset array each loop
                for ($i = 0; $i < $assumed_max_answers; $i++) {
                    $check = "" . join(["question_", $index, "_answer_", $i]);
                    // error_log("Checking for pattern $check");
                    $answer = false;
                    if (isset($_POST[$check])) {
                        $answer = $_POST[$check];
                    }
                    if ($answer) {
                        array_push($answers, ["answer" => $answer]);
                    }
                    else {
                        if($i < 2) {
                            echo $i;
                            $min_check = false;
                            $min_check_message = "Each question must have at least two answer choices";
                            goto if_min_check_false;
                        }
                        //we can break this loop since we have no more answers to parse
                        break;
                    }
                }
                array_push($questions, [
                    "question" => $question,
                    "visibility" => $visibility,
                    "answers" => $answers
                ]);
            }
            else {
                if($i < 1) {
                    $min_check = false;
                    $min_check_message = "The survey must have at least one question";
                    goto if_min_check_false;
                }
                //we don't have anymore questions in post, early terminate the loop
                break;
            }
        }
        $survey = [
            "title" => $title,
            "description" => $description,
            "category" => $category,
            "questions" => $questions //contains answers
        ];
        save_survey($survey);
        
    }
    else {
        flash("A survey title must be provided");
    }

    if_min_check_false:
    if(!$min_check) {
        flash($min_check_message);
    }

}

function save_survey($survey) {
    //this could be moved to a helper file if it's used elsewhere too
    //since I don't plan on implementing edit survey at this time, I'll keep it here
    $db = getDB();
    $hadError = false;
    //insert survey
    $stmt = $db->prepare("INSERT INTO Surveys (title, description, category, visibility, user_id) VALUES (:title, :description, :category, :visibility, :user_id)");
    $r = $stmt->execute([
        ":title" => $survey["title"],
        ":description" => $survey["description"],
        ":category" => $survey["category"],
        ":visibility" => $survey["max_attempts"],
        ":user_id" => get_user_id()
    ]);
    if ($r) {//insert questions
        $survey_id = $db->lastInsertId();
        //we could bulk insert questions, but it'll be a bit complex to get the ids back out
        //for use in the Answers insert, so instead I'll do a less efficient route and insert a question and its
        //answers one at a time.
        //loop over each question, insert the question and respective answers
        foreach ($survey["questions"] as $questionIndex => $q) {
            $stmt = $db->prepare("INSERT INTO Questions (question, survey_id) VALUES (:q, :survey_id)");
            //echo "<pre>" .var_export($q, true) . "</pre>";
            $r = $stmt->execute([":q" => $q["question"], ":survey_id" => $survey_id]);
            if ($r) {//insert answers
                $question_id = $db->lastInsertId();
                $query = "INSERT INTO Answers (answer, question_id) VALUES ";
                $params = [];
                foreach ($q["answers"] as $answerIndex => $a) {
                    if ($answerIndex > 0) {
                        $query .= ",";
                    }
                    $query .= "(:a$answerIndex, :qid)";
                    $params[":a$answerIndex"] = $a["answer"];
                }
                //only need to map this once since it's the same for this batch of answers
                $params[":qid"] = $question_id;
                //echo "<br>Answer<br>";
                //echo $query;
                //echo var_export($params, true);
                $stmt = $db->prepare($query);
                $r = $stmt->execute($params);
                if (!$r) {
                    $hadError = true;
                    flash("Error creating answers: " . var_export($stmt->errorInfo(), true));
                }
            }
            else {
                $hadError = true;
                flash("Error creating questions: " . var_export($stmt->errorInfo(), true));
            }
        }
    }
    else {
        $hadError = true;
        flash("Error creating survey: " . var_export($stmt->errorInfo(), true),);
    }
    if (!$hadError) {
        flash("Successfully created Survey: " . $survey["title"],);
        //redirect to prevent duplicate form submission
        die(header("Location: create_survey.php"));
    }
}

?>
<div class="container-fluid">
    <h3>Create Survey</h3>
    <form method="POST">
        <div class="form-group">
            <label for="">Title</label>
            <input class="form-control" type="text" id="title" name="title" required maxlength="45"/>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" type="text" id="description" name="description"></textarea>
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <input class="form-control" type="text" id="category" name="category" maxlength="15"/>
        </div>
        <div class="form-group">
            <label for="visibility">Visibility</label>
            <select class="form-control" name="visibility" id="visibility" required>
                <option value="0">Draft</option>
                <option value="1">Private</option>
                <option value="2">Public</option>
            </select>
        </div>

        <div class="list-group" i>
            <div class="list-group-item">
                <div class="form-group">
                    <label for="question_0">Question</label>
                    <input class="form-control" type="text" id="question_0" name="question_0" required maxlength="100"/>
                </div>
                <button class="btn btn-danger" onclick="event.preventDefault(); deleteMe(this);">X</button>
                <div class="list-group" cop>
                    <div class="list-group-item">
                        <div class="form-group" ied>
                            <label for="question_0_answer_0">Answer</label>
                            <input class="form-control" type="text" id="question_0_answer_0" name="question_0_answer_0" required maxlength="100"/>
                        </div>
                        <button class="btn btn-danger" onclick="event.preventDefault(); deleteMe(this);">X</button>
                    </div>
                </div>
                <button class="btn btn-secondary" onclick="event.preventDefault(); cloneThis(this);" from>Add Answer</button>
            </div>
        </div>

        <button class="btn btn-secondary git" onclick="event.preventDefault(); cloneThis(this);">Add Question</button>

        <div class="form-group">
            <input type="submit" name="submit" class="btn btn-primary" value="Create Survey"/>
        </div>

    </form>
</div>
<script>
    function update_names_and_ids($ele) {
        let $lis = $ele.children(".list-group-item");
        //loop over all list-group-items of list-group
        $lis.each(function (index, item) {
            let $fg = $(item).find(".form-group");
            let liIndex = index;
            //loop over all form-groups inside list-group-item
            $fg.each(function (index, item) {
                let $label = $(item).find("label");
                if (typeof ($label) !== 'undefined' && $label != null) {
                    let forAttr = $label.attr("for");
                    let pieces = forAttr.split('_');
                    //Note this is different since it's a plain array not a jquery object
                    pieces.forEach(function (item, index) {
                        if (!isNaN(item)) {
                            pieces[index] = liIndex;
                        }
                    });
                    let updatedRef = pieces.join("_");
                    $label.attr("for", updatedRef);
                    let $input = $(item).find(":input");
                    if (typeof ($input) !== 'undefined' && $input != null) {
                        $input.attr("id", updatedRef);
                        $input.attr("title", updatedRef);
                    }
                }
            });
            //See if we have any children list-groups (this would be our answers)
            let $child_lg = $(item).find(".list-group");//probably doesn't need an each loop but it's fine
            $child_lg.each(function (index, item) {
                let $childlis = $(item).find(".list-group-item");
                $childlis.each(function (index, item) {
                    let $fg = $(item).find(".form-group");
                    let childLiIndex = index;
                    //loop over all form-groups inside list-group-item
                    $fg.each(function (index, item) {
                        let $label = $(item).find("label");
                        if (typeof ($label) !== 'undefined' && $label != null) {
                            let forAttr = $label.attr("for");
                            let pieces = forAttr.split('_');
                            //Note this is different since it's a plain array not a jquery object
                            let lastIndex = -1;
                            pieces.forEach(function (item, index) {
                                if (!isNaN(item)) {
                                    //example: question_#_answer_#
                                    if (lastIndex == -1) {
                                        //example: question_#
                                        pieces[index] = liIndex;//replace the first # with the parent outer loop index
                                        lastIndex = index;
                                    } else {
                                        //example: question_#_answer_#
                                        pieces[index] = childLiIndex;//replace the second # with the child loop index
                                    }
                                }
                            });
                            let updatedRef = pieces.join("_");
                            $label.attr("for", updatedRef);
                            let $input = $(item).find(":input");
                            if (typeof ($input) !== 'undefined' && $input != null) {
                                $input.attr("id", updatedRef);
                                $input.attr("title", updatedRef);
                            }
                        }
                    });
                });
            });
        });
    }

    function cloneThis(ele) {
        let $lg = $(ele).siblings(".list-group");
        let $li = $lg.find(".list-group-item:first");
        let $clone = $li.clone();
        $lg.append($clone);
        update_names_and_ids($(".list-group:first"));
    }

    function deleteMe(ele) {
        let $li = $(ele).closest(".list-group-item");
        let $lg = $li.closest(".list-group");
        let $children = $lg.children(".list-group-item");
        if ($children.length > 1) {
            $li.remove();
            update_names_and_ids($(".list-group:first"));
        }
    }
</script>
<?php require(__DIR__ . "/partials/flash.php");