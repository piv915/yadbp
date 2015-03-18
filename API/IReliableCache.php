<?php
/**
 * Надежный кэш.
 * 
 * Объекты защищены хешем. Используется собственная, не зависимая от драйвера система устаревания объектов.
 * Внутри биллинга используется для сохранения состояния услуг, состояния счетов пользователей и форумов и т.п.
 *
 * @example reliablecache.php
 * 
 */
interface IReliableCache {
	/**
	 * Сохранить объект в кэше.
	 *
	 * @param string $key
	 * @param mixed $object
	 * @param integer $ttl optional time to invalidate
	 * @return boolean
	 * @throws InvalidArgumentException, DriverException
	 */
	public function save($key, $object, $ttl=0, $throws=true);
	/**
	 * Получить сохраненный объект из кэша.
	 *
	 * @param string $key
	 * @return mixed|false cached object
	 * @throws IntegrityViolationException
	 */
	public function  get($key);
	/**
	 * Получить несколько объектов из кэша. В том числе и один.
	 *
	 * @param string|array $key,... ключи в кэше
	 * @throws IntegrityViolationException
	 * @return array|false cached objects
	 */
	public function getMulti($key);
	
	public function delete($key);
}
