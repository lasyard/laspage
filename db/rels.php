<?php
class Rels
{
    protected $_db;
    protected $_tbl;
    protected $_rels;

    public function __construct($db, $tbl, $rels)
    {
        $this->_db = $db;
        $this->_tbl = $tbl;
        $this->_rels = $rels;
    }

    protected static function _alias($rel, $key)
    {
        return $rel . '_' . $key;
    }

    protected static function _strAlias($rel, $tbl, $tblAlias)
    {
        $str = $tblAlias . '.id as ' . self::_alias($rel, 'id');
        foreach ($tbl['keys'] as $key) {
            $str .= ', ' . $tblAlias . '.' . $key . ' as ' . self::_alias($rel, $key);
        }
        return $str;
    }

    public function strAlias($rel)
    {
        $rels = $this->_rels;
        if (!isset($rels[$rel])) return '';
        extract($rels[$rel]);
        if (isset($tbl3)) {
            return self::_strAlias($rel, $tbl3, 'tbl3');
        }
        return self::_strAlias($rel, $tbl2, 'tbl2');
    }

    public function strJoin($rel)
    {
        $rels = $this->_rels;
        if (!isset($rels[$rel])) return '';
        extract($rels[$rel]);
        $str = ' left join tbl_' . $tbl2['name'] . ' tbl2 on me.id = tbl2.' . $tbl2['joint'];
        if (isset($tbl3)) {
            $str .= ' left join tbl_' . $tbl3['name'] . ' tbl3 on tbl3.id = tbl2.' . $tbl3['joint'];
        }
        return $str;
    }

    protected function _keys($rel)
    {
        $rels = $this->_rels;
        if (!isset($rels[$rel])) return false;
        extract($rels[$rel]);
        if (isset($tbl3)) {
            $keys = $tbl3['keys'];
        } else {
            $keys = $tbl2['keys'];
        }
        array_unshift($keys, 'id');
        return $keys;
    }

    public function fetchRel($rel, $row, &$rec)
    {
        $keys = $this->_keys($rel);
        if (!$keys) return;
        if (!empty($row[self::_alias($rel, 'id')])) {
            $id = $row[self::_alias($rel, 'id')];
            $arr = array();
            foreach ($keys as $key) {
                $arr[$key] = $row[self::_alias($rel, $key)];
            }
            $rec[$rel][$id] = $arr;
        }
    }

    public function removeAlias($rel, &$rec)
    {
        $keys = $this->_keys($rel);
        if (!$keys) return;
        foreach ($keys as $key) {
            unset($rec[self::_alias($rel, $key)]);
        }
    }

    protected function _delRel($rel, $id)
    {
        extract($rel);
        $this->_db->tbl($tbl2['name'])->del(array($tbl2['joint'] => $id));
    }

    public function del($id)
    {
        $rels = $this->_rels;
        foreach ($rels as $rel) $this->_delRel($rel, $id);
    }

    public function insert($relName, $id, $datum)
    {
        $rels = $this->_rels;
        if (!isset($rels[$relName])) return;
        extract($rels[$relName]);
        $tbl = $this->_db->tbl($tbl2['name']);
        if (isset($tbl3)) {
            foreach ($datum as $id3 => $data) {
                $tbl->insert(array(
                    $tbl2['joint'] => $id,
                    $tbl3['joint'] => $id3,
                ));
            }
        } else {
            foreach ($datum as $data) {
                $data[$tbl2['joint']] = $id;
                $tbl->insert($data);
            }
        }
    }
}
