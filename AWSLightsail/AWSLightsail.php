<?php
require_once __DIR__ . '/func.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Aws\Lightsail\LightsailClient;

set_time_limit(0);

function AWSLightsail_ConfigOptions()
{
    $clientsize = [];

    if (isset($_REQUEST['id'])) {
        $params = Capsule::table('tblproducts')->where('id', $_REQUEST['id'])->first();
    }

    if (!empty($params->configoption1) && !empty($params->configoption2) && !empty($params->configoption3)) {

        try {
            $client = new LightsailClient([
                'region' => $params->configoption3,
                'version' => '2016-11-28',
                'credentials' => [
                    'key' => $params->configoption1,
                    'secret' => $params->configoption2,
                ],
            ]);

            $results = $client->getBundles([
                'includeInactive' => false,
            ]);

            foreach ($results['bundles'] as $result) {
                if ($result['supportedPlatforms'][0] == 'LINUX_UNIX') {
                    $type = '[Linux] ';
                } else if ($result['supportedPlatforms'][0] == 'WINDOWS') {
                    $type = '[Windows] ';
                    continue;
                } else {
                    $type = '[Unknow] ';
                    continue;
                }
                $clientsize[$result['bundleId']] = $type . '$' . $result['price'] . ' ' . $result['cpuCount'] . 'C' . $result['ramSizeInGb'] . 'G ' . $result['diskSizeInGb'] . 'G-SSD ' . ($result['transferPerMonthInGb'] - 10) . 'G-Traffic';
            }
        } catch (\Exception $e) {
            $clientsize = ['error' => '您填写的信息有误'];
        }
    } else {
        $clientsize = ['error' => '请先填写完其他项目并保存'];
    }

    $regions = [
        'us-east-1' => '美国东部(弗吉尼亚州)',
        'us-east-2' => '美国东部(俄亥俄州)',
        'us-west-1' => '[暂不支持] 美国西部(加利福尼亚州)',
        'us-west-2' => '美国西部(俄勒冈州)',
        'af-south-1' => '[暂不支持] 非洲(开普敦)',
        'ap-east-1' => '[暂不支持] 亚洲(中国 香港)',
        'ap-south-1' => '亚洲(印度 孟买)',
        'ap-northeast-1' => '亚洲(日本 东京)',
        'ap-northeast-2' => '亚洲(韩国 首尔)',
        'ap-northeast-3' => '[暂不支持] 亚洲(日本 大阪)',
        'ap-southeast-1' => '亚洲(新加坡)',
        'ap-southeast-2' => '大洋洲(澳大利亚 悉尼)',
        'ca-central-1' => '北美洲(加拿大 中部)',
        'eu-central-1' => '欧洲(德国 法兰克福)',
        'eu-west-1' => '欧洲(爱尔兰)',
        'eu-west-2' => '欧洲(英国 伦敦)',
        'eu-south-1' => '[暂不支持] 欧洲(意大利 米兰)',
        'eu-west-3' => '欧洲(法国 巴黎)',
        'eu-north-1' => '[暂不支持] 欧洲(瑞典 斯德哥尔摩)',
        'me-south-1' => '[暂不支持] 亚洲(巴林)',
        'sa-east-1' => '[暂不支持] 南美洲(巴西 圣保罗)',
    ];

    $images = AWSLightsail_GetImage();
    $configarray = array(
        'Access Key ID'                => array('Type' => 'text', 'Description' => 'AWS帐号安全设置 <a href="https://console.aws.amazon.com/iam/home#/security_credentials">点此打开</a>'),                //1
        'Secret Access Key'                => array('Type' => 'text'),                //2
        //3
        '实例区域'                    => array('Type' => 'dropdown', 'Options' => $regions, 'Description' => '部分区域需要申请'),            //3
        '实例大小'                    => array('Type' => 'dropdown', 'Options' => $clientsize),  //4
        '操作系统'                 => array('Type' => 'dropdown', 'Options' => $images),  //5
        'IP协议'                 => array('Type' => 'dropdown', 'Options' => array('dualstack' => 'IPv4 + IPv6', 'ipv4' => 'IPv4')),  //6
        '名称前缀'                => array('Type' => 'text'),    //7
    );
    unset($images['custom']);
    if (!Capsule::table('tblproductconfiggroups')->where('name', 'AWSLightsail')->exists()) {
        $currencies = Capsule::table("tblcurrencies")->get();
        $cgid = Capsule::table('tblproductconfiggroups')->insertGetId(['name' => 'AWSLightsail', 'description' => 'By AWSLightsail modules']);
        $cid = Capsule::table('tblproductconfigoptions')->insertGetId(['gid' => $cgid, 'optionname' => 'OS', 'optiontype' => '1', 'qtyminimum' => 0, 'qtymaximum' => 0, 'order' => 0, 'hidden' => 0]);
        foreach ($images as $name => $display) {
            $rid = Capsule::table('tblproductconfigoptionssub')->insertGetId(['configid' => $cid, 'optionname' => $name . '|' . $display, 'sortorder' => 0, 'hidden' => 0]);
            foreach ($currencies as $currency) {
                Capsule::table('tblpricing')->insert(['type' => 'configoptions', 'currency' => $currency->id, 'relid' => $rid, 'msetupfee' => 0.00, 'qsetupfee' => 0.00, 'ssetupfee' => 0.00, 'asetupfee' => 0.00, 'bsetupfee' => 0.00, 'tsetupfee' => 0.00, 'monthly' => 0.00, 'monthly' => 0.00, 'semiannually' => 0.00, 'annually' => 0.00, 'biennially' => 0.00, 'triennially' => 0.00]);
            }
        }
    }
    return $configarray;
}

