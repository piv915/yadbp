<?php
/**
 * Wed Feb 03 23:00:16 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

/**
 * Тип счета
 *
 */
class AccountType {
	const FORUM = 2;
	const LOCAL_USER  = 3;
	const GLOBAL_USER = 4;
}

class CustomerType {
	const FORUM = 2;
	const LOCAL_USER  = 3;
	const GLOBAL_USER = 4;
}

/**
 * Результат операции.
 *
 */
class OperationResult {
	const OK = 2;
	const ERROR = 4;
	const YES = 8;
	const NO = 16;
	const ACCCOUNT_EXISTS = 32;
}
/**
 * Тип операции над счетом. Пополнение или снятие средств.
 *
 */
class AccountOperationType {
	const PUT = 2;
	const CHARGE = 4;
	const ALL = 6;
}
