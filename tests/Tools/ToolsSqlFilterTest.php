<?php

namespace Tools;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools;

class ToolsSqlFilterTest extends TestCase
{
    private $tools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tools = new Tools();
    }

    public function testValidSelectQuery()
    {
        $sql = "SELECT * FROM users WHERE id = 1;";
        $filteredSql = $this->tools->sqlFilter($sql);
        $this->assertEquals($sql, $filteredSql, 'Valid SELECT query should not be filtered.');
    }

    public function testInvalidInsertQuery()
    {
        $sql = "INSERT INTO users (name) VALUES ('test');";
        $filteredSql = $this->tools->sqlFilter($sql);
        $this->assertNull($filteredSql, 'Invalid INSERT query should return null.');
    }

    public function testInvalidUpdateQuery()
    {
        $sql = "UPDATE users SET name = 'test' WHERE id = 2;";
        $filteredSql = $this->tools->sqlFilter($sql);
        $this->assertNull($filteredSql, 'Invalid UPDATE query should return null.');
    }

    public function testInvalidCreateTableQuery()
    {
        $sql = "CREATE TABLE users (id INT PRIMARY KEY);";
        $filteredSql = $this->tools->sqlFilter($sql);
        $this->assertNull($filteredSql, 'Invalid CREATE TABLE query should return null.');
    }

    public function testInvalidDropTableQuery()
    {
        $sql = "DROP TABLE users;";
        $filteredSql = $this->tools->sqlFilter($sql);
        $this->assertNull($filteredSql, 'Invalid DROP TABLE query should return null.');
    }

    public function testMixedQueries()
    {
        $sql = "SELECT * FROM users; DROP TABLE users;";
        $filteredSql = $this->tools->sqlFilter($sql);
        $this->assertNull($filteredSql, 'Mixed queries containing invalid operations should return null.');
    }

    public function testComplexValidQuery()
    {
        $sql = "SELECT u.name, o.order_id FROM users u JOIN orders o ON u.id = o.user_id WHERE u.id = 1;";
        $filteredSql = $this->tools->sqlFilter($sql);
        $this->assertEquals($sql, $filteredSql, 'Complex valid SELECT query should not be filtered.');
    }

    public function testLoadFileQuery()
    {
        $sql = "SELECT LOAD_FILE('/etc/passwd') FROM users;";
        $filteredSql = $this->tools->sqlFilter($sql);
        $this->assertNull($filteredSql, 'Query with LOAD_FILE should return null.');
    }

    public function testOutfileQuery()
    {
        $sql = "SELECT * FROM users INTO OUTFILE '/tmp/users.csv';";
        $filteredSql = $this->tools->sqlFilter($sql);
        $this->assertNull($filteredSql, 'Query with OUTFILE should return null.');
    }

    public function testDumpQuery()
    {
        $sql = "DUMP DATA TO '/tmp/backup.sql';";
        $filteredSql = $this->tools->sqlFilter($sql);
        $this->assertNull($filteredSql, 'Query with DUMP should return null.');
    }

    public function testCustomBlacklistPatterns()
    {
        $customBlacklistPatterns = [
            '/\btruncate\s+table\b/i',       // 匹配 "TRUNCATE TABLE"
            '/\bgrant\s+\w+\b/i'             // 匹配 "GRANT permission"
        ];

        // 测试自定义黑名单模式
        $sqlQueries = [
            "TRUNCATE TABLE users;",
            "GRANT ALL PRIVILEGES ON users TO 'user';",
            "SELECT * FROM users;"
        ];

        foreach ($sqlQueries as $sql) {
            $filteredSql = $this->tools->sqlFilter($sql, $customBlacklistPatterns);
            if (in_array($sql, ["TRUNCATE TABLE users;", "GRANT ALL PRIVILEGES ON users TO 'user';"])) {
                $this->assertNull($filteredSql, "SQL query with custom blacklist keyword {$sql} should be filtered.");
            } else {
                $this->assertEquals($sql, $filteredSql, "Valid SQL query {$sql} should not be filtered.");
            }
        }
    }
}