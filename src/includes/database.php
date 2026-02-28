<?php
require_once 'config.php';

function query($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        die("Query Error: " . $conn->error . " - SQL: " . $sql);
    }
    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

function execute($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        die("Execute Error: " . $conn->error . " - SQL: " . $sql);
    }
    return $result;
}

function escape_string($string): string
{
    global $conn;
    return $conn->real_escape_string($string);
}

function last_insert_id(): int|string
{
    global $conn;
    return $conn->insert_id;
}
