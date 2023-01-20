<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Symfony\Component\VarDumper\Cloner\Data;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ExportCollectedYuran implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct($yuran, $class, $oid, $start, $end)
    {
        $this->yuran = $yuran;
        $this->class = $class;
        $this->oid   = $oid;
        $this->start = $start;
        $this->end   = $end;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if($this->class == "ALL" && $this->yuran == "ALL"){
            $data = DB::table('transactions as t')
            ->leftJoin('fees_new_organization_user as fnou', 'fnou.transaction_id', '=', 't.id')
            ->leftJoin('fees_new as fn', 'fn.id', '=', 'fnou.fees_new_id')
            ->where('t.status', 'Success')
            ->where('fn.organization_id', $this->oid)
            ->where('fnou.status', 'Paid')
            ->whereBetween('t.datetime_created', [$this->start, $this->end])
            ->whereNotNull('fn.name')
            ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 't.status as class_name', 'fn.totalAmount', DB::raw('count("fnou.organization_user_id") as total'), DB::raw('fn.totalAmount * count("fnou.organization_user_id") as sum'))
            ->groupBy('fn.id')
            ->orderBy('fn.name')
            ->get();

            $data1 = DB::table('transactions as t')
            ->leftJoin('fees_transactions_new as ftn', 'ftn.transactions_id', '=', 't.id')
            ->leftJoin('student_fees_new as sfn', 'sfn.id', '=', 'ftn.student_fees_id')
            ->leftJoin('fees_new as fn', 'fn.id', '=', 'sfn.fees_id')
            ->where('t.status', 'Success')
            ->where('fn.organization_id', $this->oid)
            ->where('sfn.status', 'Paid')
            ->whereBetween('t.datetime_created', [$this->start, $this->end])
            ->whereNotNull('fn.name')
            ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 't.status as class_name', 'fn.totalAmount', DB::raw('count("fn.class_student_id") as total'), DB::raw('fn.totalAmount * count("fn.class_student_id") as sum'))
            ->groupBy('fn.id')
            ->orderBy('fn.name')
            ->get();

            $data = array_merge($data->toArray(), $data1->toArray());
        }
        elseif($this->class == "ALL" && $this->yuran != "ALL"){
            if($this->yuran == "Kategory A")
            {
                $data = DB::table('transactions as t')
                ->leftJoin('fees_new_organization_user as fnou', 'fnou.transaction_id', '=', 't.id')
                ->leftJoin('fees_new as fn', 'fn.id', '=', 'fnou.fees_new_id')
                ->where('t.status', 'Success')
                ->where('fn.organization_id', $this->oid)
                ->where('fnou.status', 'Paid')
                ->where('fn.category', $this->yuran)
                ->whereBetween('t.datetime_created', [$this->start, $this->end])
                ->whereNotNull('fn.name')
                ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 't.status as class_name', 'fn.totalAmount', DB::raw('count("fnou.organization_user_id") as total'), DB::raw('fn.totalAmount * count("fnou.organization_user_id") as sum'))
                ->groupBy('fn.id')
                ->orderBy('fn.name')
                ->get();
            }
            else
            {                   
                $data = DB::table('transactions as t')
                ->leftJoin('fees_transactions_new as ftn', 'ftn.transactions_id', '=', 't.id')
                ->leftJoin('student_fees_new as sfn', 'sfn.id', '=', 'ftn.student_fees_id')
                ->leftJoin('fees_new as fn', 'fn.id', '=', 'sfn.fees_id')
                ->where('t.status', 'Success')
                ->where('fn.organization_id', $this->oid)
                ->where('sfn.status', 'Paid')
                ->where('fn.category', $this->yuran)
                ->whereBetween('t.datetime_created', [$this->start, $this->end])
                ->whereNotNull('fn.name')
                ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 't.status as class_name', 'fn.totalAmount', DB::raw('count("fn.class_student_id") as total'), DB::raw('fn.totalAmount * count("fn.class_student_id") as sum'))
                ->groupBy('fn.id')
                ->orderBy('fn.name')
                ->get();
            }
        }
        elseif($this->class != "ALL" && $this->yuran != "ALL"){
            if($this->yuran == "Kategory A")
            {
                $data = DB::table('transactions as t')
                ->leftJoin('fees_new_organization_user as fnou', 'fnou.transaction_id', '=', 't.id')
                ->leftJoin('fees_new as fn', 'fn.id', '=', 'fnou.fees_new_id')
                ->leftJoin('organization_user as ou', 'ou.id', '=', 'fnou.organization_user_id')
                ->leftJoin('organization_user_student as ous', 'ous.organization_user_id', '=', 'ou.id')
                ->leftJoin('students as s', 's.id', '=', 'ous.student_id')
                ->leftJoin('class_student as cs', 'cs.student_id', '=', 's.id')
                ->leftJoin('class_organization as co', 'co.id', '=', 'cs.organclass_id')
                ->leftJoin('classes as c', 'c.id', '=', 'co.class_id')
                ->where('t.status', 'Success')
                ->where('fn.organization_id', $this->oid)
                ->where('fnou.status', 'Paid')
                ->where('fn.category', $this->yuran)
                ->where('c.id', $this->class)
                ->whereBetween('t.datetime_created', [$this->start, $this->end])
                ->whereNotNull('fn.name')
                ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 'c.nama as class_name', 'fn.totalAmount', DB::raw('count("fnou.organization_user_id") as total'), DB::raw('fn.totalAmount * count("fnou.organization_user_id") as sum'))
                ->groupBy('fn.id')
                ->orderBy('c.nama')
                ->orderBy('fn.name')
                ->get();
            }
            else
            {
                $data = DB::table('transactions as t')
                ->leftJoin('fees_transactions_new as ftn', 'ftn.transactions_id', '=', 't.id')
                ->leftJoin('student_fees_new as sfn', 'sfn.id', '=', 'ftn.student_fees_id')
                ->leftJoin('fees_new as fn', 'fn.id', '=', 'sfn.fees_id')
                ->leftJoin('class_student as cs', 'cs.id', '=', 'sfn.class_student_id')
                ->leftJoin('class_organization as co', 'co.id', '=', 'cs.organclass_id')
                ->leftJoin('classes as c', 'c.id', '=', 'co.class_id')
                ->where('t.status', 'Success')
                ->where('fn.organization_id', $this->oid)
                ->where('sfn.status', 'Paid')
                ->where('c.id', $this->class)
                ->where('fn.category', $this->yuran)
                ->whereBetween('t.datetime_created', [$this->start, $this->end])
                ->whereNotNull('fn.name')
                ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 'c.nama as class_name', 'fn.totalAmount', DB::raw('count("fn.class_student_id") as total'), DB::raw('fn.totalAmount * count("fn.class_student_id") as sum'))
                ->groupBy('fn.id')
                ->orderBy('c.nama')
                ->orderBy('fn.name')
                ->get();
            }

            // dd($data);
        }

        foreach($data as $list){
            number_format($list->totalAmount, 2);
            number_format($list->sum, 2);
            if($this->class == "ALL")
            {
                $list->class_name = "Semua Kelas";
            }
        }

        return collect($data);
    }

    public function headings(): array
    {
        return 
        [
            ['Laporan Permintaan Pelajar pada ' . explode(" ",$this->start)[0] .' hingga '. explode(" ",$this->end)[0]],
            ['Yuran',
            'Kelas',
            'Harga ($)',
            'Bilangan Telah Bayar',
            'Jumlah Kutipan ($)',],
        ];
    }
}