function AWSLightsail_CreateAccount(array $params)
{
    try {
        $os = $params['configoption5'];
        if ($os == 'custom') {
            $os = $params['configoptions']['OS'];
        }

        $client = new LightsailClient([
            'region' => $params['configoption3'],
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $params['configoption1'],
                'secret' => $params['configoption2']
            ],
        ]);

        $key = $client->createKeyPair([
            'keyPairName' => $params['configoption7'] . $params['serviceid'] . '-SSH', // REQUIRED
        ]);

        AWSLightsail_setCustomfieldsValue($params, 'pem', $key['privateKeyBase64']);

        $regions = $client->getRegions([
            'includeAvailabilityZones' => true,
            'includeRelationalDatabaseAvailabilityZones' => false,
        ])['regions'];

        foreach ($regions as $value) {
            if ($value['name'] == $params['configoption3']) {
                $region = $value;
                break;
            }
        }

        if (count($region['availabilityZones']) == 0) {
            return "无可用区";
        }

        $client->createInstances([
            'availabilityZone' => $region['availabilityZones'][rand(0, count($region['availabilityZones']) - 1)]['zoneName'], // REQUIRED
            'blueprintId' => $os, // REQUIRED
            'bundleId' => $params['configoption4'], // REQUIRED
            'instanceNames' => [$params['configoption7'] . $params['serviceid']], // REQUIRED
            'ipAddressType' => $params['configoption6'],
            'keyPairName' => $params['configoption7'] . $params['serviceid'] . '-SSH',
            'userData' => $params['customfields']['cloudinit'],
        ]);

        $ls_info = $client->getInstance([
            'instanceName' => $params['configoption7'] . $params['serviceid'],
        ]);

        foreach ($ls_info['instance']['ipv6Addresses'] as $ipv6addr) {
            $ipv6addrs .= $ipv6addr . PHP_EOL;
        }

        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update(['dedicatedip' => $ls_info['instance']['publicIpAddress'], 'password' => '', 'assignedips' => $ipv6addrs, 'username' => $ls_info['instance']['username'], 'bwlimit' => ($ls_info['instance']['networking']['monthlyTransfer']['gbPerMonthAllocated'] - 10) * 1024, 'lastupdate' => date('Y-m-d H:i:s')]);

        sleep(50);
        AWSLightsail_open_ports($params);
        return 'success';
    } catch (\Exception $e) {
        AWSLightsail_delete_pem($params);
        return $e->getMessage();
    }
}

function AWSLightsail_SuspendAccount(array $params)
{
    $params["force"] = true;
    return AWSLightsail_stop($params);
}


function AWSLightsail_UnsuspendAccount(array $params)
{
    Capsule::table('tblhosting')->where('id', $_REQUEST['id'])->update([
        'lastupdate' => date('Y-m-d H:i:s'),
    ]);
    return AWSLightsail_boot($params);
}


function AWSLightsail_TerminateAccount(array $params)
{
    AWSLightsail_stop($params);
    sleep(50);
    try {
        $client = new LightsailClient([
            'region' => $params['configoption3'],
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $params['configoption1'],
                'secret' => $params['configoption2']
            ],
        ]);

        $client->deleteInstance([
            'forceDeleteAddOns' => true,
            'instanceName' => $params['configoption7'] . $params['serviceid'],
        ]);

        AWSLightsail_delete_pem($params);

        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update(['dedicatedip' => '', 'assignedips' => '', 'username' => '', 'password' => '', 'disklimit' => '', 'bwlimit' => '', 'lastupdate' => '0000-00-00 00:00:00']);
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}


