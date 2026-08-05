CREATE TABLE tx_myblog_domain_model_post (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,

    title varchar(255) DEFAULT '' NOT NULL,
    slug varchar(2048) DEFAULT '' NOT NULL,
    description text NOT NULL,
    author varchar(255) DEFAULT '' NOT NULL,
    image int(11) unsigned DEFAULT '0' NOT NULL,
    views int(11) unsigned DEFAULT '0' NOT NULL,
    categories int(11) unsigned DEFAULT '0' NOT NULL,
    reading_time int(11) unsigned DEFAULT '0' NOT NULL,
    comments int(11) unsigned DEFAULT '0' NOT NULL,
    allow_comments tinyint(4) unsigned DEFAULT '1' NOT NULL,
    fe_user int(11) unsigned DEFAULT '0' NOT NULL,
    comment_count_total int(11) unsigned DEFAULT '0' NOT NULL,
    comment_count_registered int(11) unsigned DEFAULT '0' NOT NULL,
    comment_count_guest int(11) unsigned DEFAULT '0' NOT NULL,
    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
    hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

CREATE TABLE tx_myblog_domain_model_comment (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,

    post int(11) unsigned DEFAULT '0' NOT NULL,
    author_name varchar(255) DEFAULT '' NOT NULL,
    author_email varchar(255) DEFAULT '' NOT NULL,
    content text NOT NULL,
    fe_user int(11) unsigned DEFAULT '0' NOT NULL,
    approved tinyint(4) unsigned DEFAULT '0' NOT NULL,

    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
    hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);