<link rel="stylesheet" href="{$systemurl}modules/servers/AWSLightsail/theme/style.css">
<link rel="stylesheet" href="{$systemurl}modules/servers/AWSLightsail/theme/flags.css">
<div class="row m-b-15">
    <h3>产品信息 <small>Product Detail</small> | {$product}</h3>
    <div class="col-md-6 col-sm-12">
        <h4>主要信息 <small>Main Detail</small></h4>
    </div>
</div>
<div id="YVSY">
    <div class="row">

        <div class="col-md-4 col-sm-12">

            <div class="box">
                <div class="boxTitle">
                    CPU核心
                </div>
                <div>
                    <span class="boxContent">{$cpus} 核心</span>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    内存
                </div>
                <div>
                    <span class="boxContent">{$memory} GB</span>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">

            <div class="box">
                <div class="boxTitle">
                    到期时间
                </div>
                <div>
                    <span class="boxContent" style="font-size:14px;">{$nextduedate}</span>
                </div>
            </div>
        </div>

    </div>
</div>
<div class="row m-b-15">
    <div class="col-md-12 col-sm-12">
        <h4>实例信息 <small>Connect Detail</small> | <a href="?action=productdetails&id={$serviceid}&do=getpem"
                class="btn btn-default"><i class="fas fa-download"></i> 下载SSH私钥</a></h4>
    </div>
</div>
<div id="YVSY">
    <div class="row">
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    状态
                </div>
                <div>
                    <span class="boxContent">{$status}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    IP地址
                </div>
                <div>
                    <span class="boxContent">{$ip}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    操作系统
                </div>
                <div>
                    <span class="boxContent">{$system}</span>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    SSH用户名
                </div>
                <div>
                    <span class="boxContent">{$username}</span>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="foot">
    <p>执行 sudo -i 可以提升到root权限</p>
    <p>控制台关机再开机可以更换IP</p>
</div>

<div class="row m-b-15">
    <div class="col-md-12 col-sm-12">
        <h4>流量信息 <small>Traffic Detail</small>
    </div>
</div>

<div id="YVSY">
    <div class="row">
        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    总流量
                </div>
                <div>
                    <span class="boxContent">{$params['model']['bwlimit']} MB</span>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
            <div class="box">
                <div class="boxTitle">
                    已用流量
                </div>
                <div>
                    <span class="boxContent">{$params['model']['bwusage']} MB</span>
                </div>
            </div>
        </div>
    </div>
</div>
<p class="foot">流量每月1日重置</p>

<div class="col-xs-12 foot text-center">
    <p>Copyright ZeroTime Team. All Rights Reserved.</p>
    <p> Based On AWSLightsail</a></p>
    <p><a href="https://shop.zeroteam.top/submitticket.php?step=2&amp;deptid=2" style="color: #999;">Report a Bug</a>
    </p>
</div>

<style>
    .foot {
        font-size: 12px;
        color: #999;
    }
</style>