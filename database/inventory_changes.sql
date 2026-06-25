-- ============================================================
-- TABLE: inventory_changes
-- Jab bhi kisi PC ki koi bhi field change ho, yahan record ho
-- ============================================================

CREATE TABLE `inventory_changes` (
  `id`            int(11) NOT NULL AUTO_INCREMENT,
  `pc_name`       varchar(100) NOT NULL,
  `ip_address`    varchar(50)  DEFAULT NULL,
  `motherboard_serial` varchar(100) DEFAULT NULL,
  `change_type`   varchar(50)  NOT NULL,  -- ram / ip / cpu / os / disk / location / mb_serial / arch / logged_user
  `field_name`    varchar(100) NOT NULL,  -- exact column name
  `old_value`     text         DEFAULT NULL,
  `new_value`     text         DEFAULT NULL,
  `detected_at`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `acknowledged`  tinyint(1)   NOT NULL DEFAULT 0,  -- 0=unread, 1=read
  `ack_by`        varchar(100) DEFAULT NULL,
  `ack_at`        datetime     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pc`     (`pc_name`),
  KEY `idx_ack`    (`acknowledged`),
  KEY `idx_type`   (`change_type`),
  KEY `idx_date`   (`detected_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
