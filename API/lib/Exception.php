<?php
 /*
  *  Author: Vasiliy Ivanovich
  *
  *  $Id: Exception.php,v 1.1.1.1 2010/02/25 18:45:48 username Exp $
  */

class _Exception extends Exception {}
//class OutOfRangeException extends Exception {}
class TypeMismatchException extends Exception {}
class DBException extends Exception {}
class FileNotFoundException extends Exception {}
class AccountExistsException extends Exception {}
class MutexException extends Exception {}
class RouterException extends Exception {}
class UserInputException extends Exception {}
class InvalidArgumentException extends Exception {}

?>
