<?php
class Db extends PDO
{
    public function __construct()
    {
        parent::__construct(PDO_DSN, DB_USER, DB_PASSWORD);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function tbl($tbl)
    {
        return new Tbl($this, $tbl);
    }

    public function sql($sql)
    {
        $recs = array();
        $result = $this->query($sql);
        while ($row = $result->fetch()) {
            $recs[] = $row;
        }
        return $recs;
    }
}
