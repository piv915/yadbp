<?php
/**
 * Wed Feb 03 22:48:39 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

/**
 * Параметр вышел за пределы допустимых значений.
 *
 * Код - порядковый номер параметра, текст - "название_параметра:суть ошибки"
 *
 */
//class OutOfRangeException extends Exception {}
/**
 * Попытка создать счет, который уже существует.
 *
 * Код - номер счета, текст содержит дату его создания в формате unix timestamp.
 *
 */
//class AccountExistsException extends Exception {}
if(!class_exists('AccountExistsException')) { class AccountExistsException extends Exception {} }
/**
 * Целостность объекта была нарушена.
 *
 * В тексте может содержаться дополнительная информация для записи в логи, на усмотрение класса.
 *
 */
class IntegrityViolationException extends Exception {}
/**
 * Объекту высшего уровня не удалось создать объект низшего.
 *
 * Текст содержит название "драйвера"
 *
 */
class DriverNotFoundException extends Exception {}
/**
 * Объект не найден.
 *
 * На данный момент используется в ReliableCache;
 *
 */
class ObjectNotFoundException extends Exception {}

class DriverException extends Exception {}
