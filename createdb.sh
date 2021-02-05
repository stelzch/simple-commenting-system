sqlite3 comments.db <<EOF

DROP TABLE IF EXISTS comments;
CREATE TABLE comments(
    email_file TEXT,
    email_uid TEXT PRIMARY KEY,
    post_id TEXT,
    date INTEGER,
    validated INTEGER,
    UNIQUE(email_uid)
);

EOF
