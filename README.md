# Simple Commenting System

This is a simple commenting system for my blog. It was designed with the
following goals in mind:

* privacy-friendly (no cookies)
* no need for sign-in (use pre-existing email account)
* ability to moderate content before it is published
* easy to maintain (<150 LoC)

It also has a couple of limitations:

* Plaintext only
* No threads, no replies, just a list of comments for each blog post

## Design
The visitors leave a comment using a mailto link embedded in the blog post. It
contains a special `POST:<POSTID>` tag in the subject that is used to identify
the post the comment is in reply to.

Emails are read via the `mailindex.php` script into a folder and indexed in an
SQLite database. This is done periodically via CRON.

A little bit of javascript glue code loads the comments from the `index.php`
endpoint by specifying the post tag.

## Configuration
* `$imap_host`: Hostname of your E-Mail server
* `$imap_port`: IMAP Port of your E-Mail Server
* `$imap_user`, `$imap_password`: E-Mail credentials
* `$imap_folder`: Inbox folder path
* `$mail_dir`: Absolute path to a server directory where emails will be stored
* `$max_mail_size`: Maximum size of an email specified in bytes
* `$db_path`: Absolute path to a sqlite database (can be created with `./createdb.sh`)
* `$cors_header`: Must be set to allow other pages to include comments. See
[MDN
Docs](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin)
