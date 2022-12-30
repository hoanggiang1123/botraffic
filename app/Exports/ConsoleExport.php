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
        $keyword = $tracker->keyword_name ? $tracker->keyword_name : 'N/A';
        $website =  $tracker->url ?  $tracker->url : 'N/A';
        $totalClick =  $tracker->total_click ? $tracker->total_click : 'N/A';
        $LimitClick =  $tracker->traffic ?  $tracker->traffic : 'N/A';
        $clickPerday =  $tracker->total_click_perday ?  $tracker->total_click_perday : 'N/A';
        $internal =  $tracker->anchor_text ?  $tracker->anchor_text : 'N/A';
        $handle =  $tracker->name ?  $tracker->name : 'N/A';

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
