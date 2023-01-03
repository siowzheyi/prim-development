<?php

namespace App\Exports;

use App\Models\Expenses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Http\Controllers\Excel;
class ExpensesPayStatusExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public function __construct($payStatus,$organ)
    {
        $this->payStatus = $payStatus;
        $this->organ = $organ;
    }

    public function collection()
    {

        $list = DB::table('expenses')
                ->join('recurrings','recurrings.id','=','expenses.recurring_id')
                ->join('student_expenses','student_expenses.expenses_id','=','expenses.id')
                ->select('expenses.name as expenses_name','expenses.description','expenses.amount',
                            'recurrings.name as recurrings_name',
                             DB::raw("count(student_expenses.status) AS payStatus"))
                ->where('expenses.organization_id','=',$this->organ)
                ->where('expenses.status','active')
                ->orderBy('expenses.start_date')
                ->groupBy('expenses.id');

        if($this->payStatus == 'all')
        {
            $list = $list->get();
        }
        elseif($this->payStatus == 'paid')
        {
            $list = $list->where('student_expenses.status','=','paid')->get();
        }
        elseif($this->payStatus == 'unpaid')
        {
            $list = $list->where('student_expenses.status','=','unpaid')->get();
        }

        return $list;
    }

    public function headings(): array
    {
        return [
            'Nama Perbelanjaan',
            'Diskripsi Perbelanjaan',
            'Amaun Perbelanjaan',
            'Jenis Berulangan',
            'Bilangan Pembayar'
        ];
    }
}
