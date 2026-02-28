<?php
/**
 * Base Seeder Class
 * Laravel-style database seeders
 */

abstract class Seeder
{
    protected $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Run the seeder
     */
    abstract public function run(): void;

    /**
     * Insert data into table
     */
    protected function insert(string $table, array $data): bool
    {
        $columns = array_keys($data);
        $values = array_values($data);

        $columns_str = implode('`, `', $columns);
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $sql = "INSERT INTO `$table` (`$columns_str`) VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $this->conn->error);
        }

        // Build type string for bind_param
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_double($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Insert multiple rows
     */
    protected function insertMany(string $table, array $rows): void
    {
        foreach ($rows as $row) {
            $this->insert($table, $row);
        }
    }

    /**
     * Truncate table
     */
    protected function truncate(string $table): void
    {
        $this->conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->conn->query("TRUNCATE TABLE `$table`");
        $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Execute raw SQL
     */
    protected function execute(string $sql): bool
    {
        return $this->conn->query($sql);
    }

    /**
     * Call another seeder
     */
    protected function call(string $seederClass): void
    {
        $seeder = new $seederClass($this->conn);
        $seeder->run();
    }
}
?>
