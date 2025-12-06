-- ============================
-- DROP (in dependency order)
-- ============================
DROP TABLE IF EXISTS ticket_attachment;
DROP TABLE IF EXISTS ticket_tag;
DROP TABLE IF EXISTS ticket_assignment;
DROP TABLE IF EXISTS ticket_comment;
DROP TABLE IF EXISTS ticket;
DROP TABLE IF EXISTS ticket_category;

-- ============================================
-- 1) ticket_category
-- ============================================
CREATE TABLE IF NOT EXISTS ticket_category (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,
    slug  VARCHAR(100) NOT NULL,
    name  VARCHAR(100) NOT NULL,

    PRIMARY KEY (ctime, crand),
    UNIQUE INDEX uq_ticket_category_slug (slug),
    UNIQUE INDEX uq_ticket_category_name (name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 2) ticket
-- ============================================
CREATE TABLE IF NOT EXISTS ticket (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,

    created_by_crand INT NULL,        -- FK -> account.Id

    category      VARCHAR(100) NULL,  -- FK -> ticket_category.slug
    guild_id      INT NULL,           -- FK -> guild.id
    game_id       INT NULL,           -- FK -> game.Id
    server_ctime  DATETIME(6) NULL,   -- FK -> server.ctime
    server_crand  BIGINT NULL,        -- FK -> server.crand

    status   VARCHAR(32) NOT NULL DEFAULT 'open',
    priority TINYINT UNSIGNED NOT NULL DEFAULT 2
        COMMENT '1=low, 2=medium, 3=high, 4=urgent',
    severity TINYINT UNSIGNED NULL
        COMMENT '1=cosmetic, 2=minor, 3=major, 4=critical',

    subject     VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,

    tags_json JSON NULL,

    updated_at        DATETIME(6) NOT NULL,
    first_response_at DATETIME(6) NULL,
    resolved_at       DATETIME(6) NULL,
    closed_at         DATETIME(6) NULL,
    created_ip        VARCHAR(45) NULL,
    user_agent        VARCHAR(255) NULL,
    is_deleted        TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at        DATETIME(6) NULL,
    updated_by_crand  INT NULL,       -- FK -> account.Id

    PRIMARY KEY (ctime, crand),

    INDEX idx_ticket_creator        (created_by_crand),
    INDEX idx_ticket_category       (category),
    INDEX idx_ticket_guild          (guild_id),
    INDEX idx_ticket_game           (game_id),
    INDEX idx_ticket_server         (server_ctime, server_crand),
    INDEX idx_ticket_status         (status),
    INDEX idx_ticket_priority       (priority),
    INDEX idx_ticket_updated_at     (updated_at),
    INDEX idx_ticket_updated_by     (updated_by_crand),
    INDEX idx_ticket_not_deleted    (is_deleted, updated_at),

    FULLTEXT INDEX ft_ticket_search (subject, description),

    CHECK (status IN ('open', 'in_progress', 'resolved', 'closed')),
    CHECK (priority BETWEEN 1 AND 4),
    CHECK (severity IS NULL OR severity BETWEEN 1 AND 4),
    CHECK (tags_json IS NULL OR JSON_VALID(tags_json)),

    CONSTRAINT fk_ticket_category_slug
        FOREIGN KEY (category)
        REFERENCES ticket_category (slug)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT fk_ticket_guild
        FOREIGN KEY (guild_id)
        REFERENCES guild (id)
        ON DELETE SET NULL,

    CONSTRAINT fk_ticket_game
        FOREIGN KEY (game_id)
        REFERENCES game (Id)
        ON DELETE SET NULL,

    CONSTRAINT fk_ticket_server
        FOREIGN KEY (server_ctime, server_crand)
        REFERENCES server (ctime, crand)
        ON DELETE SET NULL,

    CONSTRAINT fk_ticket_created_by
        FOREIGN KEY (created_by_crand)
        REFERENCES account (Id)
        ON DELETE SET NULL,

    CONSTRAINT fk_ticket_updated_by
        FOREIGN KEY (updated_by_crand)
        REFERENCES account (Id)
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;



-- ============================================
-- 3) ticket_comment
-- ============================================
CREATE TABLE IF NOT EXISTS ticket_comment (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,

    ticket_ctime DATETIME(6) NOT NULL,
    ticket_crand BIGINT NOT NULL,

    author_crand INT NULL,    -- FK -> account.Id
    body TEXT NOT NULL,

    PRIMARY KEY (ctime, crand),

    INDEX idx_ticket_comment_ticket (ticket_ctime, ticket_crand),
    INDEX idx_ticket_comment_author (author_crand),

    CONSTRAINT fk_ticket_comment_ticket
        FOREIGN KEY (ticket_ctime, ticket_crand)
        REFERENCES ticket (ctime, crand)
        ON DELETE CASCADE,

    CONSTRAINT fk_ticket_comment_author
        FOREIGN KEY (author_crand)
        REFERENCES account (Id)
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 4) ticket_assignment
-- ============================================
CREATE TABLE IF NOT EXISTS ticket_assignment (
    ticket_ctime DATETIME(6) NOT NULL,
    ticket_crand BIGINT NOT NULL,

    account_crand     INT NOT NULL,   -- FK -> account.Id
    assigned_by_crand INT NULL,       -- FK -> account.Id

    assigned_at DATETIME(6) NOT NULL,

    email_opt_in      TINYINT(1) NOT NULL DEFAULT 1,
    unsubscribe_token VARCHAR(128) NOT NULL,

    PRIMARY KEY (ticket_ctime, ticket_crand, account_crand),

    INDEX idx_ticket_assignment_account      (account_crand),
    INDEX idx_ticket_assignment_assigned_by  (assigned_by_crand),

    CONSTRAINT fk_ticket_assignment_ticket
        FOREIGN KEY (ticket_ctime, ticket_crand)
        REFERENCES ticket (ctime, crand)
        ON DELETE CASCADE,

    CONSTRAINT fk_ticket_assignment_account
        FOREIGN KEY (account_crand)
        REFERENCES account (Id)
        ON DELETE CASCADE,

    CONSTRAINT fk_ticket_assignment_assigned_by
        FOREIGN KEY (assigned_by_crand)
        REFERENCES account (Id)
        ON DELETE SET NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 5) ticket_tag
-- ============================================
CREATE TABLE IF NOT EXISTS ticket_tag (
    ticket_ctime DATETIME(6) NOT NULL,
    ticket_crand BIGINT NOT NULL,

    tag VARCHAR(64) NOT NULL,

    PRIMARY KEY (ticket_ctime, ticket_crand, tag),

    INDEX idx_ticket_tag_tag (tag),

    CONSTRAINT fk_ticket_tag_ticket
        FOREIGN KEY (ticket_ctime, ticket_crand)
        REFERENCES ticket (ctime, crand)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 6) ticket_attachment
-- ============================================
CREATE TABLE IF NOT EXISTS ticket_attachment (
    ctime DATETIME(6) NOT NULL,
    crand BIGINT NOT NULL,

    ticket_ctime DATETIME(6) NOT NULL,
    ticket_crand BIGINT NOT NULL,

    stored_name   VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type     VARCHAR(255) NOT NULL,
    size_bytes    INT NOT NULL,
    created_at    DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),

    PRIMARY KEY (ctime, crand),

    INDEX idx_attachment_ticket (ticket_ctime, ticket_crand),

    CONSTRAINT fk_ticket_attachment_ticket
        FOREIGN KEY (ticket_ctime, ticket_crand)
        REFERENCES ticket (ctime, crand)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
