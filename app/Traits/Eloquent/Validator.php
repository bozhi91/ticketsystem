<?php namespace App\Traits\Eloquent;

trait Validator {

    // protected static $create_validator_fields = [];
    // protected static $update_validator_fields = [];

    public static function getCreateValidatorFields()
    {
        return isset(self::$create_validator_fields) ? self::$create_validator_fields : [];
    }

    public static function getCreateValidator($data, $exclude, $rules = [])
    {
        $fields = self::excludeFields(self::getCreateValidatorFields(), $exclude);

        if (!empty($rules))
        {
            $fields = array_merge($fields, $rules);
        }

        return \Validator::make($data, $fields);
    }

    public static function getUpdateValidatorFields($current_id)
    {
        $fields = isset(self::$update_validator_fields) ? self::$update_validator_fields : [];

        // Replace the current_id in the rules
        foreach ($fields as $f => $value)
        {
            $fields[$f] = preg_replace('#:current#', $current_id, $value);
        }

        return $fields;
    }

    public static function getUpdateValidator($data, $current_id, $exclude = [])
    {
        $fields = self::excludeFields(self::getUpdateValidatorFields($current_id), $exclude);
        return \Validator::make($data, $fields);
    }

    public static function getValidator($data, $exclude = [], $rules = [])
    {
        return self::getCreateValidator($data, $exclude, $rules);
    }

    protected static function excludeFields($fields, $exclude)
    {
        if (is_array($exclude))
        {
            foreach ($exclude as $field)
            {
                unset($fields[$field]);
            }
        }

        return $fields;
    }

}
