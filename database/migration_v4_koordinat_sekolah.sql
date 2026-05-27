-- Migration v4: koordinat sekolah dan pilihan sumber koordinat per kelas

CREATE TABLE IF NOT EXISTS pengaturan (
    kunci           VARCHAR(100) PRIMARY KEY,
    nilai           TEXT NULL,
    diperbarui_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @kolom_sumber_koordinat := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'kelas'
      AND COLUMN_NAME = 'sumber_koordinat'
);

SET @sql := IF(
    @kolom_sumber_koordinat = 0,
    'ALTER TABLE kelas ADD COLUMN sumber_koordinat ENUM(''sekolah'',''kelas'') NOT NULL DEFAULT ''sekolah'' AFTER tahun',
    'SELECT ''Kolom sumber_koordinat sudah ada'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Data lama yang sudah punya titik kelas tetap memakai titik kelas sendiri.
UPDATE kelas
SET sumber_koordinat = 'kelas'
WHERE latitude IS NOT NULL AND longitude IS NOT NULL;
