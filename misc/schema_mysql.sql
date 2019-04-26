CREATE TABLE food_vote
(
    day      VARCHAR(16)  NOT NULL,
    category VARCHAR(128) NOT NULL,
    ip       VARCHAR(128) NOT NULL,
    vote     VARCHAR(128) NOT NULL,
    INDEX IDX_EF372DB5A5E3B32D (ip),
    PRIMARY KEY (day, category, ip)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  ENGINE = InnoDB;
CREATE TABLE food_user
(
    ip               VARCHAR(128) NOT NULL,
    name             VARCHAR(128) NOT NULL,
    email            VARCHAR(128) DEFAULT NULL,
    vote_reminder    TINYINT(1)   NOT NULL,
    voted_mail_only  TINYINT(1)   NOT NULL,
    vote_always_show TINYINT(1)   NOT NULL,
    PRIMARY KEY (ip)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  ENGINE = InnoDB;
CREATE TABLE food_cache
(
    venue   VARCHAR(32) NOT NULL,
    date    VARCHAR(16) NOT NULL,
    changed DATETIME    NOT NULL,
    data    LONGTEXT    NOT NULL,
    price   LONGTEXT    NOT NULL,
    PRIMARY KEY (venue, date)
) DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  ENGINE = InnoDB;
ALTER TABLE food_vote
    ADD CONSTRAINT FK_EF372DB5A5E3B32D FOREIGN KEY (ip) REFERENCES food_user (ip);