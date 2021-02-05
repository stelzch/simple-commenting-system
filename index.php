<?php

require 'config.php';
// EXIT CODES: -2 for DB connection issue

function fetchEmailsOrdered($db, $postid, $only_validated = 1) {
    include 'config.php';

    if ($only_validated) {
    $statement = $db->prepare('SELECT date,email_file FROM comments
        WHERE (post_id = :post_id) AND validated = 1
        ORDER BY date DESC');
    } else {
    $statement = $db->prepare('SELECT date,email_file FROM comments
        WHERE (post_id = :post_id)
        ORDER BY date DESC');
    }
    $statement->execute(array(':post_id' => $postid));

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
        
        yield array('from' => $from, 'date' => $date, 'content' => $body);
    }
}

try {
    $db = new PDO("sqlite:$db_path");
} catch(PDOException $e) {
    exit -2;
}


//$id = $_GET['id'];
$id = 'ApostID';


$entries_printed = false;

foreach(fetchEmailsOrdered($db, $id, $show_by_default) as $comment) {
    echo '<div class="comment">' . "\n";
    echo '  <span class="author">' . $comment['from'] . '</span>' . "\n";
    echo '  <time>' . $comment['date']->format('d M y, G:i') . '</time>' . "\n";
    echo '  <span class="content">' . $comment['content'] . '</span>';
    echo '</div>' . "\n";
    $entries_printed = true;
}

if (!$entries_printed) {
    echo '<b>No comments</b>' . "\n";
}
?>
