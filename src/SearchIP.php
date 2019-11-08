<?php


namespace Meklis\BillingNetworkHelpers;


use Meklis\BillingNetworkHelpers\Interfaces\StoreInterface;
use SwitcherCore\Modules\Helper;
use SwitcherCore\Switcher\CoreConnector;

class SearchIP
{
    protected $store;
    protected $ip;
    protected $proxySearchPath;

    /**
     * SearchIP constructor.
     * @param StoreInterface $store
     */
    public function __construct(StoreInterface $store){
        $this->store = $store;
    }

    /**
     * Устанавливается IP для поиска
     *
     * @param $ip
     * @return $this
     */
    public function setIp($ip) {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Получает лист IP адресов, возвращает ассоциативный массив вида
     *  '<IP address>' => [
     *      ip => <IP address>,
     *      login => <Login>,
     *      password => <Password>,
     *      community => <Community>,
     *      port_telnet => <Port Telnet>,
     *      port_api => <Port Mikrotik RouterOS api>
     *   ]
     *
     * Для получения данных используется StoreInterface
     *
     * @param $ipList
     * @return array
     * @throws \Exception
     */
    private function getIpListWithAccess($ipList) {
        $response = [];
        foreach ($ipList as $ip) {
            $access = $this->store->getDeviceAccess($ip);
            if(!$access) throw new \Exception("Error get access for ip $ip");
            $response[$ip] = [
                'ip' => $ip,
                'login' => $access['login'],
                'community' => $access['community'],
                'password' => $access['password'],
                'port_telnet' => $access['port_telnet'],
                'port_api' => $access['port_api']
            ];
        }
        return $response;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getSearchDeviceList() {
        $devList = $this->store->getSwitchesListByIp($this->ip);
        if(!$devList) {
            throw new \Exception("Not found devices for IP {$this->ip}");
        }
        return $this->getIpListWithAccess($devList);
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function getSearchRouterList() {
        $devList = $this->store->getRouterListByIp($this->ip);
        if(!$devList) {
            throw new \Exception("Not found devices for IP {$this->ip}");
        }
        return $this->getIpListWithAccess($devList);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    private function getArp() {
        foreach ($this->getSearchRouterList() as $router_ip=>$access) {
            $arp = $this->getCoreInstance($router_ip, $access)->action('arp_info', ['ip' => $this->ip]);
            if (count($arp) >= 1) {
                $arp[0]['router'] = $router_ip;
                return $arp[0];
            }
        }
        throw new \Exception("ARP for ip {$this->ip} not found on L3 devices");
    }

    /**
     * @param $vlan_id
     * @param $mac_addr
     * @param bool $ignoreTagged
     * @return mixed
     * @throws \Exception
     */
    private function getFDB($vlan_id, $mac_addr, $ignoreTagged = true) {
        foreach ($this->getSearchDeviceList() as $dev=>$access) {
            try {
                $core = $this->getCoreInstance($dev, $access);
                $fdb = $core->action('fdb', ['vlan_id' => $vlan_id, 'mac' => $mac_addr]);
                $vlans = $core->action('vlans', ['vlan_id'=>$vlan_id]);
            } catch (\Exception $e) {
                continue;
            }
            if(!isset($vlans[0])) {
                throw new \Exception("Vlan with ID $vlan_id not found on device $dev");
            } else {
                $vlan = $vlans[0];
            }
            foreach ($fdb as $f) {
                if($ignoreTagged) {
                    if(in_array($f['port'], $vlan['ports']['tagged'])) {
                        continue;
                    }
                    if(in_array($f['port'], $vlan['ports']['forbidden'])) {
                        continue;
                    }
                }
                $f['device'] = $dev;
                return $f;
            }
        }
        throw new \Exception("MAC $mac_addr in vlan $vlan_id not found on L2 devices");
    }


    /**
     * Передать файл к proxies.yml. Пример реализации файла https://github.com/meklis/switcher-core/blob/master/configs/example.proxies.yml
     * Путь к файлу используется для определения прокси через которую необходимо производить подключение к оборудованиюю
     * @param string $path
     * @return $this
     */
    public function setProxyConfigurationPath($path = '') {
        if(!$path) $path = __DIR__ . '/../configs/proxies.yml';
        $this->proxySearchPath = $path;
        return $this;
    }

    /**
     * @param $ip
     * @param $access
     * @return \SwitcherCore\Switcher\Core
     * @throws \ErrorException
     * @throws \SwitcherCore\Exceptions\ModuleErrorLoadException
     * @throws \SwitcherCore\Exceptions\ModuleNotFoundException
     */
    private function getCoreInstance($ip, $access) {
        $connector = (new CoreConnector(Helper::getBuildInConfig(), $this->proxySearchPath))
            ->setTelnetPort($access['port_telnet'])->setMikrotikApiPort($access['port_api']);
        $core = $connector->init($ip, $access['community'], $access['login'], $access['password']);
        return $core;
    }

    /**
     * Производит поиск ARP и FDB, определяет порт включения
     * Возвращает массив вида
     * Array
     * (
     *      [ip] => 172.16.1.3
     *      [port] => 1
     *      [mac] => F8:D1:11:43:5A:6F
     *      [device] => 10.1.1.11
     * )
     *
     * @return array
     * @throws \Exception
     */
    public function search() {
        $arp = $this->getArp();
        $fdb = $this->getFDB($arp['vlan_id'], $arp['mac']);

        return [
            'ip' => $this->ip,
            'mac' => $arp['mac'],
            'port' => $fdb['port'],
            'device' => $fdb['device'],
            'extra' => [
                'fdb' => $fdb,
                'arp' => $arp,
            ]
        ];
    }
}