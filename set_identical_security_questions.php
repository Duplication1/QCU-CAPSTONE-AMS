<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/model/Database.php';

$isCli = php_sapi_name() === 'cli';

$question1 = 'What is your favorite food?';
$answer1 = 'defaultanswer1';
$question2 = 'What is your favorite movie?';
$answer2 = 'defaultanswer2';
$onlyMissing = false;

if ($isCli) {
    $options = getopt('', ['q1::', 'a1::', 'q2::', 'a2::', 'only-missing']);

    if (!empty($options['q1'])) {
        $question1 = trim($options['q1']);
    }
    if (!empty($options['a1'])) {
        $answer1 = trim($options['a1']);
    }
    if (!empty($options['q2'])) {
        $question2 = trim($options['q2']);
    }
    if (!empty($options['a2'])) {
        $answer2 = trim($options['a2']);
    }
    if (isset($options['only-missing'])) {
        $onlyMissing = true;
    }
} else {
    if (isset($_GET['q1']) && trim($_GET['q1']) !== '') {
        $question1 = trim($_GET['q1']);
    }
    if (isset($_GET['a1']) && trim($_GET['a1']) !== '') {
        $answer1 = trim($_GET['a1']);
    }
    if (isset($_GET['q2']) && trim($_GET['q2']) !== '') {
        $question2 = trim($_GET['q2']);
    }
    if (isset($_GET['a2']) && trim($_GET['a2']) !== '') {
        $answer2 = trim($_GET['a2']);
    }
    if (isset($_GET['only_missing']) && $_GET['only_missing'] === '1') {
        $onlyMissing = true;
    }
}

if ($question1 === '' || $question2 === '' || $answer1 === '' || $answer2 === '') {
    $msg = 'Questions and answers must not be empty.';
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
    } else {
        echo $msg;
    }
    exit(1);
}

if (strcasecmp($question1, $question2) === 0) {
    $msg = 'Question 1 and Question 2 must be different.';
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
    } else {
        echo $msg;
    }
    exit(1);
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    $hashedAnswer1 = password_hash(strtolower($answer1), PASSWORD_DEFAULT);
    $hashedAnswer2 = password_hash(strtolower($answer2), PASSWORD_DEFAULT);

    $sql = "UPDATE users
            SET security_question_1 = ?,
                security_answer_1 = ?,
                security_question_2 = ?,
                security_answer_2 = ?,
                updated_at = NOW()";

    if ($onlyMissing) {
        $sql .= " WHERE security_question_1 IS NULL OR security_question_1 = ''
                  OR security_answer_1 IS NULL OR security_answer_1 = ''
                  OR security_question_2 IS NULL OR security_question_2 = ''
                  OR security_answer_2 IS NULL OR security_answer_2 = ''";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([$question1, $hashedAnswer1, $question2, $hashedAnswer2]);
    $affected = $stmt->rowCount();

    $result = "Done. Updated {$affected} user(s).";
    if ($isCli) {
        echo $result . PHP_EOL;
    } else {
        echo nl2br(htmlspecialchars($result, ENT_QUOTES, 'UTF-8'));
    }
} catch (Exception $e) {
    $msg = 'Error: ' . $e->getMessage();
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
    } else {
        echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
    }
    exit(1);
}
