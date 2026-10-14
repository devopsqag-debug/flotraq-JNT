<?php

namespace JntExpress\Support;

class ErrorMessages
{
    public const MAP = [
        'S10' => 'Duplicate Order Number',
        'S11' => 'Duplicate Waybill Number',
        'S12' => "Order Already Pick Up Can't Cancel",
        'S13' => 'API Key Wrong',
        'S14' => "Order Number can't Empty",
        'S15' => "Waybill Number can't Empty",
        'S17' => 'Number does not meet our rules',
        'S18' => "Sender Address can't Empty",
        'S19' => "Receiver Address can't Empty",
        'S29' => "Sender Postcode can't Empty",
        'S30' => "Receiver Postcode can't Empty",
        'S31' => 'Sender Postcode not Exist',
        'S32' => 'Receiver Postcode not Exist',
        'S34' => 'Customer/Vip Code not Exist',
        'S35' => "Sender Name can't Empty",
        'S36' => "Sender Phone can't Empty",
        'S37' => "Receiver Name can't Empty",
        'S38' => "Receiver Phone can't Empty",
        'S40' => "Weight can't Empty",
        'S41' => "Payment Type can't Empty",
        'S42' => 'Wrong Payment Type',
        'S43' => "Service Type can't Empty",
    ];

    public static function for(?string $code): string
    {
        $code = (string) $code;

        return self::MAP[$code] ?? ($code !== '' ? $code : 'Unknown error');
    }
}
