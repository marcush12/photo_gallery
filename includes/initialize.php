<?php
//define the core paths
//define them as absolute paths to make sure that require_once work as expected
//DIRECTORY_SEPARATOR is a ph pre-defined constant \ for windows
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
defined('SITE_ROOT') ? null : define('SITE_ROOT', DS. 'xampp'.DS.'htdocs'.DS.'photo_gallery');
defined('LIB_PATH') ? null : define('LIB_PATH', SITE_ROOT.DS.'includes');

//load config file first
require_once (LIB_PATH.DS."config.php");
//load basic functions next so that everything after can use them
require_once (LIB_PATH.DS."functions.php");
//load core objects
require_once (LIB_PATH.DS."session.php");
require_once (LIB_PATH.DS."database.php");
require_once (LIB_PATH.DS."database_object.php");
require_once (LIB_PATH.DS."pagination.php");
require_once (LIB_PATH.DS."phpMailer".DS."class.phpmailer.php");
require_once (LIB_PATH.DS."phpMailer".DS."class.smtp.php");
//require_once (LIB_PATH.DS."phpMailer".DS."language".DS."phpmailer.lang-en.php");
//load database-related classes
require_once (LIB_PATH.DS."user.php");
require_once (LIB_PATH.DS."photograph.php");
require_once (LIB_PATH.DS."comment.php");