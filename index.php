<?php
// EXIT CODES: -2 for DB connection issue

include 'config.php';
header("Access-Control-Allow-Origin: $cors_header");

function fetchEmailsOrdered($db, $postid) {
    include 'config.php';

    $statement = $db->prepare('SELECT date,email_file FROM comments
        WHERE (post_id = :post_id) AND validated = 1
        ORDER BY date DESC');
    $statement->execute(array(':post_id' => $postid));

    while ($entry = $statement->fetch(PDO::FETCH_ASSOC)) {
        $file = fopen($entry['email_file'], 'r');
        if (!$file) continue;

        $email = fread($file, $max_mail_size);
        fclose($file);

        // Split the email into headers and body
        $arr = explode("\r\n\r\n", $email, 2);

        $headers = $arr[0];
        $body = preg_replace('/\r\n/', "<br>\n",htmlspecialchars($arr[1]));

        // Try to determine the sender, otherwise use fallback value
        $match = '';
        $from = 'Anonymous';
        if(preg_match('/\nFrom: ([^<\n]+)( )?(<.+>)?/', $headers, $match) || true) {
            $from = htmlspecialchars($match[1]);
        }

        $date = DateTime::createFromFormat('U', $entry['date']);
        $comment = array();
        
        yield array("from" => $from, "date" => $date, "content" => $body);
    }
}

try {
    $db = new PDO("sqlite:$db_path");
} catch(PDOException $e) {
    exit -2;
}


$id = $_GET["id"];

$entries_printed = false;

$generator = fetchEmailsOrdered($db, $id);
foreach($generator as $comment) {
    echo '<div class="comment">' . "\n";
    echo '  <span class="author">' . $comment['from'] . '</span>' . "\n";
    echo '  <time>' . $comment['date']->format('d M y, H:i') . '</time>' . "\n";
    echo '  <span class="content">' . $comment['content'] . '</span>';
    echo '</div>' . "\n";
    $entries_printed = true;
}

if (!$entries_printed) {
    echo '<b>No comments</b>' . "\n";
}
?>
