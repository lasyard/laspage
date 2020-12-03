<?php
class Tbl
{
    protected $_db;
    protected $_tbl;
    protected $_rels;
    protected $_columnsInfo;

    public function __construct($db, $tbl)
    {
        $this->_db = $db;
        $this->_tbl = 'tbl_' . $tbl;
        $relsFile = CONF_PATH . '/tbl_rels/' . $tbl . '_rels.php';
        if (is_file($relsFile)) {
            $this->_rels = new Rels($db, $tbl, require_once $relsFile);
        }
    }

    private static function _keyEqualStrings($keys)
    {
        return array_map(function ($k) {
            return $k . ' = ?';
        }, $keys);
    }

    private static function _strWhere($rec)
    {
        return join(' and ', self::_keyEqualStrings(array_keys($rec)));
    }

    private static function _strWhereWith($rec)
    {
        return join(' and ', self::_keyEqualStrings(
            array_map(function ($k) {
                return 'me.' . $k;
            }, array_keys($rec))
        ));
    }

    private static function _bindValues($stmt, $rec, $i = 1)
    {
        foreach ($rec as $value) {
            $stmt->bindValue($i++, $value);
        }
        return $i;
    }

    private static function _condSql($cond)
    {
        return is_string($cond) ? $cond : $cond['sql'];
    }

    private static function _bindCondValues($stmt, $cond, $i = 1)
    {
        if (is_array($cond) && isset($cond['values'])) {
            $values = $cond['values'];
            if (is_array($values)) {
                $i = self::_bindValues($stmt, $values, $i);
            } else {
                $stmt->bindValue($i++, $values);
            }
        }
        return $i;
    }

    public function getEnumValues($colName)
    {
        $sql = 'show columns from ' . $this->_tbl . ' like ?';
        $stmt = $this->_db->prepare($sql);
        $stmt->bindValue(1, $colName);
        if (!$stmt->execute()) return false;
        if ($stmt->rowCount() != 1) return false;
        $value = $stmt->fetch()['Type'];
        $matches = array();
        if (!preg_match('/enum\((.*)\)/', $value, $matches)) return false;
        return str_getcsv($matches[1], ',', "'");
    }

    public function select($rec = array(), $keys = false, $cond = false)
    {
        $recs = array();
        $sql = 'select ';
        if ($keys) {
            $keys[] = 'id';
            $sql .= join(', ', $keys);
        } else {
            $sql .= '*';
        }
        $sql .= ' from ' . $this->_tbl;
        if ($rec) {
            $sql .= ' where ' . $this->_strWhere($rec);
            $hasWhere = true;
        }
        if ($cond) {
            $sql .= isset($hasWhere) ? ' and ' : ' where ';
            $sql .= self::_condSql($cond);
        }
        $stmt = $this->_db->prepare($sql);
        $i = self::_bindValues($stmt, $rec);
        $i = self::_bindCondValues($stmt, $cond, $i);
        if ($stmt->execute()) {
            while ($row = $stmt->fetch()) {
                $recs[$row['id']] = $row;
            }
        }
        return $recs;
    }

    public function selectById($id, $keys = false)
    {
        $recs = $this->select(array('id' => $id), $keys);
        if (count($recs) != 1) return false;
        return $recs[$id];
    }

    public function getById($id, $key)
    {
        $rec = $this->selectById($id, array($key));
        if (!$rec) return $rec;
        return $rec[$key];
    }

    public function getId($rec)
    {
        $recs = $this->select($rec, array());
        if (count($recs) != 1) return false;
        return array_keys($recs)[0];
    }

