<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Symfony\Component\VarDumper\Cloner\Data;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExportCollectedYuran implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct($yuran, $class, $oid)
    {
        $this->yuran = $yuran;
        $this->class = $class;
        $this->oid   = $oid;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if($this->class == "ALL" && $this->yuran == "ALL"){
            $data = DB::table('fees_new as fn')
            ->leftJoin('student_fees_new as sfn', 'sfn.fees_id', '=', 'fn.id')
            ->leftJoin('fees_new_organization_user as fnou', 'fnou.fees_new_id', '=', 'fn.id')
            ->leftJoin('organization_user as ou', 'ou.id', '=', 'fnou.organization_user_id')
            ->leftJoin('organization_user_student as ous', 'ous.organization_user_id', '=', 'ou.id')
            ->leftJoin('students as s', 's.id', '=', 'ous.student_id')
            ->where([
                ['fn.status', 1],
                ['fnou.status', 'Paid'],
                ['fn.organization_id', $this->oid]
            ])
            ->orWhere([
                ['fn.status', 1],
                ['sfn.status', 'Paid'],
                ['fn.organization_id', $this->oid]
            ])
            ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 'fn.organization_id as class_name', 'fn.totalAmount', DB::raw('count("s.id") as total'), DB::raw('fn.totalAmount * count("s.id") as sum'))
            ->groupBy('fn.id')
            ->orderBy('fn.category')
            ->orderBy('fn.name')
            ->get();
        }
        elseif($this->class == "ALL" && $this->yuran != "ALL"){
            if($this->yuran == "Kategory A")
            {
                $data = DB::table('fees_new as fn')
                ->leftJoin('fees_new_organization_user as fnou', 'fnou.fees_new_id', '=', 'fn.id')
                ->leftJoin('organization_user as ou', 'ou.id', '=', 'fnou.organization_user_id')
                ->leftJoin('organization_user_student as ous', 'ous.organization_user_id', '=', 'ou.id')
                ->leftJoin('students as s', 's.id', '=', 'ous.student_id')
                ->where([
                    ['fn.category', $this->yuran],
                    ['fn.status', 1],
                    ['fnou.status', 'Paid']
                ])
                ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 'fn.organization_id as class_name', 'fn.totalAmount', DB::raw('count("s.id") as total'), DB::raw('fn.totalAmount * count("s.id") as sum'))
                ->orderBy('fn.name')
                ->get();
            }
            else
            {
                $data = DB::table('fees_new as fn')
                ->leftJoin('student_fees_new as sfn', 'sfn.fees_id', '=', 'fn.id')
                ->where([
                    ['fn.category', $this->yuran],
                    ['fn.status', 1],
                    ['sfn.status', 'Paid'],
                ])
                ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 'fn.organization_id as class_name', 'fn.totalAmount', DB::raw('count("s.id") as total'), DB::raw('fn.totalAmount * count("s.id") as sum'))
                ->groupBy('fn.id')
                ->orderBy('fn.name')
                ->get();
            }
        }
        elseif($this->class != "ALL" && $this->yuran != "ALL"){
            if($this->yuran == "Kategory A")
            {
                $data = DB::table('fees_new as fn')
                ->leftJoin('fees_new_organization_user as fnou', 'fnou.fees_new_id', '=', 'fn.id')
                ->leftJoin('organization_user as ou', 'ou.id', '=', 'fnou.organization_user_id')
                ->leftJoin('organization_user_student as ous', 'ous.organization_user_id', '=', 'ou.id')
                ->leftJoin('students as s', 's.id', '=', 'ous.student_id')
                ->leftJoin('class_student as cs', 'cs.student_id', '=', 's.id')
                ->leftJoin('class_organization as co', 'co.id', '=', 'cs.organclass_id')
                ->leftJoin('classes as c', 'c.id', '=', 'co.class_id')
                ->where([
                    ['fn.category', $this->yuran],
                    ['fn.status', 1],
                    ['c.id', $this->class],
                    ['fnou.status', 'Paid']
                ])
                ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 'c.nama as class_name', 'fn.totalAmount', DB::raw('count("cs.id") as total'), DB::raw('fn.totalAmount * count("s.id") as sum'))
                ->orderBy('c.nama')
                ->orderBy('fn.name')
                ->get();
            }
            else
            {
                $data = DB::table('fees_new as fn')
                ->leftJoin('student_fees_new as sfn', 'sfn.fees_id', '=', 'fn.id')
                ->leftJoin('class_student as cs', 'cs.id', '=', 'sfn.class_student_id')
                ->leftJoin('class_organization as co', 'co.id', '=', 'cs.organclass_id')
                ->leftJoin('classes as c', 'c.id', '=', 'co.class_id')
                ->where([
                    ['fn.category', $this->yuran],
                    ['fn.status', 1],
                    ['c.id', $this->class],
                    ['sfn.status', 'Paid'],
                ])
                ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 'c.nama as class_name', 'fn.totalAmount', DB::raw('count("cs.id") as total'), DB::raw('fn.totalAmount * count("s.id") as sum'))
                ->groupBy('fn.id')
                ->orderBy('c.nama')
                ->orderBy('fn.name')
                ->get();
            }
        }

        foreach($data as $list){
            number_format($list->totalAmount, 2);
            number_format($list->sum, 2);
            if($this->class == "ALL")
            {
                $list->class_name = "Semua Kelas";
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Yuran',
            'Kelas',
            'Harga ($)',
            'Bilangan Telah Bayar',
            'Jumlah Kutipan ($)',
        ];
    }
}
