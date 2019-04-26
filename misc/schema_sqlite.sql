CREATE TABLE food_vote
(
    day      VARCHAR(16)  NOT NULL,
    category VARCHAR(128) NOT NULL,
    ip       VARCHAR(128) NOT NULL,
    vote     VARCHAR(128) NOT NULL,
    PRIMARY KEY (day, category, ip),
    CONSTRAINT FK_EF372DB5A5E3B32D FOREIGN KEY (ip) REFERENCES food_user (ip) NOT DEFERRABLE INITIALLY IMMEDIATE
);
CREATE INDEX IDX_EF372DB5A5E3B32D ON food_vote (ip);
CREATE TABLE food_user
(
    ip               VARCHAR(128) NOT NULL,
    name             VARCHAR(128) NOT NULL,
    email            VARCHAR(128) DEFAULT NULL,
    vote_reminder    BOOLEAN      NOT NULL,
    voted_mail_only  BOOLEAN      NOT NULL,
    vote_always_show BOOLEAN      NOT NULL,
    PRIMARY KEY (ip)
);
CREATE TABLE food_cache
(
    venue   VARCHAR(32) NOT NULL,
    date    VARCHAR(16) NOT NULL,
    changed DATETIME    NOT NULL,
    data    CLOB        NOT NULL,
    price   CLOB        NOT NULL,
    PRIMARY KEY (venue, date)
);
