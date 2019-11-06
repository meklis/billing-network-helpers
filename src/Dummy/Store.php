<?php


namespace Meklis\BillingNetworkHelpers\Dummy;


use Meklis\BillingNetworkHelpers\Interfaces\StoreInterface;

class Store implements StoreInterface
{
    function getSwitchesListByIp($user_ip)
    {
        //Считаем что по ip user_ip найдено 3 IP свитчей по этому влану
        return [
            '10.1.1.17',
            '10.1.1.11',
            '10.1.1.14',
        ];
    }

    function getDeviceAccess($device_ip)
    {
        //На основе введенного IP возвращается элемент массива
        $db = [
            '10.1.1.11' => ['ip'=> '10.1.1.11', 'community' => 'public', 'login'=>'login', 'password'=>'password', 'port_telnet'=>23, 'port_api'=>55055],
            '10.1.1.14' => ['ip'=> '10.1.1.14', 'community' => 'public', 'login'=>'login', 'password'=>'password', 'port_telnet'=>23, 'port_api'=>55055],
            '10.1.1.17' => ['ip'=> '10.1.1.17', 'community' => 'public', 'login'=>'login', 'password'=>'password', 'port_telnet'=>23, 'port_api'=>55055],
            '185.190.150.1' => ['ip'=> '185.190.150.1', 'community' => 'public', 'login'=>'login', 'password'=>'password', 'port_telnet'=>23, 'port_api'=>55055, 'port_telnet'=>23, 'port_api'=>55055],
        ];

        return $db[$device_ip];
    }

    function getRouterListByIp($user_ip)
    {
       return ['185.190.150.1'];
    }

}