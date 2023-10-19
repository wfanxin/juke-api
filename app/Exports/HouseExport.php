<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class HouseExport implements FromView, WithColumnFormatting
{
    protected $exportData;
    public function __construct(array $exportData)
    {
        $this->exportData = $exportData;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        return view('exports.houseExport', [
            'exportData' => $this->exportData
        ]);
    }

    /**
     * 设置单元格格式（保留两位小数点）
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }
}
