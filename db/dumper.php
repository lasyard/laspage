<?php
class Dumper extends mysqli
{
    public function __construct()
    {
        parent::__construct(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
        $this->set_charset("utf8");
    }

    public static function dump($path, $tables = '*', $excludes = null, $whole = true)
    {
        $db = new Dumper();
        $db->_dump($path, $tables, $excludes, $whole);
    }

    private function _dump($path, $tables, $excludes, $whole)
    {
        if ($tables == '*') {
            $tables = array();
            $result = $this->query('show tables');
            while ($row = $result->fetch_row()) {
                $table = $row[0];
                if ($excludes && in_array($table, $excludes)) continue;
                $tables[] = $table;
            }
            $result->close();
        } else {
            $tables = is_array($tables) ? $tables : explode(',', $tables);
        }
        $colExcludes = array_fill_keys($tables, null);
        if ($excludes) {
            foreach ($excludes as $exclude) {
                list($table, $col) = explode('.', $exclude, 2);
                if (array_key_exists($table, $colExcludes) && $col) {
                    if ($colExcludes[$table] === null) {
                        $colExcludes[$table] = array();
                    }
                    $colExcludes[$table][] = $col;
                }
            }
        }
        if ($whole) {
            $file = $path . '.sql';
            $fh = fopen($file, 'w');
            if (!$fh) {
                mkdir(dirname($file), 0775, true);
                $fh = fopen($file, 'w');
            }
            if (!$fh) {
                return "Open file $file failed!";
            }
            $this->_dumpSettings($fh);
            fwrite($fh, PHP_EOL);
            foreach ($tables as $table) {
                $this->_dumpTable($fh, $table, $colExcludes[$table]);
            }
            fclose($fh);
        } else {
            if (is_dir($path)) {
                $dir = opendir($path);
                while (($file = readdir($dir)) !== false) {
                    if ($file == '.' || $file == '..') continue;
                    unlink($path . '/' . $file);
                }
                closedir($dir);
            } else {
                mkdir($path, 0775, true);
            }
            foreach ($tables as $table) {
                $file = $path . '/' . $table . '.sql';
                $fh = fopen($file, 'w');
                if (!$fh) {
                    return "Open file $file failed!";
                }
                $this->_dumpSettings($fh);
                fwrite($fh, PHP_EOL);
                $this->_dumpTable($fh, $table, $colExcludes[$table]);
                fclose($fh);
            }
        }
        return '';
    }

    private function _dumpSettings($fh)
    {
        fwrite($fh, "SET NAMES 'utf8';" . PHP_EOL);
        fwrite($fh, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";" . PHP_EOL);
        fwrite($fh, "SET time_zone = \"+00:00\";" . PHP_EOL);
    }

    private function _dumpTable($fh, $table, $excludes = null)
    {
        fwrite($fh, "DROP TABLE IF EXISTS `" . $table . "`;" . PHP_EOL);
        $result = $this->query('show create table ' . $table);
        $row = $result->fetch_row();
        fwrite($fh, $row[1] . ";" . PHP_EOL . PHP_EOL);
        $result->close();
        $result = $this->query('select * from ' . $table);
        $fields = $result->fetch_fields();
        $isNum = array();
        $skipped = array();
        $fieldsStr = array();
        foreach ($fields as $field) {
            $name = $field->name;
            if ($excludes && in_array($name, $excludes)) {
                $skipped[$name] = true;
                continue;
            }
            $skipped[$name] = false;
            $isNum[$name] = self::_mysqliIsNumField($field);
            $fieldsStr[] = "`$name`";
        }
        $insertLine = "INSERT INTO `" . $table . "` (" . join(', ', $fieldsStr) . ") VALUES" . PHP_EOL;
        $row = $result->fetch_assoc();
        while ($row) {
            fwrite($fh, $insertLine);
            for ($count = 0;;) {
                $v = array();
                foreach ($row as $key => $value) {
                    if ($skipped[$key]) continue;
                    if (!isset($value)) {
                        $v[] = 'NULL';
                    } elseif ($isNum[$key]) {
                        $v[] = $value;
                    } else {
                        $v[] = "'" . $this->escape_string($value) . "'";
                    }
                }
                fwrite($fh, "(" . join(', ', $v) . ")");
                $row = $result->fetch_assoc();
                $count++;
                if ($row && $count < 100) {
                    fwrite($fh, ',' . PHP_EOL);
                } else {
                    fwrite($fh, ';' . PHP_EOL);
                    break;
                }
            }
        }
        $result->close();
        fwrite($fh, PHP_EOL);
    }

    private static function _mysqliIsNumField($field)
    {
        return ($field->type == MYSQLI_TYPE_DECIMAL ||
            $field->type == MYSQLI_TYPE_TINY ||
            $field->type == MYSQLI_TYPE_SHORT ||
            $field->type == MYSQLI_TYPE_LONG ||
            $field->type == MYSQLI_TYPE_FLOAT ||
            $field->type == MYSQLI_TYPE_DOUBLE ||
            $field->type == MYSQLI_TYPE_LONGLONG ||
            $field->type == MYSQLI_TYPE_INT24 ||
            $field->type == MYSQLI_TYPE_YEAR);
    }
}