function AWSLightsail_AdminCustomButtonArray()
{
    return array(
        '开机' => 'boot',
        '重启' => 'reboot',
        '关机' => 'shutdown',
        '强制关机' => 'stop',
        '关闭安全组' => 'open_ports',
        '删除私钥' => 'delete_pem',
    );
}

function AWSLightsail_ClientAreaCustomButtonArray()
{
    return array(
        '开机' => 'boot',
        '重启' => 'reboot',
        '关机' => 'shutdown',
        '强制关机' => 'stop',
        '关闭安全组' => 'open_ports',
    );
}


function AWSLightsail_ClientArea(array $params)
{
    if ($params['status'] != 'Active') {
        return;
    }

    if ($_REQUEST['do'] == 'getpem') {
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . strlen($params['customfields']['pem']));
        header("Content-Disposition: attachment; filename=ssh.pem");
        exit($params['customfields']['pem']);
    }

    try {

        $client = new LightsailClient([
            'region' => $params['configoption3'],
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $params['configoption1'],
                'secret' => $params['configoption2']
            ],
        ]);


        $ls_info = $client->getInstance([
            'instanceName' => $params['configoption7'] . $params['serviceid'],
        ]);

        foreach ($ls_info['instance']['ipv6Addresses'] as $ipv6addr) {
            $ipv6addrs .= $ipv6addr . PHP_EOL;
        }

        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update(['dedicatedip' => $ls_info['instance']['publicIpAddress'], 'assignedips' => $ipv6addrs, 'username' => $ls_info['instance']['username'], 'bwlimit' => ($ls_info['instance']['networking']['monthlyTransfer']['gbPerMonthAllocated'] - 10) * 1024]);

        if ($params['configoption5'] == 'custom') {
            $system = $params['configoptions']['OS'];
        } else {
            $system = $params['configoption5'];
        }

        return array(
            'tabOverviewReplacementTemplate' => 'templates/clientarea.tpl',
            'vars' => array(
                'status' => AWSLightsail_Status()[$ls_info['instance']['state']['name']],
                'username' => $ls_info['instance']['username'],
                'system' => AWSLightsail_GetImage()[$system],
                'ip'    => $ls_info['instance']['publicIpAddress'],
                'cpus' => $ls_info['instance']['hardware']['cpuCount'],
                'memory' => $ls_info['instance']['hardware']['ramSizeInGb'],
                'params' => $params,
            ),
        );
    } catch (\Exception $e) {
        return "<h1>服务不可用,请联系管理员</h1>";
    }
}

function AWSLightsail_AdminServicesTabFields(array $params)
{

    if ($params['status'] == 'Terminated' || $params['status'] == 'Pending') {
        return;
    }

    try {

        $client = new LightsailClient([
            'region' => $params['configoption3'],
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $params['configoption1'],
                'secret' => $params['configoption2']
            ],
        ]);


        $ls_info = $client->getInstance([
            'instanceName' => $params['configoption7'] . $params['serviceid'],
        ]);

        foreach ($ls_info['instance']['ipv6Addresses'] as $ipv6addr) {
            $ipv6addrs .= $ipv6addr . PHP_EOL;
        }

        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update(['dedicatedip' => $ls_info['instance']['publicIpAddress'], 'assignedips' => $ipv6addrs, 'username' => $ls_info['instance']['username'], 'bwlimit' => ($ls_info['instance']['networking']['monthlyTransfer']['gbPerMonthAllocated'] - 10) * 1024]);
        return array('实例状态' =>  AWSLightsail_Status()[$ls_info['instance']['state']['name']],);
    } catch (\Exception $e) {
        return array('Error' => $e->getMessage());
    }
}

