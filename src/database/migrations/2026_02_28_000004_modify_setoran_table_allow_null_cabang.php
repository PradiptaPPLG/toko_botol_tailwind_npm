<?php

class ModifySetoranTableAllowNullCabang extends Migration
{
    public function up(): void
    {
        // Drop the foreign key constraint first
        $this->execute("ALTER TABLE setoran DROP FOREIGN KEY setoran_cabang_id_foreign");
        
        // Modify cabang_id to be nullable
        $this->execute("ALTER TABLE setoran MODIFY COLUMN cabang_id INT NULL");
        
        // Re-add the foreign key without restriction for null values
        $this->execute("ALTER TABLE setoran ADD CONSTRAINT setoran_cabang_id_foreign FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE SET NULL");
    }

    public function down(): void
    {
        $this->execute("ALTER TABLE setoran DROP FOREIGN KEY setoran_cabang_id_foreign");
        $this->execute("ALTER TABLE setoran MODIFY COLUMN cabang_id INT NOT NULL");
        $this->execute("ALTER TABLE setoran ADD CONSTRAINT setoran_cabang_id_foreign FOREIGN KEY (cabang_id) REFERENCES cabang(id)");
    }
}
