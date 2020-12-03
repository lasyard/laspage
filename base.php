<?php
class Base
{
    public function __call($name, $args)
    {
        if (substr($name, 0, 3) == 'set') {
            $var = '_' . lcfirst(substr($name, 3));
            if (property_exists($this, $var)) {
                $this->{$var} = $args[0];
                return;
            }
        }
        if (substr($name, 0, 3) == 'add') {
            $var = '_' . lcfirst(substr($name, 3)) . 's';
            if (property_exists($this, $var)) {
                array_push($this->{$var}, $args[0]);
                return;
            }
        }
        $var = '_' . $name;
        if (property_exists($this, $var)) {
            return $this->{$var};
        }
        throw new Exception('Call to undefined method ' . get_class($this) . '::' . $name);
    }

    public function __get($var)
    {
        return $this->{$var}();
    }

    public function __set($var, $val)
    {
        $fun = 'set' . ucfirst($var);
        $this->{$fun}($val);
    }
}
