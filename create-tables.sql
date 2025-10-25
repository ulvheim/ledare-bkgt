CREATE TABLE wp_bkgt_manufacturers (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    manufacturer_id varchar(4) NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY manufacturer_id (manufacturer_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;

CREATE TABLE wp_bkgt_item_types (
    id int(11) NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    item_type_id varchar(4) NOT NULL,
    custom_fields longtext,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY item_type_id (item_type_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;