<?php
/**
 * Base Migration Class
 * Laravel-style database migrations
 */

abstract class Migration
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function execute(string $sql): bool
    {
        return $this->conn->query($sql);
    }

    protected function createTable(string $table, callable $callback): void
    {
        $schema = new Schema($this->conn, $table);
        $callback($schema);
        $schema->create();
    }

    protected function dropTable(string $table): void
    {
        $this->execute("DROP TABLE IF EXISTS `$table`");
    }

    protected function dropIfExists(string $table): void
    {
        $this->execute("DROP TABLE IF EXISTS `$table`");
    }

    protected function rename(string $from, string $to): void
    {
        $this->execute("RENAME TABLE `$from` TO `$to`");
    }

    protected function hasTable(string $table): bool
    {
        $result = $this->conn->query("SHOW TABLES LIKE '$table'");
        return $result && $result->num_rows > 0;
    }

    protected function addColumn(string $table, string $column, string $definition): void
    {
        $this->execute("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }

    protected function dropColumn(string $table, string $column): void
    {
        $this->execute("ALTER TABLE `$table` DROP COLUMN `$column`");
    }
}

/**
 * Schema Builder Class
 */
class Schema
{
    private $conn;
    private $table;
    private $columns = [];
    private $primary_key = null;
    private $indexes = [];
    private $foreign_keys = [];

    // Tracks the last column definition for chained modifiers
    private $last_col_index = null;

    public function __construct($conn, string $table)
    {
        $this->conn  = $conn;
        $this->table = $table;
    }

    // ---------------------------------------------------------------
    // Column types
    // ---------------------------------------------------------------

    public function id(string $name = 'id'): self
    {
        $this->columns[]      = "`$name` INT(11) NOT NULL AUTO_INCREMENT";
        $this->primary_key    = $name;
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function integer(string $name, int $length = 11): self
    {
        $this->columns[]      = "`$name` INT($length) NOT NULL DEFAULT 0";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function integerNullable(string $name, int $length = 11): self
    {
        $this->columns[]      = "`$name` INT($length) DEFAULT NULL";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    /** Tiny integer (for boolean-like flags, e.g., is_cancelled) */
    public function tinyint(): self
    {
        // Replace the last column definition with TINYINT(1) variant
        if ($this->last_col_index !== null) {
            $col = $this->columns[$this->last_col_index];
            $this->columns[$this->last_col_index] = preg_replace('/INT\(\d+\)/', 'TINYINT(1)', $col);
        }
        return $this;
    }

    public function string(string $name, int $length = 255): self
    {
        $this->columns[]      = "`$name` VARCHAR($length) NOT NULL";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function stringNullable(string $name, int $length = 255): self
    {
        $this->columns[]      = "`$name` VARCHAR($length) DEFAULT NULL";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function text(string $name): self
    {
        $this->columns[]      = "`$name` TEXT NOT NULL";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function textNullable(string $name): self
    {
        $this->columns[]      = "`$name` TEXT DEFAULT NULL";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function enum(string $name, array $values): self
    {
        $values_str           = "'" . implode("','", $values) . "'";
        $this->columns[]      = "`$name` ENUM($values_str) NOT NULL";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function datetime(string $name): self
    {
        $this->columns[]      = "`$name` DATETIME NOT NULL";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function datetimeNullable(string $name): self
    {
        $this->columns[]      = "`$name` DATETIME DEFAULT NULL";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function date(string $name): self
    {
        $this->columns[]      = "`$name` DATE NOT NULL";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function timestamp(string $name): self
    {
        $this->columns[]      = "`$name` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    public function timestamps(): self
    {
        $this->columns[] = "`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $this->last_col_index = count($this->columns) - 1;
        return $this;
    }

    // ---------------------------------------------------------------
    // Column modifiers (chainable on the last column)
    // ---------------------------------------------------------------

    /** Set a DEFAULT value on the last column */
    public function default($value): self
    {
        if ($this->last_col_index === null) {
            return $this;
        }
        $col = $this->columns[$this->last_col_index];

        // Remove existing DEFAULT clause if present, then append new one
        $col = preg_replace('/\s+DEFAULT\s+\S+/i', '', $col);

        $quoted = is_string($value) ? "'$value'" : $value;
        $this->columns[$this->last_col_index] = $col . " DEFAULT $quoted";

        return $this;
    }

    // ---------------------------------------------------------------
    // Indexes & constraints
    // ---------------------------------------------------------------

    public function unique(string $name, $columns = null): self
    {
        if ($columns === null) {
            // single-column unique using the name as both key name and column
            $this->indexes[] = "UNIQUE KEY `{$name}_unique` (`$name`)";
        } else {
            // composite unique: $name = key name, $columns = array of column names
            $cols = '`' . implode('`, `', (array)$columns) . '`';
            $this->indexes[] = "UNIQUE KEY `$name` ($cols)";
        }
        return $this;
    }

    public function index(string $column, string $name = null): self
    {
        $index_name      = $name ?? "{$column}_index";
        $this->indexes[] = "KEY `$index_name` (`$column`)";
        return $this;
    }

    public function foreign(
        string $column,
        string $references_table,
        string $references_column = 'id',
        string $on_delete = 'RESTRICT'
    ): self {
        $constraint_name        = "{$this->table}_{$column}_foreign";
        $this->foreign_keys[]   = "CONSTRAINT `$constraint_name` FOREIGN KEY (`$column`) "
                                 . "REFERENCES `$references_table` (`$references_column`) ON DELETE $on_delete";
        return $this;
    }

    // ---------------------------------------------------------------
    // Build & execute
    // ---------------------------------------------------------------

    public function create(): void
    {
        $sql  = "CREATE TABLE IF NOT EXISTS `{$this->table}` (\n";
        $sql .= "  " . implode(",\n  ", $this->columns);

        if ($this->primary_key) {
            $sql .= ",\n  PRIMARY KEY (`{$this->primary_key}`)";
        }

        if (!empty($this->indexes)) {
            $sql .= ",\n  " . implode(",\n  ", $this->indexes);
        }

        if (!empty($this->foreign_keys)) {
            $sql .= ",\n  " . implode(",\n  ", $this->foreign_keys);
        }

        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        if (!$this->conn->query($sql)) {
            throw new Exception("Failed to create table {$this->table}: " . $this->conn->error);
        }
    }
}
?>
