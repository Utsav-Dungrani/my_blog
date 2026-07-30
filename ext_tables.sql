CREATE TABLE tx_myblog_domain_model_post (
    title varchar(255) DEFAULT '' NOT NULL,
    description text NOT NULL,
    author varchar(255) DEFAULT '' NOT NULL,
    image int(11) unsigned DEFAULT '0' NOT NULL
);