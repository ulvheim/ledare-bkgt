-- BKGT Document Management System - Database Tables
-- Run this script to create the necessary database tables for DMS Phase 2

USE bkgt_se_db_1;

-- Documents table
CREATE TABLE IF NOT EXISTS wp_bkgt_documents (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    title varchar(255) NOT NULL,
    filename varchar(255) NOT NULL,
    filepath varchar(500) NOT NULL,
    file_url varchar(500) NOT NULL,
    file_size bigint(20) NOT NULL,
    mime_type varchar(100) NOT NULL,
    category varchar(100) DEFAULT '',
    description text,
    uploaded_by bigint(20) unsigned NOT NULL,
    upload_date datetime DEFAULT CURRENT_TIMESTAMP,
    modified_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    access_level varchar(50) DEFAULT 'private',
    version int(11) DEFAULT 1,
    parent_id mediumint(9) DEFAULT 0,
    metadata longtext,
    PRIMARY KEY (id),
    KEY uploaded_by (uploaded_by),
    KEY category (category),
    KEY access_level (access_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Document categories table
CREATE TABLE IF NOT EXISTS wp_bkgt_document_categories (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,
    slug varchar(100) NOT NULL,
    description text,
    parent_id mediumint(9) DEFAULT 0,
    created_by bigint(20) unsigned NOT NULL,
    created_date datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug),
    KEY parent_id (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Document access permissions table
CREATE TABLE IF NOT EXISTS wp_bkgt_document_permissions (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    document_id mediumint(9) NOT NULL,
    user_id bigint(20) unsigned NOT NULL,
    permission_type varchar(50) NOT NULL, -- 'read', 'write', 'delete', 'admin'
    granted_by bigint(20) unsigned NOT NULL,
    granted_date datetime DEFAULT CURRENT_TIMESTAMP,
    expires_date datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY document_id (document_id),
    KEY user_id (user_id),
    KEY permission_type (permission_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT IGNORE INTO wp_bkgt_document_categories (name, slug, created_by) VALUES
('Allmänna dokument', 'allmanna', 1),
('Ekonomi', 'ekonomi', 1),
('Spelare', 'spelare', 1),
('Tränare', 'tranare', 1),
('Styrelse', 'styrelse', 1),
('Utrustning', 'utrustning', 1),
('Kontrakt', 'kontrakt', 1),
('Protokoll', 'protokoll', 1);