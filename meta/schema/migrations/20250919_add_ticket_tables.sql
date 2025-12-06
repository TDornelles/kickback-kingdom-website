-- Ticketing system foundational tables
CREATE TABLE IF NOT EXISTS ticket (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    created_by_crand INT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'open',
    priority VARCHAR(32) NOT NULL DEFAULT 'medium',
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    tags_json JSON NULL,
    updated_at DATETIME(6) NOT NULL,
    PRIMARY KEY (ctime, crand),
    INDEX idx_ticket_creator (created_by_crand)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_comment (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    ticket_ctime DATETIME(6) NOT NULL,
    ticket_crand BIGINT NOT NULL,
    author_crand INT NULL,
    body TEXT NOT NULL,
    PRIMARY KEY (ctime, crand),
    INDEX idx_ticket_comment_ticket (ticket_ctime, ticket_crand),
    CONSTRAINT fk_ticket_comment_ticket FOREIGN KEY (ticket_ctime, ticket_crand)
        REFERENCES ticket (ctime, crand) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_assignment (
    ticket_ctime DATETIME(6) NOT NULL,
    ticket_crand BIGINT NOT NULL,
    account_crand INT NOT NULL,
    assigned_by_crand INT NULL,
    assigned_at DATETIME(6) NOT NULL,
    email_opt_in TINYINT(1) NOT NULL DEFAULT 1,
    unsubscribe_token VARCHAR(128) NOT NULL,
    PRIMARY KEY (ticket_ctime, ticket_crand, account_crand),
    INDEX idx_ticket_assignment_account (account_crand),
    CONSTRAINT fk_ticket_assignment_ticket FOREIGN KEY (ticket_ctime, ticket_crand)
        REFERENCES ticket (ctime, crand) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_guild_link (
    ticket_ctime DATETIME(6) NOT NULL,
    ticket_crand BIGINT NOT NULL,
    guild_id INT NOT NULL,
    PRIMARY KEY (ticket_ctime, ticket_crand, guild_id),
    CONSTRAINT fk_ticket_guild_link_ticket FOREIGN KEY (ticket_ctime, ticket_crand)
        REFERENCES ticket (ctime, crand) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_tag (
    ticket_ctime DATETIME(6) NOT NULL,
    ticket_crand BIGINT NOT NULL,
    tag VARCHAR(64) NOT NULL,
    PRIMARY KEY (ticket_ctime, ticket_crand, tag),
    CONSTRAINT fk_ticket_tag_ticket FOREIGN KEY (ticket_ctime, ticket_crand)
        REFERENCES ticket (ctime, crand) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_category (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    slug VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (ctime, crand),
    UNIQUE INDEX uq_ticket_category_slug (slug),
    UNIQUE INDEX uq_ticket_category_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
