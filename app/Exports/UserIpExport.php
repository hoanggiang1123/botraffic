<?php

namespace App\Exports;

use App\Models\UserIp;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class UserIpExport implements WithHeadings, WithMapping, FromCollection
{
    use Exportable;

    public function __construct($userIp)
    {
        $this->userIp = $userIp;
    }
    public function collection()
    {
        return $this->userIp;
    }

    public function map($userIp): array
    {
        $ip = $userIp->ip ? $userIp->ip : 'N/A';
        $browserName =  $userIp->browser_name ?  $userIp->browser_name : 'N/A';
        $hostName =  $userIp->hostname ?  $userIp->hostname : 'N/A';
        $link =  $userIp->link ?  $userIp->link : 'N/A';
        $time =  $userIp->created_at ?  date('d/m/Y H:i:s', strtotime($userIp->created_at)) : 'N/A';

        return [
            $ip, $browserName, $hostName, $link, $time
        ];
    }

    public function headings(): array
    {
        return [
            'Ip', 'BrowserName', 'Hostname', 'Link ', 'Time'
        ];
    }

}
