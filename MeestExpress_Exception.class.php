<?php
/**
 * WebProduction Packages
 *
 * @copyright (C) 2007-2016 WebProduction <webproduction.ua>
 *
 * This program is commercial software;
 * you can not distribute it and/or modify it.
 */

/**
 * Exception for MeestExpress
 *
 * @copyright WebProduction
 * @package   MeestExpress
 */
class MeestExpress_Exception extends Exception {

    public function __construct($message, $code = 0) {
        parent::__construct('MeestExpress: '.$message, $code);
    }

}