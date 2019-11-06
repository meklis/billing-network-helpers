<?php


namespace Meklis\BillingNetworkHelpers\Interfaces;

/**
 * Пример реализации(пример ответа) можно посмотреть в Dummy\Store
 *
 * Interface StoreInterface
 * @package Meklis\BillingSwitcherCore\Interfaces
 *
 */
interface StoreInterface
{
    /**
     * Возвращает лист IP адресов устройств,
     * на которых нужно произвести поиск по MAC-адресам
     *
     * @param $user_ip
     * @return mixed
     */
    function getSwitchesListByIp($user_ip);

    /**
     * Возвращает асоциативный массив с ключами ip, login, password, community, telnetPort, apiPort
     * @example request getSwitchesListByIp('10.1.1.11');
     * @example response ['ip'=> '10.1.1.11', 'community' => 'public', 'login'=>'login', 'password'=>'password', 'port_telnet'=>23, 'port_api'=>55055]
     * Запрашиваются IP возвращаемые методами getSwitchesListByIp(), getRouterListByIp().
     * @param $device_ip
     * @return mixed
     */
    function getDeviceAccess($device_ip);

    /**
     * Возвращает лист IP адресов роутеров, на которых нужно искать ARP
     *
     * @param $user_ip
     * @return mixed
     */
    function getRouterListByIp($user_ip);
}