    public function selectWith($rel, $rec = array(), $keys = false, $cond = false)
    {
        $recs = array();
        $sql = 'select ';
        if ($keys) {
            $lookups = array();
            foreach ($keys as $index => $key) {
                if (is_string($index)) {
                    $lookups[$index] = $key;
                    unset($keys[$index]);
                }
            }
            $sql .= implode(', ', array_map(function ($s) {
                return 'me.' . $s;
            }, $keys));
        } else {
            $sql .= 'me.*';
        }
        $rels = $this->_rels;
        $sql .= ', ' . $rels->strAlias($rel);
        if (!empty($lookups)) {
            foreach ($lookups as $tbl => $fs) {
                foreach ($fs as $f) {
                    $sql .= ', ' . $tbl . '.' . $f . ' as ' . $tbl . '_' . $f;
                }
            }
        }
        $sql .= ' from ' . $this->_tbl . ' me' . $rels->strJoin($rel);
        if (!empty($lookups)) {
            foreach ($lookups as $tbl => $fs) {
                $sql .= ' left join tbl_' . $tbl . ' ' . $tbl . ' on me.' . $tbl . ' = ' . $tbl . '.id';
            }
        }
        if ($rec) {
            $sql .= ' where ' . $this->_strWhereWith($rec);
            $hasWhere = true;
        }
        if ($cond) {
            $sql .= isset($hasWhere) ? ' and ' : ' where ';
            $sql .= self::_condSql($cond);
        }
        $stmt = $this->_db->prepare($sql);
        $i = self::_bindValues($stmt, $rec);
        $i = self::_bindCondValues($stmt, $cond, $i);
        if ($stmt->execute()) {
            while ($row = $stmt->fetch()) {
                $id = $row['id'];
                if (!isset($recs[$id])) {
                    $recs[$id] = $row;
                    $recs[$id][$rel] = array();
                }
                $rels->fetchRel($rel, $row, $recs[$id]);
            }
            foreach ($recs as &$rec) $rels->removeAlias($rel, $rec);
        }
        return $recs;
    }

    public function selectByIdWith($rel, $id, $keys = false)
    {
        $recs = $this->selectWith($rel, array('id' => $id), $keys);
        if (count($recs) != 1) return false;
        return $recs[$id];
    }

    public function del($rec)
    {
        if (isset($this->_rels)) {
            $datum = $this->select($rec, array('id'));
            foreach ($datum as $data) $this->_rels->del($data['id']);
        }
        $sql = 'delete from ' . $this->_tbl . ' where ' . $this->_strWhere($rec);
        $stmt = $this->_db->prepare($sql);
        self::_bindValues($stmt, $rec);
        return $stmt->execute();
    }

    public function deleteById($id)
    {
        if ($this->del(array('id' => $id))) return $id;
        return false;
    }

    private static function _stripRels(&$rec)
    {
        $rels = array();
        foreach ($rec as $key => $value) {
            if (is_array($value)) {
                $rels[$key] = $value;
                unset($rec[$key]);
            }
        }
        return $rels;
    }

    public function insert($rec)
    {
        if (isset($this->_rels)) $rels = self::_stripRels($rec);
        $sql = 'insert into ' . $this->_tbl;
        $sql .= '(' . join(', ', array_keys($rec)) . ')';
        $sql .= 'value(' . join(', ', array_fill(0, count($rec), '?')) . ')';
        $stmt = $this->_db->prepare($sql);
        self::_bindValues($stmt, $rec);
        if ($stmt->execute()) {
            $id = $this->_db->lastInsertId();
            if (isset($rels)) {
                foreach ($rels as $name => $values) {
                    $this->_rels->insert($name, $id, $values);
                }
            }
            return $id;
        }
        return false;
    }

    public function updateById($id, $rec)
    {
        if (isset($this->_rels)) $rels = self::_stripRels($rec);
        $sql = 'update ' . $this->_tbl . ' set ';
        $sql .= join(', ', self::_keyEqualStrings(array_keys($rec)));
        $sql .= ' where id = ?';
        $stmt = $this->_db->prepare($sql);
        $i = self::_bindValues($stmt, $rec);
        $stmt->bindValue($i++, $id);
        if ($stmt->execute()) {
            if (isset($this->_rels)) {
                $this->_rels->del($id);
                if (isset($rels)) {
                    foreach ($rels as $name => $values) {
                        $this->_rels->insert($name, $id, $values);
                    }
                }
            }
            return $id;
        }
        return false;
    }

    public function count()
    {
        $stmt = $this->_db->query('select count(1) as cnt from ' . $this->_tbl);
        $row = $stmt->fetch();
        return $row['cnt'];
    }
}