function AWSLightsail_shutdown(array $params)
{
    if (!isset($_SESSION['adminid']) && $params['status'] != 'Active' && php_sapi_name() != 'cli') {
        return '权限不足';
    }

    try {

        $client = new LightsailClient([
            'region' => $params['configoption3'],
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $params['configoption1'],
                'secret' => $params['configoption2']
            ],
        ]);

        $ls_info = $client->getInstance([
            'instanceName' => $params['configoption7'] . $params['serviceid'],
        ]);

        if ($ls_info['instance']['state']['name'] == 'running') {
            $client->stopInstance([
                'force' => false,
                'instanceName' => $params['configoption7'] . $params['serviceid'], // REQUIRED
            ]);
        }
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AWSLightsail_stop(array $params)
{

    if (!isset($_SESSION['adminid']) && $params['status'] != 'Active' && php_sapi_name() != 'cli' && !$params["force"]) {
        return '权限不足';
    }

    try {
        $client = new LightsailClient([
            'region' => $params['configoption3'],
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $params['configoption1'],
                'secret' => $params['configoption2']
            ],
        ]);

        $ls_info = $client->getInstance([
            'instanceName' => $params['configoption7'] . $params['serviceid'],
        ]);

        if ($ls_info['instance']['state']['name'] == 'running') {
            $client->stopInstance([
                'force' => true,
                'instanceName' => $params['configoption7'] . $params['serviceid'], // REQUIRED
            ]);
        }
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AWSLightsail_boot(array $params)
{

    if (!isset($_SESSION['adminid']) && $params['status'] != 'Active' && php_sapi_name() != 'cli') {
        return '权限不足';
    }

    try {

        $client = new LightsailClient([
            'region' => $params['configoption3'],
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $params['configoption1'],
                'secret' => $params['configoption2']
            ],
        ]);

        $client->startInstance([
            'instanceName' => $params['configoption7'] . $params['serviceid'], // REQUIRED
        ]);

        $ls_info = $client->getInstance([
            'instanceName' => $params['configoption7'] . $params['serviceid'],
        ]);

        foreach ($ls_info['instance']['ipv6Addresses'] as $ipv6addr) {
            $ipv6addrs .= $ipv6addr . PHP_EOL;
        }

        Capsule::table('tblhosting')->where('id', $params['serviceid'])->update(['dedicatedip' => $ls_info['instance']['publicIpAddress'], 'assignedips' => $ipv6addrs]);

        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}


function AWSLightsail_reboot(array $params)
{

    if (!isset($_SESSION['adminid']) && $params['status'] != 'Active' && php_sapi_name() != 'cli') {
        return '权限不足';
    }

    try {
        $client = new LightsailClient([
            'region' => $params['configoption3'],
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $params['configoption1'],
                'secret' => $params['configoption2']
            ],
        ]);

        $client->rebootInstance([
            'instanceName' => $params['configoption7'] . $params['serviceid'], // REQUIRED
        ]);
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}


function AWSLightsail_delete_pem(array $params)
{
    try {
        $client = new LightsailClient([
            'region' => $params['configoption3'],
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $params['configoption1'],
                'secret' => $params['configoption2']
            ],
        ]);

        $client->deleteKeyPair([
            'keyPairName' => $params['configoption7'] . $params['serviceid'] . '-SSH',
        ]);
        AWSLightsail_setCustomfieldsValue($params, 'pem', '');
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AWSLightsail_open_ports(array $params)
{

    try {
        $client = new LightsailClient([
            'region' => $params['configoption3'],
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $params['configoption1'],
                'secret' => $params['configoption2']
            ],
        ]);
        $client->openInstancePublicPorts([
            'instanceName' => $params['configoption7'] . $params['serviceid'], // REQUIRED
            'portInfo' => [ // REQUIRED
                'cidrs' => ['0.0.0.0/0'],
                'protocol' => 'all',
                'fromPort' => 0,
                'toPort' => 65535,
            ],
        ]);

        $client->openInstancePublicPorts([
            'instanceName' => $params['configoption7'] . $params['serviceid'], // REQUIRED
            'portInfo' => [ // REQUIRED
                'ipv6Cidrs' => ['::/0'],
                'protocol' => 'all',
                'fromPort' => 0,
                'toPort' => 65535,
            ],
        ]);
        return 'success';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

function AWSLightsail_GetImage()
{

    $images = [
        'custom' => '用户自行选择',

        'windows_server_2019' => 'Windows Server 2019',
        'windows_server_2016' => 'Windows Server 2016',
        'windows_server_2012' => 'Windows Server 2012 R2',
        'windows_server_2016_sql_2016_express' => 'Windows SQL Server 2016 Express',

        'centos_7_1901_01' => 'CentOS 7',
        'ubuntu_16_04_2' => 'Ubuntu 16.04',
        'ubuntu_18_04' => 'Ubuntu 18.04',
        'ubuntu_20_04' => 'Ubuntu 20.04',
        'debian_8_7' => 'Debian 8',
        'debian_9_5' => 'Debian 9',
        'debian_10' => 'Debian 10',
        'opensuse_15_1' => 'OpenSUSE 15',
        'freebsd_12' => 'FreeBSD 12',
    ];
    return $images;
}
