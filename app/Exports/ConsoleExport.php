<?php

namespace App\Exports;

use App\Models\Console;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ConsoleExport implements WithHeadings, WithMapping, FromCollection
{
    use Exportable;

    public function __construct($tracker)
    {
        $this->tracker = $tracker;
    }
    public function collection()
    {
        return $this->tracker;
    }

    public function map($tracker): array
    {
        $keyword = $tracker->keyword && $tracker->keyword->name ?  $tracker->keyword->name : 'N/A';
        $website =  $tracker->keyword && $tracker->keyword->url ?  $tracker->keyword->url : 'N/A';
        $totalClick =  $tracker->keyword && $tracker->keyword->total_click ?  $tracker->keyword->total_click : 'N/A';
        $LimitClick =  $tracker->keyword && $tracker->keyword->traffic ?  $tracker->keyword->traffic : 'N/A';
        $clickPerday =  $tracker->keyword && $tracker->keyword->total_click_perday ?  $tracker->keyword->total_click_perday : 'N/A';
        $internal =  $tracker->internal && $tracker->internal->anchor_text ?  $tracker->internal->anchor_text : 'N/A';
        $handle =  $tracker->user && $tracker->user->name ?  $tracker->user->name : 'N/A';

        return [
            $keyword, $website, $totalClick, $LimitClick, $clickPerday, $internal, $handle
        ];
    }

    public function headings(): array
    {
        return [
            'Từ khóa', 'Website', 'Tổng Click', 'Limit Click ', 'CLick/ngày', 'Internal Keyword', 'Handled by'
        ];
    }

}
