<?php

namespace App\Exports;

use App\Models\Expenses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Http\Controllers\Excel;
class ParentPayStatusExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public function __construct($payStatus,$expensesId)
    {
        $this->payStatus = $payStatus;
        $this->expensesId = $expensesId;
    }

    public function collection()
    {

        $list = DB::table('student_expenses')
        ->join('class_student','class_student.id','=','student_expenses.class_student_id')
        ->join('class_organization','class_organization.id','=','class_student.organclass_id')
        ->join('classes','classes.id','=','class_organization.class_id')
        ->join('students','students.id','=','class_student.student_id')
        ->join('organization_user_student','organization_user_student.student_id','=','students.id')
        ->join('organization_user','organization_user.id','=','organization_user_student.organization_user_id')
        ->join('users','users.id','=','organization_user.user_id')
        ->where('student_expenses.expenses_id',$this->expensesId)
        ->select('users.name as parentName','students.parent_tel as parentTel','students.nama as studentName','classes.nama as className','student_expenses.status as payStatus');
        
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
            'Nama Penjaga',
            'Nombor Telefon Penjaga',
            'Nama Pelajar',
            'Nama Kelas',
            'Status Pembayaran'
        ];
    }
}
