<?php

class Struct
{
    /**
     * Define a new struct object, a blueprint object with only empty properties.
     */
    public static function factory()
    {
        $struct = new self;
        foreach (func_get_args() as $value) {
            $struct->$value = null;
        }
        return $struct;
    }
 
    /**
     * Create a new variable of the struct type $this.
     */
    public function create()
    {
        // Clone the empty blueprint-struct ($this) into the new data $struct.
        $struct = clone $this;
 
        // Populate the new struct.
        $properties = array_keys((array) $struct);
        foreach (func_get_args() as $key => $value) {
            if (!is_null($value)) {
                $struct->$properties[$key] = $value;
            }
        }
 
        // Return the populated struct.
        return $struct;
    }
}
?>
