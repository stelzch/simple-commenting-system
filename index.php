<?php

// EXIT CODES: -2 for DB connection issue
include 'config.php';

try {
    $db = new PDO("sqlite:$db_path");
} catch(PDOException $e) {
    exit -2;
}

if ($show_by_default) {
$statement = $db->prepare('SELECT date,email_file FROM comments
    WHERE (post_id = :post_id)
    ORDER BY date DESC');
} else {
$statement = $db->prepare('SELECT date,email_file FROM comments
    WHERE (post_id = :post_id) AND validated = 1
    ORDER BY date DESC');
}

//$id = $_GET['id'];
$id = 'ApostID';
$statement->execute(array(':post_id' => $id));


$entries_printed = false;
while ($entry = $statement->fetch(PDO::FETCH_ASSOC)) {
    $file = fopen($entry['email_file'], 'r');
    if (!$file) continue;

    $email = fread($file, $max_mail_size);
    fclose($file);

    $arr = explode("\r\n\r\n", $email, 2);

    $headers = $arr[0];
    $body = preg_replace('/\r\n/', "<br>\n",htmlspecialchars($arr[1]));

    $match = '';
    $from = 'Anonymous';
    if(preg_match('/^From: (.+) (<.+>)?$/', $headers)) {
        $from = htmlspecialchars($match[0]);
    }

    $date = DateTime::createFromFormat('U', $entry['date']);

    echo '<div class="comment">' . "\n";
    echo '  <span class="author">' . $from . '</span>' . "\n";
    echo '  <time>' . $date->format('d M y, G:i') . '</time>' . "\n";
    echo '  <span class="content">' . $body . '</span>';
    echo '</div>' . "\n";
    $entries_printed = true;
}

if (!$entries_printed) {
    echo '<b>No comments</b>' . "\n";
}

?>
