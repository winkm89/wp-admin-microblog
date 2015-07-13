<?php
/**
 * This file contains the wpam_core class
 * @package wpam
 * @since 2.3
 */

/**
 * This class contains all general helpers functions for wpam
 * @since 2.3
 */
class wpam_core {
    /**
     * Extract column settings from a string
     * Adapted from teachpress 5.0
     * @param string $data
     * @return array
     * @since 2.3
     */
    public static function extract_column_data ($data) {
        $return = array();
        $data = explode(',', $data);
        foreach ( $data as $row ) {
            $row = explode(' = ', $row);
            $name = trim($row[0]);
            $value = str_replace(array('{','}'), array('',''), trim($row[1]));
            $return[$name] = $value;
        }
        return $return;
    }
    
    /** 
     * Split the timestamp
     * @param $date - timestamp
     * @return $split - ARRAY
     *
     * $split[0][0] => Year
     * $split[0][1] => Month 
     * $split[0][2] => Day
     * $split[0][3] => Hour 
     * $split[0][4] => Minute 
     * $split[0][5] => Second
     * @since 2.3
    */ 
    public static function datesplit($date) {
       $preg = '/[\d]{2,4}/'; 
       $split = array(); 
       preg_match_all($preg, $date, $split); 
       return $split; 
    }

}