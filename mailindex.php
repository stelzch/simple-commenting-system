<?php
include 'config.php';

define('SQL_CODE_CONSTRAINT_VIOLATION', 23000);

try {
    $db = new PDO("sqlite:$db_path");
} catch(PDOException $e) {
    die("Could not connect to DB: " . $e->getMessage());
}

$ref = "{". "$imap_host:$imap_port" . "/ssl}$imap_folder";

$imap = imap_open($ref, $imap_user, $imap_password);

if ($imap == false) {
    die("Unable to connect to IMAP server:" . imap_last_error() . "\n");
}

$info = imap_check($imap);
$number_of_messages = $info->Nmsgs;

$overview = imap_fetch_overview($imap, "1:$number_of_messages");

$fetched_messages = array();
$post_id = "";
$email_file = "";
$email_uid = "";
$date = 0;
$validated = 0;


foreach ($overview as $msg_info) {
    //if ($msg_info->seen) continue;

    // Try to determine the blog post the email is referencing
    // Ignore malformed mails, or mails that are too large
    $matches = array();
    preg_match("/POST:(\S+)/", $msg_info->subject, $matches);
    if ((count($matches) == 0)
        || ($msg_info->size > $max_mail_size)) {
        echo("Skipping malformed or oversized message\n");
        array_push($fetched_messages, $msg_info->msgno); 
        continue;
    }
    $post_id = $matches[1];

    $headers = utf8_encode(quoted_printable_decode(
                    imap_fetchbody($imap, $msg_info->msgno, "0")));

    $body = utf8_encode(quoted_printable_decode(
                    imap_fetchbody($imap, $msg_info->msgno, "1")));

    $email_uid = $msg_info->message_id;
    $date = DateTime::createFromFormat(DateTime::RFC2822, $msg_info->date)
        ->getTimestamp();
    $email_file = $mail_dir . "/" . sha1($email_uid);

    $file = fopen($email_file, 'w');
    if (!$file) {
        echo "Unable to open $email_file for writing.\n";
        break;
    }

    fwrite($file, $headers);
    fwrite($file, $body);
    fclose($file);

    $insert_statement = $db->prepare('INSERT INTO comments (post_id,email_file,email_uid,date,validated) VALUES (:post_id, :email_file, :email_uid, :date, :validated)');

    try {
        $insert_statement->execute(array(':post_id' => $post_id,
            ':email_file' => $email_file, ':email_uid' => $email_uid,
            ':date' => $date, ':validated' => $validated));
    } catch (PDOException $e) {
        // Only report the error if it is not the one caused by trying
        // to add duplicate entries to the database
        if ($e->getCode() != SQL_CODE_CONSTRAINT_VIOLATION) {
            echo("PDO Exception: " . $e->getMessage() . "\n");
        }
    }
    array_push($fetched_messages, $msg_info->msgno); 
    echo("[MSG] {$msg_info->subject}\n");
}

imap_setflag_full($imap, implode(',', $fetched_messages), '\\Seen');

imap_close($imap);
?>
