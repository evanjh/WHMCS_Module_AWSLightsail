<?php
require_once __DIR__ . '/../../../init.php';

use Illuminate\Database\Capsule\Manager as Capsule;

set_time_limit(0);
function AWSLightsail_Status()
{
    return [
        'running' => '<a style="color: green;">运行中</a>',
        'stopping' => '<a style="color: orange;">关机中</a>',
        'stopped' => '<a style="color: red;">已关机</a>',
    ];
}

function AWSLightsail_setCustomfieldsValue(array $params, string $field, string $value)
{
    $res = Capsule::table('tblcustomfields')->where('relid', $params['pid'])->where('fieldname', $field)->first();
    if ($res) {
        $fieldValue = Capsule::table('tblcustomfieldsvalues')->where('relid', $params['serviceid'])->where('fieldid', $res->id)->first();
        if ($fieldValue) {
            if ($fieldValue->value !== $value) {
                Capsule::table('tblcustomfieldsvalues')
                    ->where('relid', $params['serviceid'])
                    ->where('fieldid', $res->id)
                    ->update(
                        [
                            'value' => $value,
                        ]
                    );
            }
        } else {
            Capsule::table('tblcustomfieldsvalues')
                ->insert(
                    [
                        'relid'   => $params['serviceid'],
                        'fieldid' => $res->id,
                        'value'   => $value,
                    ]
                );
        }
    }
}
