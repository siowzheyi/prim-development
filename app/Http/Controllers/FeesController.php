<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Fee;
use App\Models\Fee_New;
use App\Models\Category;
use App\Models\ClassModel;
use App\Models\Transaction;
use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AppBaseController;
use App\Exports\ExportYuranStatus;
use App\Exports\ExportClassYuranStatus;
use App\Exports\ExportCollectedYuran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Psy\Command\WhereamiCommand;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\VarDumper\Cloner\Data;
use PDF;
use App\Mail\NotifyFee;

class FeesController extends AppBaseController
{
    public function index()
    {
        //
        $fees = DB::table('fees')->orderBy('nama')->get();
        $organization = $this->getOrganizationByUserId();
        $listcategory = DB::table('categories')->get();
        return view('pentadbir.fee.index', compact('fees', 'listcategory', 'organization'));
    }



    public function create()
    {
        $organization = $this->getOrganizationByUserId();

        return view('pentadbir.fee.add', compact('organization'));
    }

    public function store(Request $request)
    {
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        // get type org
        // get year from class name
        $fee = DB::table('fees')
            ->join('class_fees', 'class_fees.fees_id', '=', 'fees.id')
            ->join('class_organization', 'class_fees.class_organization_id', '=', 'class_organization.id')
            ->join('classes', 'class_organization.class_id', '=', 'classes.id')
            ->join('organizations', 'organizations.id', '=', 'class_organization.organization_id')
            ->select('fees.id as feeid', 'fees.nama as feename', 'fees.totalamount', 'organizations.id as organization_id', 'organizations.type_org', 'classes.nama')
            ->where('fees.id', $id)
            ->first();

        $aa = $fee->nama;
        $getyear = substr($aa, 0, 1);

        $getallclass = DB::table('organizations')
            ->join('class_organization', 'class_organization.organization_id', '=', 'organizations.id')
            ->join('classes', 'classes.id', '=', 'class_organization.class_id')
            ->select('organizations.id as oid', 'organizations.nama as organizationname', 'classes.id as cid', 'classes.nama as classname')
            ->where('organizations.id', $fee->organization_id)
            ->where('classes.nama', 'LIKE', '%' . $getyear . '%')
            ->orderBy('classes.nama')
            ->get();

        $getclass = DB::table('fees')
            ->join('class_fees', 'class_fees.fees_id', '=', 'fees.id')
            ->join('class_organization', 'class_fees.class_organization_id', '=', 'class_organization.id')
            ->join('classes', 'class_organization.class_id', '=', 'classes.id')
            ->select('fees.id as feeid', 'fees.nama as feename', 'fees.totalamount', 'class_organization.organization_id', 'classes.id as cid', 'classes.nama as classname')
            ->where('fees.id', $id)
            ->orderBy('classes.nama')
            ->get();

        // $getclassid = $getclass->cid;

        // dd($getclass);
        $organization = $this->getOrganizationByUserId();
        return view('pentadbir.fee.update', compact('fee', 'organization', 'getyear', 'getclass', 'getallclass'));
    }

    public function update(Request $request, $id)
    {
        $class = $request->get('cb_class');

        $req = DB::table('organizations')
            ->join('class_organization', 'class_organization.organization_id', '=', 'organizations.id')
            ->join('classes', 'classes.id', '=', 'class_organization.class_id')
            ->select('organizations.id as oid', 'organizations.nama as organizationname', 'classes.id as cid', 'classes.nama as cname', 'class_organization.id as co_id')
            ->where('organizations.id', $request->get('organization'))
            ->whereIn('classes.id', $class)
            ->get()->toArray();

        //get all class that have been store with that fees
        $getclassfees = DB::table('fees')
            ->join('class_fees', 'class_fees.fees_id', '=', 'fees.id')
            ->join('class_organization', 'class_fees.class_organization_id', '=', 'class_organization.id')
            ->join('classes', 'class_organization.class_id', '=', 'classes.id')
            ->select('fees.id as feeid', 'fees.nama as feename', 'fees.totalamount', 'class_organization.organization_id', 'classes.id as cid', 'classes.nama as classname')
            ->where('fees.id', $id)
            ->get()->toArray();


        for ($i = 0; $i < count($req); $i++) {

            //check if that kelas (in request) have been store with that fees or not
            $query = DB::table('fees')
                ->join('class_fees', 'class_fees.fees_id', '=', 'fees.id')
                ->join('class_organization', 'class_fees.class_organization_id', '=', 'class_organization.id')
                ->join('classes', 'class_organization.class_id', '=', 'classes.id')
                ->select('fees.id as feeid', 'fees.nama as feename', 'fees.totalamount', 'class_organization.organization_id', 'classes.id as cid', 'classes.nama as classname')
                ->where('fees.id', $id)
                ->where('class_fees.class_organization_id', $req[$i]->co_id)
                ->first();

            for ($j = 0; $j < count($getclassfees); $j++) {
                if (is_null($query)) {
                    // dd('haha');

                    DB::table('class_fees')->insert([
                        'status'                =>  '1',
                        'class_organization_id' =>  $req[$i]->co_id,
                        'fees_id'               =>  $id
                    ]);
                } elseif ($req[$i]->co_id != $getclassfees[$j]) {
                    DB::table('class_fees')
                        ->where('fees_id', $id)
                        ->update([
                            'status'                =>  '0'
                        ]);
                } else {
                    DB::table('class_fees')
                        ->where('fees_id', $id)
                        ->update([
                            'status'                =>  '1',
                            'class_organization_id' =>  $req[$i]->co_id
                        ]);
                }
            }
        }
    }

    public function destroy($id)
    {
        // $result = DB::table('fees_new')
        //     ->where('id', '=', $id)
        //     ->delete();
        
        $result = DB::table('fees_new')
            ->where('id', $id)
            ->update([
                'status' =>  '0'
            ]); 

        if ($result) {
            Session::flash('success', 'Yuran Berjaya Dipadam');
            return View::make('layouts/flash-messages');
        } else {
            Session::flash('error', 'Yuran Gagal Dipadam');
            return View::make('layouts/flash-messages');
        }
    }

    public function getOrganizationByUserId()
    {
        $userId = Auth::id();
        if (Auth::user()->hasRole('Superadmin')) {
            return Organization::all();
        } elseif (Auth::user()->hasRole('Pentadbir') || Auth::user()->hasRole('Guru')) {

            // user role pentadbir n guru
            return Organization::whereHas('user', function ($query) use ($userId) {
                $query->where('user_id', $userId)->Where(function ($query) {
                    $query->where('organization_user.role_id', '=', 4)
                        ->Orwhere('organization_user.role_id', '=', 5);
                });
            })->get();
        } else {
            // user role ibu bapa
            return Organization::whereHas('user', function ($query) use ($userId) {
                $query->where('user_id', $userId)->where('role_id', '6')->OrWhere('role_id', '7')->OrWhere('role_id', '8');
            })->get();
        }
    }

    public function sendEmail($id, $category){
        
        if($category == "A"){
            $arrayRecipientEmail = DB::table('users')
            ->join('organization_user', 'organization_user.user_id', '=', 'users.id')
            ->join('fees_new_organization_user as fnou', 'fnou.organization_user_id', '=', 'organization_user.id')
            ->join('fees_new', 'fees_new.id', '=', 'fnou.fees_new_id')
            ->where('fees_new.status', 1)
            ->where('fees_new.id', $id)
            ->select('users.email', 'fees_new.end_date', 'fees_new.name')
            ->distinct()
            ->get();
        }
        else{
            $arrayRecipientEmail = DB::table('users')
            ->join('organization_user', 'organization_user.user_id', '=', 'users.id')
            ->join('organization_user_student as ous', 'ous.organization_user_id', '=', 'organization_user.id')
            ->join('class_student as cs', 'cs.student_id', '=', 'ous.student_id')
            ->join('student_fees_new as sfn', 'sfn.class_student_id', '=', 'cs.id')
            ->join('fees_new', 'fees_new.id', '=', 'sfn.fees_id')
            ->where('fees_new.status', 1)
            ->where('fees_new.id', $id)
            ->select('users.email', 'fees_new.end_date', 'fees_new.name')
            ->distinct()
            ->get();
        }
        // dd($arrayRecipientEmail);

        if (isset($arrayRecipientEmail)) {
            foreach ($arrayRecipientEmail as $email) {
                Mail::to($email->email)->send(new NotifyFee($email->name, $email->end_date));

                if (Mail::failures()) {
                    return response()->Fail('Sorry! Please try again latter');
                }
            }
        } 
    }

    public function fetchYear(Request $request)
    {
        $oid = $request->get('oid');
        $category = Category::where('organization_id', $oid)->get();

        $list = DB::table('organizations')
            ->select('organizations.id as oid', 'organizations.nama as organizationname', 'organizations.type_org')
            ->where('organizations.id', $oid)
            ->first();

        return response()->json(['success' => $list, 'category' => $category]);
    }


    public function fetchClass(Request $request)
    {

        // dd($request->get('schid'));
        $oid    = $request->get('oid');
        $year   = $request->get('year');

        // dd($year);

        $list = DB::table('organizations')
            ->join('class_organization', 'class_organization.organization_id', '=', 'organizations.id')
            ->join('classes', 'classes.id', '=', 'class_organization.class_id')
            ->select('organizations.id as oid', 'organizations.nama as organizationname', 'classes.id as cid', 'classes.nama as cname')
            ->where('organizations.id', $oid)
            ->where('classes.nama', 'LIKE', '%' . $year . '%')
            ->where('classes.status', 1)
            ->orderBy('classes.nama')
            ->get();

        return response()->json(['success' => $list]);
    }

    public function feesReport()
    {
        $organization = $this->getOrganizationByUserId();

        $all_student = DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->where('class_organization.organization_id', 22)
            ->count();

        // dd($all_student);
        $student_complete = DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->where('class_organization.organization_id', 22)
            ->where('class_student.fees_status', 'Completed')
            ->count();

        $student_notcomplete = DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->where('class_organization.organization_id', 22)
            ->where('class_student.fees_status', 'Not Complete')
            ->count();

        $all_parent =  DB::table('organization_user')
            ->where('organization_id', 22)
            ->where('role_id', 6)
            ->where('status', 1)
            ->count();

        $parent_complete =  DB::table('organization_user')
            ->where('organization_id', 22)
            ->where('role_id', 6)
            ->where('status', 1)
            ->where('fees_status', 'Completed')
            ->count();

        $parent_notcomplete =  DB::table('organization_user')
            ->where('organization_id', 22)
            ->where('role_id', 6)
            ->where('status', 1)
            ->where('fees_status', 'Not Complete')
            ->count();

        // dd($all_student);

        return view('fee.report', compact('organization', 'all_student', 'student_complete', 'student_notcomplete', 'all_parent', 'parent_complete', 'parent_notcomplete'));
    }

    public function feesReportByOrganizationId(Request $request)
    {
        $oid = $request->oid;

        $all_student = DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->where([
                ['class_organization.organization_id', $oid],
                ['class_student.status', 1]
            ])
            ->count();

        $student_complete = DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->where('class_organization.organization_id', $oid)
            ->where('class_student.status', 1)
            ->where('class_student.fees_status', 'Completed')
            ->count();

        $student_notcomplete = DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->where('class_organization.organization_id', $oid)
            ->where('class_student.status', 1)
            ->where('class_student.fees_status', 'Not Complete')
            ->count();

        $catB_student = count(DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
            ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
            ->where([
                ['class_organization.organization_id', $oid],
                ['fees_new.category', 'Kategory B'],
                ['class_student.status', 1],
                ['fees_new.status', 1]
            ])
            ->whereIn('student_fees_new.status', ['Debt', 'Paid'])
            ->groupBy('students.id')
            ->get());

        $catB_notcomplete = count(DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
            ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
            ->where([
                ['class_organization.organization_id', $oid],
                ['fees_new.category', 'Kategory B'],
                ['class_student.status', 1],
                ['fees_new.status', 1],
                ['student_fees_new.status', 'Debt']
            ])
            ->groupBy('students.id')
            ->get());
        
        $catB_complete = $catB_student - $catB_notcomplete;

        $catC_student = count(DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
            ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
            ->where([
                ['class_organization.organization_id', $oid],
                ['fees_new.category', 'Kategory C'],
                ['class_student.status', 1],
                ['fees_new.status', 1]
            ])
            ->whereIn('student_fees_new.status', ['Debt', 'Paid'])
            ->groupBy('students.id')
            ->get());

        $catC_notcomplete = count(DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
            ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
            ->where([
                ['class_organization.organization_id', $oid],
                ['fees_new.category', 'Kategory C'],
                ['class_student.status', 1],
                ['fees_new.status', 1],
                ['student_fees_new.status', 'Debt']
            ])
            ->groupBy('students.id')
            ->get());

        $catC_complete = $catC_student - $catC_notcomplete;

        $catD_student = count(DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
            ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
            ->where([
                ['class_organization.organization_id', $oid],
                ['fees_new.category', 'Kategory D'],
                ['class_student.status', 1],
                ['fees_new.status', 1]
            ])
            ->whereIn('student_fees_new.status', ['Debt', 'Paid'])
            ->groupBy('students.id')
            ->get());

        $catD_notcomplete = count(DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
            ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
            ->where([
                ['class_organization.organization_id', $oid],
                ['fees_new.category', 'Kategory D'],
                ['class_student.status', 1],
                ['fees_new.status', 1],
                ['student_fees_new.status', 'Debt']
            ])
            ->groupBy('students.id')
            ->get());

        $catD_complete = $catD_student - $catD_notcomplete;

        $all_parent =  DB::table('organization_user')
            ->where('organization_id', $oid)
            ->where('role_id', 6)
            ->where('status', 1)
            ->count();

        $parent_complete =  DB::table('organization_user')
            ->where('organization_id', $oid)
            ->where('role_id', 6)
            ->where('status', 1)
            ->where('fees_status', 'Completed')
            ->count();

        $parent_notcomplete =  DB::table('organization_user')
            ->where('organization_id', $oid)
            ->where('role_id', 6)
            ->where('status', 1)
            ->where('fees_status', 'Not Complete')
            ->count();

        return response()->json(['all_student' => $all_student, 'student_complete' => $student_complete, 'student_notcomplete' => $student_notcomplete, 
        'all_parent' => $all_parent, 'parent_complete' => $parent_complete, 'parent_notcomplete' => $parent_notcomplete, 
        'catB_student' => $catB_student, 'catB_complete' => $catB_complete, 'catB_notcomplete' => $catB_notcomplete, 
        'catC_student' => $catC_student, 'catC_complete' => $catC_complete, 'catC_notcomplete' => $catC_notcomplete,
        'catD_student' => $catD_student, 'catD_complete' => $catD_complete, 'catD_notcomplete' => $catD_notcomplete], 200);

    }

    public function reportByClass($type, $class_id)
    {
        $class = DB::table('classes')
            ->where('id', $class_id)->first();

        return view('fee.reportbyclass', compact('type', 'class'));
    }

    public function getTypeDatatable(Request $request)
    {
        // dd($request->oid);
        if (request()->ajax()) {
            $type = $request->type;
            $oid = $request->oid;
            // dd($type);
            $userId = Auth::id();

            // get student that already complete pay all fees associated
            if ($type == 'Selesai') {
                
                $data = DB::table('students')
                    ->join('class_student', 'class_student.student_id', '=', 'students.id')
                    ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
                    ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                    ->select('class_organization.organization_id as oid', 'classes.id', 'classes.nama', DB::raw('COUNT(students.id) as totalstudent'), 'class_student.fees_status')
                    ->where('class_organization.organization_id', $oid)
                    ->where('class_student.fees_status', "Completed")
                    ->where('class_student.status', 1)
                    ->groupBy('classes.nama')
                    ->orderBy('classes.nama')
                    ->get();
                
            } 
            // get student that havent complete pay all fees
            else {
                $data = DB::table('students')
                    ->join('class_student', 'class_student.student_id', '=', 'students.id')
                    ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
                    ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                    ->select('class_organization.organization_id as oid', 'classes.id', 'classes.nama', DB::raw('COUNT(students.id) as totalstudent'), 'class_student.fees_status')
                    ->where('class_organization.organization_id', $oid)
                    ->where('class_student.fees_status', "Not Complete")
                    ->where('class_student.status', 1)
                    ->groupBy('classes.nama')
                    ->orderBy('classes.nama')
                    ->get();
                
            }

            // dd($first);
            $table = Datatables::of($data);

            $table->addColumn('total', function ($row) {

                $first = DB::table('students')
                ->join('class_student', 'class_student.student_id', '=', 'students.id')
                ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
                ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                ->select('classes.nama', DB::raw('COUNT(students.id) as totalallstudent'))
                ->where('class_organization.organization_id', $row->oid)
                ->where('class_student.status', 1)
                ->where('classes.id', $row->id)
                ->groupBy('classes.nama')
                ->orderBy('classes.nama')
                ->first();
            
                $btn = '<div class="d-flex justify-content-center">';
                $btn = $btn . $row->totalstudent . '/' . $first->totalallstudent . '</div>';
                return $btn;
            });

            $table->addColumn('action', function ($row) {
                $token = csrf_token();
                $btn = '<div class="d-flex justify-content-center">';
                $btn = $btn . '<a href="' . route('fees.reportByClass', ['type' => $row->fees_status, 'class_id' => $row->id]) . '"" class="btn btn-primary m-1">Butiran</a></div>';
                return $btn;
            });

            $table->rawColumns(['total', 'action']);
            return $table->make(true);
        }
    }

    public function getCategoryBCDDatatable(Request $request)
    {
        // dd($request->oid);
        if (request()->ajax()) {
            $type = $request->type;
            $oid = $request->oid;
            $cat = "Kategory ". $request->cat;
            // dd($type);
            $userId = Auth::id();

            // get student that completed pay fees 
            if ($type == 'Selesai') {
            
                $data = DB::table('students')
                    ->join('class_student', 'class_student.student_id', '=', 'students.id')
                    ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
                    ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                    ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                    ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
                    ->where([
                        ['class_organization.organization_id', $oid],
                        ['fees_new.category', $cat],
                        ['class_student.status', 1],
                        ['fees_new.status', 1]
                    ])
                    ->whereIn('student_fees_new.status', ["Debt", "Paid"])
                    ->select('students.id as id', 'students.nama as nama', 'classes.nama as class', 'class_organization.organization_id as organization_id', 'student_fees_new.status')
                    ->groupBy('students.id')
                    ->orderBy('classes.nama')
                    ->get();
                
                $debt = DB::table('students')
                ->join('class_student', 'class_student.student_id', '=', 'students.id')
                ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
                ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
                ->where([
                    ['class_organization.organization_id', $oid],
                    ['fees_new.category', $cat],
                    ['student_fees_new.status', "Debt"],
                    ['class_student.status', 1],
                    ['fees_new.status', 1]
                ])
                ->select('students.id as id')
                ->groupBy('students.id')
                ->orderBy('classes.nama')
                ->get();

                // remove student that still have debt for the selected category
                foreach ($data as $key => $value) {
                    foreach($debt as $isdebt)
                    {
                        if ($value->id == $isdebt->id) {
                            $data->forget($key);
                        }
                    }
                }
                // dd($data);
                
            } else {
                $data = DB::table('students')
                ->join('class_student', 'class_student.student_id', '=', 'students.id')
                ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
                ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
                ->where([
                    ['class_organization.organization_id', $oid],
                    ['fees_new.category', $cat],
                    ['student_fees_new.status', "Debt"],
                    ['class_student.status', 1],
                    ['fees_new.status', 1]
                ])
                ->select('students.id as id', 'students.nama as nama', 'classes.nama as class', 'class_organization.organization_id as organization_id')
                ->groupBy('students.id')
                ->orderBy('classes.nama')
                ->get();
            }
            
            $table = Datatables::of($data);

            $table->addColumn('action', function ($row) {
                $token = csrf_token();
                $btn = '<div class="d-flex justify-content-center">';
                $btn = $btn . '<a class="btn btn-primary m-1 student-id" id="' . $row->id . '">Butiran</a></div>';
                return $btn;
            });

            $table->rawColumns(['action']);
            return $table->make(true);
        }
    }

    public function getParentDatatable(Request $request)
    {
        // dd($request->oid);

        if (request()->ajax()) {
            $type = $request->type;
            $oid = $request->oid;
            // dd($type);
            $userId = Auth::id();

            if ($type == 'Selesai') {

                $data = DB::table('users')
                    ->join('organization_user', 'organization_user.user_id', '=', 'users.id')
                    ->select('users.*', 'organization_user.organization_id')
                    ->where('organization_user.organization_id', $oid)
                    ->where('organization_user.role_id', 6)
                    ->where('organization_user.status', 1)
                    ->where('organization_user.fees_status', 'Completed')
                    ->get();
            } else {
                $data = DB::table('users')
                    ->join('organization_user', 'organization_user.user_id', '=', 'users.id')
                    ->select('users.*', 'organization_user.organization_id')
                    ->where('organization_user.organization_id', $oid)
                    ->where('organization_user.role_id', 6)
                    ->where('organization_user.status', 1)
                    ->where('organization_user.fees_status', 'Not Complete')
                    ->get();
            }

            // dd($first);
            $table = Datatables::of($data);

            $table->addColumn('action', function ($row) {
                $token = csrf_token();
                $btn = '<div class="d-flex justify-content-center">';
                $btn = $btn . '<a class="btn btn-primary m-1 user-id" id="' . $row->id . '-' . $row->organization_id . '">Butiran</a></div>';
                return $btn;
            });

            $table->rawColumns(['action']);
            return $table->make(true);
        }
    }

    public function getstudentDatatable(Request $request)
    {
        // dd($request->oid);

        if (request()->ajax()) {
            $status = $request->status;
            $class_id = $request->class_id;
            // dd($type);
            $userId = Auth::id();

            $data = DB::table('students')
                ->join('class_student', 'class_student.student_id', '=', 'students.id')
                ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
                ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                ->select('students.*')
                ->where('classes.id', $class_id)
                ->where('class_student.fees_status', $status)
                ->where('class_student.status', 1)
                ->orderBy('students.nama')
                ->get();

            // dd($first);
            $table = Datatables::of($data);

            $table->addColumn('action', function ($row) {
                $token = csrf_token();
                $btn = '<div class="d-flex justify-content-center">';
                $btn = $btn . '<a class="btn btn-primary m-1 student-id" id="' . $row->id . '">Butiran</a></div>';
                return $btn;
            });

            $table->rawColumns(['action']);
            return $table->make(true);
        }
    }

    public function CategoryA()
    {
        $organization = $this->getOrganizationByUserId();

        return view('fee.category_A.index', compact('organization'));
    }

    public function createCategoryA()
    {
        $organization = $this->getOrganizationByUserId();

        return view('fee.category_A.add', compact('organization'));
    }

    public function renew(Request $request)
    {
        $result = 0;
        $oid = $request->organUpdate;
        $catname = $request->catUpdate;
        $feeid = $request->yuranUpdate;
        $currentDate = date("Y-m-d");

        if($feeid == "ALL"){
            $fees = DB::table('fees_new')
            ->where('fees_new.organization_id', $oid)
            ->where('fees_new.category', $catname)
            ->where('fees_new.status', 0)
            ->get();

            foreach($fees as $fees){
                if($currentDate > $fees->end_date){
                    if($fees->category == "Kategory D"){
                        $target = json_decode($fees->target)->repeat;
                        $target *= 30;
                        $target = (int)round($target);
    
                        $remainStart = date('Y-m-d', strtotime($fees->start_date. ' + '. $target.' days'));
                        $remainEnd = date('Y-m-d', strtotime($fees->end_date. ' + '. $target.' days'));
                    }
                    else{
                        $remainStart = date("Y") . '-' . date('m-d', strtotime($fees->start_date));
                        $remainEnd = date("Y") . '-' . date('m-d', strtotime($fees->end_date));
                    }
    
                    $result = DB::table('fees_new')
                        ->where('id', $fees->id)
                        ->where('organization_id', $oid)
                        ->update([
                            'start_date' => $remainStart,
                            'end_date'   => $remainEnd,
                            'status'     => 1
                    ]);

                    if($fees->category == "Kategory A"){
                        $updateResult = $this->updateParentDebt($oid, $feeid, $result);
                    }
                    else{
                        $updateResult = $this->updateStudentDebt($oid, $feeid, $result);
                    }
    
                    if($updateResult){
                        if($fees->category == "Kategory A"){
                            $this->sendEmail($fees->id, "A");
                        }
                        else{
                            $this->sendEmail($fees->id, "B");
                        }
                        
                        return redirect('/fees/category/report')->with('success', 'Yuran telah berjaya dikemaskini');
                    }
                }
            }
        }
        else{
            $fees = DB::table('fees_new')
            ->where('fees_new.id', $feeid)
            ->where('fees_new.organization_id', $oid)
            ->first();

            if($currentDate > $fees->end_date){
                if($fees->category == "Kategory D"){
                    $target = json_decode($fees->target)->repeat;
                    $target *= 30;
                    $target = (int)round($target);

                    $remainStart = date('Y-m-d', strtotime($fees->start_date. ' + '. $target.' days'));
                    $remainEnd = date('Y-m-d', strtotime($fees->end_date. ' + '. $target.' days'));
                }
                else{
                    $remainStart = date("Y") . '-' . date('m-d', strtotime($fees->start_date));
                    $remainEnd = date("Y") . '-' . date('m-d', strtotime($fees->end_date));
                }

                $result = DB::table('fees_new')
                    ->where('id', $feeid)
                    ->where('organization_id', $oid)
                    ->update([
                        'start_date' => $remainStart,
                        'end_date'   => $remainEnd,
                        'status'     => 1
                ]);

                if($fees->category == "Kategory A"){
                    $updateResult = $this->updateParentDebt($oid, $feeid, $result);
                }
                else{
                    $updateResult = $this->updateStudentDebt($oid, $feeid, $result);
                }

                if($updateResult){
                    return redirect('/fees/category/report')->with('success', 'Yuran telah berjaya dikemaskini');
                }
            }
        }  
        return redirect('/fees/category/report');
    }

    public function updateParentDebt($oid, $feeid, $result){
        $parent_id = DB::table('organization_user')
                ->where('organization_id', $oid)
                ->where('fees_status', 'Completed')
                ->where('role_id', 6)
                ->where('status', 1)
                ->get();

        // to make sure one parent would recieve one only katagory fee if he or she hv more than children in school
        for ($i = 0; $i < count($parent_id); $i++) {
            $fees_parent = DB::table('organization_user')
                ->where('id', $parent_id[$i]->id)
                ->update(['fees_status' => 'Not Complete']);

            $parent_debt = DB::table('fees_new_organization_user')
                ->where('organization_user_id', $parent_id[$i]->id)
                ->where('fees_new_id', $feeid)
                ->update(['status' => 'Debt']);

            // DB::table('fees_new_organization_user')->insert([
            //     'status' => 'Debt',
            //     'fees_new_id' => $feeid,
            //     'organization_user_id' => $parent_id[$i]->id,
            // ]);
        }

        return TRUE;
    }

    public function updateStudentDebt($oid, $feeid, $result){

        $list = DB::table('fees_new')
                ->join('student_fees_new as sfn', 'sfn.fees_id', '=', 'fees_new.id')
                ->join('class_student as cs', 'cs.id', '=', 'sfn.class_student_id')
                ->select('cs.id as class_student_id')
                ->where('fees_new.organization_id', $oid)
                ->where('fees_new.id', $feeid)
                ->where('sfn.status', 'Paid')
                ->where('cs.status', "1")
                ->get();

        for ($i = 0; $i < count($list); $i++) {
            $fees_student = DB::table('class_student')
                ->where('id', $list[$i]->class_student_id)
                ->update(['fees_status' => 'Not Complete']);
            
            $student_debt = DB::table('student_fees_new')
                ->where('fees_id', $feeid)
                ->where('class_student_id', $list[$i]->class_student_id)
                ->update(['status' =>  'Debt']);
        }

        
        return TRUE;
    }

    public function editCategory($id)
    {
        if(Auth::user()->hasRole('Superadmin')){
            $selectedFee = DB::table('fees_new')
            ->join('organizations', 'organizations.id', '=', 'fees_new.organization_id')
            ->where('fees_new.id', $id)
            ->select('fees_new.*', 'organizations.nama as orgName')
            ->first();    
        }
        else{
            $selectedFee = DB::table('fees_new')
            ->join('organization_user as ou', 'ou.organization_id', '=', 'fees_new.organization_id')
            ->join('organizations', 'organizations.id', '=', 'ou.organization_id')
            ->where('fees_new.id', $id)
            ->where('ou.user_id', Auth::user()->id)
            ->select('fees_new.*', 'organizations.nama as orgName')
            ->first();    
        }

        if(isset($selectedFee))
        {
            $data = json_decode($selectedFee->target)->data;

            if(is_array($data)){
                foreach($data as $r){
                    $data = DB::table('classes')
                    ->where('classes.id', $r)
                    ->value('classes.levelid');
                }
            }
            
            if($selectedFee->category == "Kategory C"){
                $gender = json_decode($selectedFee->target)->gender;
            }

            if($selectedFee->category == "Kategory D"){
                $repeat = json_decode($selectedFee->target)->repeat;
            }
            
            $permit = DB::table('organization_user')
            ->where('organization_user.user_id', Auth::user()->id)
            ->select('organization_user.organization_id', 'organization_user.role_id')
            ->get();

            if(count($permit) > 0 && $selectedFee != NULL)
            {   
                foreach($permit as $permit){
                    if($permit->role_id == 1 || ($permit->role_id != 6 && $permit->organization_id == $selectedFee->organization_id))
                    {
                        if($selectedFee->category == "Kategory A")
                            return view('fee.category_A.update', compact('selectedFee', 'data'));
                        elseif($selectedFee->category == "Kategory B")
                            return view('fee.category_B.update', compact('selectedFee', 'data'));
                        elseif($selectedFee->category == "Kategory C")
                            return view('fee.category_C.update', compact('selectedFee', 'data', 'gender'));
                        elseif($selectedFee->category == "Kategory D")
                            return view('fee.category_D.update', compact('selectedFee', 'data', 'repeat'));
                    }
                }
            }   
        }
        return view('errors.404');
    }

    public function StoreCategory(Request $request)
    {
        if($request->category == "A"){
            $this->validate($request, [
                'price'      =>  'required',
                'quantity'      =>  'integer',
            ]);
            
            $price          = $request->get('price');
            $quantity       = $request->get('quantity');
            $oid            = $request->get('organization');
            $date_started   = Carbon::createFromFormat(config('app.date_format'), $request->get('date_started'))->format('Y-m-d');
            $date_end       = Carbon::createFromFormat(config('app.date_format'), $request->get('date_end'))->format('Y-m-d');
            $total          = $price * $quantity;

            $target = ['data' => 'ALL'];

            $fee = new Fee_New([
                'name'              =>  $request->get('name'),
                'desc'              =>  $request->get('description'),
                'category'          =>  "Kategory A",
                'quantity'          =>  $request->get('quantity'),
                'price'             =>  $request->get('price'),
                'totalAmount'       =>  $total,
                'start_date'        =>  $date_started,
                'end_date'          =>  $date_end,
                'status'            =>  "1",
                'target'            =>  $target,
                'organization_id'   =>  $oid,
            ]);

            if ($fee->save()) {
                $parent_id = DB::table('organization_user')
                    ->where('organization_id', $oid)
                    ->where('role_id', 6)
                    ->where('status', 1)
                    ->get();

                // to make sure one parent would recieve one only katagory fee if he or she hv more than children in school
                for ($i = 0; $i < count($parent_id); $i++) {
                    $fees_parent = DB::table('organization_user')
                        ->where('id', $parent_id[$i]->id)
                        ->update(['fees_status' => 'Not Complete']);

                    DB::table('fees_new_organization_user')->insert([
                        'status' => 'Debt',
                        'fees_new_id' => $fee->id,
                        'organization_user_id' => $parent_id[$i]->id,
                    ]);
                }

                $this->sendEmail($fee->id, "A");
                return redirect('/fees/A')->with('success', 'Yuran Kategori A telah berjaya dimasukkan');
            }
        }
        elseif($request->category == "B"){
            $this->validate($request, [
                'price'      =>  'required',
                'quantity'      =>  'integer',
            ]);

            $id             = NULL;
            $gender         = "";
            $class          = $request->get('cb_class');
            $level          = $request->get('level');
            $year           = $request->get('year');
            $name           = $request->get('name');
            $price          = $request->get('price');
            $quantity       = $request->get('quantity');
            $desc           = $request->get('description');
            $oid            = $request->get('organization');
            $date_started   = Carbon::createFromFormat(config('app.date_format'), $request->get('date_started'))->format('Y-m-d');
            $date_end       = Carbon::createFromFormat(config('app.date_format'), $request->get('date_end'))->format('Y-m-d');
            $total          = $price * $quantity;
            $category       = "Kategory B";

            if ($level == "All_Level") {
                return $this->allLevel($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $gender, $category);
            } elseif ($year == "All_Year") {
                return $this->allYear($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $gender, $category);
            } else {
                return $this->allClasses($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $class, $gender, $category);
            }
        }
        elseif($request->category == "C"){
            $this->validate($request, [
                'price'      =>  'required',
                'quantity'   =>  'integer',
            ]);

            $id         = NULL;
            $gender     = $request->get('gender');
            $class      = $request->get('cb_class');
            $level      = $request->get('level');
            $year       = $request->get('year');
            $name       = $request->get('name');
            $price          = $request->get('price');
            $quantity       = $request->get('quantity');
            $desc           = $request->get('description');
            $oid            = $request->get('organization');
            $date_started   = Carbon::createFromFormat(config('app.date_format'), $request->get('date_started'))->format('Y-m-d');
            $date_end       = Carbon::createFromFormat(config('app.date_format'), $request->get('date_end'))->format('Y-m-d');
            $total          = $price * $quantity;
            $category       = "Kategory C";

            if ($level == "All_Level") {
                return $this->allLevel($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $gender, $category);
            } elseif ($year == "All_Year") {
                return $this->allYear($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $gender, $category);
            } else {
                return $this->allClasses($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $class, $gender, $category);
            }
        }
        elseif($request->category == "D"){
            $this->validate($request, [
                'grade'         =>  'required',
            ]);

            $id             = NULL;
            $name           = $request->get('name');
            $desc           = $request->get('desc');
            $price          = $request->get('price');
            $quantity       = 1;
            $oid            = $request->get('organization');
            $date_started   = Carbon::createFromFormat(config('app.date_format'), $request->get('date_started'))->format('Y-m-d');
            $date_end       = Carbon::createFromFormat(config('app.date_format'), $request->get('date_end'))->format('Y-m-d');
            $total          = $price * $quantity;
            $repeat         = $request->get('renew');
            $dorm           = $request->get('cb_dorm');
            $grade          = $request->get('grade');
            $category       = "Kategory D";

            if ($grade == "ALL_TYPE") {
                return $this->allType($id, $name, $desc, $quantity, $price, $total, $repeat, $date_started, $date_end, $oid, $grade, $category);
            }
            else {
                return $this->allDorm($id, $name, $desc, $quantity, $price, $total, $repeat, $date_started, $date_end, $dorm, $oid, $grade, $category);
            }
        }
        else{
            
        }
    }

    public function updateCategory(Request $request)
    {
        if($request->categoryFee == "A"){
            $price          = $request->get('price');
            $quantity       = $request->get('quantity');
            $oid            = $request->get('organization');
            $total          = $price * $quantity;
            $date_started   = $request->get('date_started');
            $date_end       = $request->get('date_end');
            
            $target = ['data' => 'ALL'];

            $fee = [
                'name'              =>  $request->get('name'),
                'desc'              =>  $request->get('description'),
                'category'          =>  "Kategory A",
                'quantity'          =>  $request->get('quantity'),
                'price'             =>  $request->get('price'),
                'totalAmount'       =>  $total,
                'start_date'        =>  $date_started,
                'end_date'          =>  $date_end,
                'status'            =>  "1",
                'target'            =>  $target,
                'organization_id'   =>  $oid,
            ];

            $result = DB::table('fees_new')
            ->where('fees_new.id', $request->get('id'))
            ->update($fee);

            if ($result) {
                return redirect('/fees/A')->with('success', 'Yuran Kategori A telah berjaya dikemaskinikan');
            }
            else{
                return redirect('/fees/A');
            }
        }
        elseif($request->categoryFee == "B"){
            $id             = $request->get('id');
            $gender         = "";
            $class          = $request->get('cb_class');
            $level          = $request->get('level');
            $year           = $request->get('year');
            $name           = $request->get('name');
            $price          = $request->get('price');
            $quantity       = $request->get('quantity');
            $desc           = $request->get('description');
            $oid            = $request->get('organization');
            $date_started   = $request->get('date_started');
            $date_end       = $request->get('date_end');
            $total          = $price * $quantity;
            $category       = "Kategory B";

            if ($level == "All_Level") {
                return $this->allLevel($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $gender, $category);
            } elseif ($year == "All_Year") {
                return $this->allYear($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $gender, $category);
            } else {
                return $this->allClasses($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $class, $gender, $category);
            }
        }
        elseif($request->categoryFee == "C"){
            $id         = $request->get('id');
            $gender     = $request->get('gender');
            $class      = $request->get('cb_class');
            $level      = $request->get('level');
            $year       = $request->get('year');
            $name       = $request->get('name');
            $price          = $request->get('price');
            $quantity       = $request->get('quantity');
            $desc           = $request->get('description');
            $oid            = $request->get('organization');
            $date_started   = $request->get('date_started');
            $date_end       = $request->get('date_end');
            $total          = $price * $quantity;
            $category       = "Kategory C";

            if ($level == "All_Level") {
                return $this->allLevel($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $gender, $category);
            } elseif ($year == "All_Year") {
                return $this->allYear($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $gender, $category);
            } else {
                return $this->allClasses($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $class, $gender, $category);
            }
        }
        elseif($request->categoryFee == "D"){
            $id             = $request->get('id');
            $name           = $request->get('name');
            $desc           = $request->get('desc');
            $price          = $request->get('price');
            $quantity       = 1;
            $oid            = $request->get('organization');
            $date_started   = $request->get('date_started');
            $date_end       = $request->get('date_end');
            $total          = $price * $quantity;
            $repeat         = $request->get('renew');
            $category       = "Kategory D";
    
            $getTarget = DB::table('fees_new')
                ->where('fees_new.id', $id)
                ->select('fees_new.target')
                ->get();
    
            foreach($getTarget as $list){
                $target = json_decode($list->target);
            }
    
            $grade  = $target->data;
    
            if ($grade == "ALL_TYPE") 
            {
                return $this->allType($id, $name, $desc, $quantity, $price, $total, $repeat, $date_started, $date_end, $oid, $grade, $category);
            }
            else 
            {
                $dorm = $target->dorm;
                return $this->allDorm($id, $name, $desc, $quantity, $price, $total, $repeat, $date_started, $date_end, $dorm, $oid, $grade, $category);
            }
        }
    }

    public function getCategoryDatatable(Request $request)
    {
        if (request()->ajax()) {
            $oid = $request->oid;
            $category = $request->category;
            $userId = Auth::id();

            if ($oid != '') {

                $update = DB::table('fees_new')
                ->where('organization_id', $oid)
                ->where('end_date', '<', date("Y-m-d"))
                ->update([
                    'status' => 0
                ]);

                // $data = DB::table('fees')->orderBy('nama')->get();

                if ($category == "A") {
                    $data     = DB::table('fees_new')
                        ->where('organization_id', $oid)
                        ->where('category', "Kategory A")
                        ->where('status', "1")
                        ->get();
                    
                    foreach($data as $d)
                    {
                        $d->target = "Setiap Keluarga";
                    }

                } elseif ($category == "B") {
                    $data     = DB::table('fees_new')
                        ->where('organization_id', $oid)
                        ->where('category', "Kategory B")
                        ->where('status', "1")
                        ->get();
                    
                    foreach($data as $d)
                    {
                        $level = json_decode($d->target);
                        if($level->data == "All_Level")
                        {
                            $d->target = "Semua Tahap";
                        }
                        elseif($level->data  == 1)
                        {
                            $d->target = "Kelas : Tahap 1";
                        }
                        elseif($level->data  == 2)
                        {
                            $d->target = "Kelas : Tahap 2";
                        }
                        elseif(is_array($level->data))
                        {
                            $classes = DB::table('classes')
                                        ->whereIN('id', $level->data)
                                        ->get();
                            
                            $d->target = "Kelas : ";
                            foreach($classes as $i=>$class)
                            {
                                $d->target = $d->target .  $class->nama  . (sizeof($classes) - 1 == $i ? "" : ", ");
                            }
                        }
                    }

                } elseif($category == "C") {
                    $data     = DB::table('fees_new')
                        ->where('organization_id', $oid)
                        ->where('category', "Kategory C")
                        ->where('status', "1")
                        ->get();
                    
                    foreach($data as $d)
                    {
                        $level = json_decode($d->target);
                        $d->target = "Jantina : " . ($level->gender == 'L' ? "Lelaki<br>" : "Perempuan<br>");
                        if($level->data == "All_Level")
                        {
                            $d->target = $d->target . "Kelas : Semua Tahap";
                        }
                        elseif($level->data  == 1)
                        {
                            $d->target = $d->target . "Kelas : Tahap 1";
                        }
                        elseif($level->data  == 2)
                        {
                            $d->target = $d->target . "Kelas : Tahap 2";
                        }
                        elseif(is_array($level->data))
                        {
                            $classes = DB::table('classes')
                                        ->whereIN('id', $level->data)
                                        ->get();
                            
                            $d->target = $d->target . $d->target = "Kelas : ";
                            foreach($classes as $i=>$class)
                            {
                                $d->target = $d->target .  $class->nama  . (sizeof($classes) - 1 == $i ? "" : ", ");
                            }
                        }
                    }
                }elseif ($category == "D") {
                    $data = DB::table('fees_new')
                        ->where('organization_id', $oid)
                        ->where('category', "Kategory D")
                        ->where('status', "1")
                        ->get();
                    
                    foreach($data as $d)
                    {
                        $level = json_decode($d->target);
                        if($level->data == "ALL_TYPE")
                        {  
                            $d->target = "Semua Jenis";
                            if($level->repeat > 0)
                                $d->target = "Semua Jenis<br>Ulang selama " . $level->repeat . " bulan";
                        }
                        elseif(is_array($level->dorm))
                        {
                            $dorms = DB::table('dorms')
                                        ->whereIN('id', $level->dorm)
                                        ->get();

                            if($level->data == "1"){
                                $d->target = "Jenis : Bilik Peribadi<br>";
                            }
                            elseif($level->data == "2"){
                                $d->target = "Jenis : Bilik Kongsi<br>";
                            }
                            

                            $d->target = $d->target . "Asrama : ";
                            foreach($dorms as $i=>$dorm)
                            {
                                $d->target = $d->target .  $dorm->name  . (sizeof($dorms) - 1 == $i ? "" : ", ");
                            }
                            if($level->repeat > 0)
                                $d->target = $d->target . "<br>Ulang selama " . $level->repeat . " bulan";
                        }
                    }
                } 
            }

            $table = Datatables::of($data);

            $table->addColumn('status', function ($row) {
                if ($row->status == '1') {
                    $btn = '<div class="d-flex justify-content-center">';
                    $btn = $btn . '<span class="badge badge-success">Aktif</span></div>';
                    return $btn;
                } else {
                    $btn = '<div class="d-flex justify-content-center">';
                    $btn = $btn . '<span class="badge badge-danger"> Tidak Aktif </span></div>';
                    return $btn;
                }
            });

            $table->addColumn('action', function($row){
                $token = csrf_token();
                $btn = '<div class="d-flex justify-content-center">';
                $btn = $btn . '<a href="' . route('fees.editCategory', $row->id) . '" class="btn btn-primary m-1">Edit</a>';
                $btn = $btn . '<button id="' . $row->id . '" data-token="' . $token . '" class="btn btn-danger m-1">Buang</button></div>';
                return $btn;
            });

            /* $table->addColumn('action', function ($row) {
                $token = csrf_token();
                $btn = '<div class="d-flex justify-content-center">';
                // $btn = $btn . '<a href="' . route('fees.edit', $row->id) . '" class="btn btn-primary m-1">Edit</a>';
                $btn = $btn . '<button id="' . $row->id . '" data-token="' . $token . '" class="btn btn-danger m-1">Buang</button></div>';
                return $btn;
            }); */

            // $table->rawColumns(['status', 'action']);
            $table->rawColumns(['target', 'status', 'action']);
            return $table->make(true);
        }
    }

    public function CategoryB()
    {
        $organization = $this->getOrganizationByUserId();

        return view('fee.category_B.index', compact('organization'));
    }

    public function createCategoryB()
    {
        $organization = $this->getOrganizationByUserId();

        return view('fee.category_B.add', compact('organization'));
    }

    public function CategoryC()
    {
        $organization = $this->getOrganizationByUserId();

        return view('fee.category_C.index', compact('organization'));
    }

    public function createCategoryC()
    {
        $organization = $this->getOrganizationByUserId();

        return view('fee.category_C.add', compact('organization'));
    }

    public function CategoryD()
    {
        $organization = $this->getOrganizationByUserId();

        return view('fee.category_D.index', compact('organization'));
    }

    public function createCategoryD()
    {
        $organization = $this->getOrganizationByUserId();

        return view('fee.category_D.add', compact('organization'));
    }
    
    public function fetchClassYear(Request $request)
    {

        // dd($request->get('level'));
        $level = $request->get('level');
        $oid = $request->get('oid');
        if ($level == "1") {
            $list = DB::table('organizations')
                ->select('organizations.id as oid', 'organizations.nama as organizationname', 'organizations.type_org')
                ->where('organizations.id', $oid)
                ->first();

            $class_organization = DB::table('classes')
                ->join('class_organization', 'class_organization.class_id', '=', 'classes.id')
                ->select(DB::raw('substr(classes.nama, 1, 1) as year'))
                ->distinct()
                ->where('classes.status', 1)
                ->where('classes.levelid', $level)
                ->where('class_organization.organization_id', $oid)
                ->get();

            // dd($class_organization);

            return response()->json(['data' => $list, 'datayear' => $class_organization]);
        } elseif ($level == "2") {
            $list = DB::table('organizations')
                ->select('organizations.id as oid', 'organizations.nama as organizationname', 'organizations.type_org')
                ->where('organizations.id', $oid)
                ->first();

            $class_organization = DB::table('classes')
                ->join('class_organization', 'class_organization.class_id', '=', 'classes.id')
                ->select(DB::raw('substr(classes.nama, 1, 1) as year'))
                ->distinct()
                ->where('classes.status', 1)
                ->where('classes.levelid', $level)
                ->where('class_organization.organization_id', $oid)
                ->get();

            // dd($class_organization);

            return response()->json(['data' => $list, 'datayear' => $class_organization]);
        }
    }

    public function allLevel($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $gender, $category)
    {
        if ($gender) {
            $list = DB::table('class_organization')
                ->join('class_student', 'class_student.organclass_id', '=', 'class_organization.id')
                ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                ->join('students', 'students.id', '=', 'class_student.student_id')
                ->select('class_student.id as class_student_id')
                ->where('class_organization.organization_id', $oid)
                ->where('classes.status', "1")
                ->where('students.gender', $gender)
                ->get();

            $data = array(
                'data' => $level,
                'gender' => $gender
            );
        } else {
            $list = DB::table('class_organization')
                ->join('class_student', 'class_student.organclass_id', '=', 'class_organization.id')
                ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                ->select('class_student.id as class_student_id')
                ->where('class_organization.organization_id', $oid)
                ->where('classes.status', "1")
                ->get();

            $data = array(
                'data' => $level
            );
        }

        $target = json_encode($data);

        if($id == NULL){
            $fees = DB::table('fees_new')->insertGetId([
                'name'          => $name,
                'desc'          => $desc,
                'category'      => $category,
                'quantity'      => $quantity,
                'price'         => $price,
                'totalAmount'       => $total,
                'start_date'        => $date_started,
                'end_date'          => $date_end,
                'status'            => "1",
                'target'            => $target,
                'organization_id'   => $oid,

            ]);

            for ($i = 0; $i < count($list); $i++) {

                $fees_student = DB::table('class_student')
                    ->where('id', $list[$i]->class_student_id)
                    ->update(['fees_status' => 'Not Complete']);
                
                DB::table('student_fees_new')->insert([
                    'status' => 'Debt',
                    'fees_id' => $fees,
                    'class_student_id' => $list[$i]->class_student_id,
                ]);
            }

            if ($category == "Kategory B") {
                $this->sendEmail($fees, "B");
                return redirect('/fees/B')->with('success', 'Yuran Kategori B telah berjaya dimasukkan');
            } else {
                $this->sendEmail($fees, "C");
                return redirect('/fees/C')->with('success', 'Yuran Kategori C telah berjaya dimasukkan');
            }
        }
        else{
            $fees = [
                'name'          => $name,
                'desc'          => $desc,
                'category'      => $category,
                'start_date'        => $date_started,
                'end_date'          => $date_end,
            ];
            // dd($name, $desc, $category, $date_started, $date_end);
            
            $result = DB::table('fees_new')
            ->where('fees_new.id', $id)
            ->update($fees);

            if($result){
                if ($category == "Kategory B") {
                    $this->sendEmail($id, "B");
                    return redirect('/fees/B')->with('success', 'Yuran Kategori B telah berjaya dikemaskini');
                } else {
                    $this->sendEmail($id, "C");
                    return redirect('/fees/C')->with('success', 'Yuran Kategori C telah berjaya dikemaskini');
                }
            }
            else{
                if ($category == "Kategory B") {
                    return redirect('/fees/B');
                } else {
                    return redirect('/fees/C');
                }
            }
        }
    }

    public function allYear($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $gender, $category)
    {
        if ($gender) {
            $list = DB::table('class_organization')
                ->join('class_student', 'class_student.organclass_id', '=', 'class_organization.id')
                ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                ->join('students', 'students.id', '=', 'class_student.student_id')
                ->select('class_student.id as class_student_id')
                ->where('class_organization.organization_id', $oid)
                ->where('classes.levelid', $level)
                ->where('classes.status', "1")
                ->where('students.gender', $gender)
                ->get();
            $data = array(
                'data' => $level,
                'gender' => $gender
            );
        } else {
            $list = DB::table('class_organization')
                ->join('class_student', 'class_student.organclass_id', '=', 'class_organization.id')
                ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                ->select('class_student.id as class_student_id')
                ->where('class_organization.organization_id', $oid)
                ->where('classes.levelid', $level)
                ->where('classes.status', "1")
                ->get();
            $data = array(
                'data' => $level
            );
        }

        $target = json_encode($data);

        if($id == NULL){
            $fees = DB::table('fees_new')->insertGetId([
                'name'          => $name,
                'desc'          => $desc,
                'category'      => $category,
                'quantity'      => $quantity,
                'price'         => $price,
                'totalAmount'       => $total,
                'start_date'        => $date_started,
                'end_date'          => $date_end,
                'status'            => "1",
                'target'            => $target,
                'organization_id'   => $oid,
    
            ]);
    
            for ($i = 0; $i < count($list); $i++) {
    
                $fees_student = DB::table('class_student')
                    ->where('id', $list[$i]->class_student_id)
                    ->update(['fees_status' => 'Not Complete']);
    
                DB::table('student_fees_new')->insert([
                    'status' => 'Debt',
                    'fees_id' => $fees,
                    'class_student_id' => $list[$i]->class_student_id,
                ]);
            }
            
            if ($category == "Kategory B") {
                $this->sendEmail($fees, "B");
                return redirect('/fees/B')->with('success', 'Yuran Kategori B telah berjaya dimasukkan');
            } else {
                $this->sendEmail($fees, "C");
                return redirect('/fees/C')->with('success', 'Yuran Kategori C telah berjaya dimasukkan');
            }
        }
        else{
            $fees = [
                'name'          => $name,
                'desc'          => $desc,
                'category'      => $category,
                // 'quantity'      => $quantity,
                // 'price'         => $price,
                // 'totalAmount'       => $total,
                'start_date'        => $date_started,
                'end_date'          => $date_end,
                // 'status'            => "1",
                // 'target'            => $target,
                // 'organization_id'   => $oid,

            ];

            $result = DB::table('fees_new')
            ->where('fees_new.id', $id)
            ->update($fees);

            if($result){
                if ($category == "Kategory B") {
                    $this->sendEmail($id, "B");
                    return redirect('/fees/B')->with('success', 'Yuran Kategori B telah berjaya dikemaskini');
                } else {
                    $this->sendEmail($id, "C");
                    return redirect('/fees/C')->with('success', 'Yuran Kategori C telah berjaya dikemaskini');
                }
            }
            else{
                if ($category == "Kategory B") {
                    return redirect('/fees/B');
                } else {
                    return redirect('/fees/C');
                }
            }
        }
    }

    public function allClasses($id, $name, $desc, $quantity, $price, $total, $date_started, $date_end, $level, $oid, $class, $gender, $category)
    {
        if($id == NULL)
        {
            // get list class checked from checkbox
            if($class){
                $list = DB::table('classes')
                ->where('status', "1")
                ->whereIn('id', $class)
                ->get();

                // dd(count($list));
                for ($i = 0; $i < count($list); $i++) {
                    $class_arr[] = $list[$i]->id;
                }
            }

            if ($gender) {
                $list_student = DB::table('class_organization')
                    ->join('class_student', 'class_student.organclass_id', '=', 'class_organization.id')
                    ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                    ->join('students', 'students.id', '=', 'class_student.student_id')
                    ->select('class_student.id as class_student_id')
                    ->where('class_organization.organization_id', $oid)
                    ->where('classes.status', "1")
                    ->where('students.gender', $gender)
                    ->whereIn('classes.id', $class)
                    ->get();
                $data = array(
                    'data' => $class_arr,
                    'gender' => $gender
                );
            } 
            elseif($class) {
                $list_student = DB::table('class_organization')
                    ->join('class_student', 'class_student.organclass_id', '=', 'class_organization.id')
                    ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                    ->select('class_student.id as class_student_id')
                    ->where('class_organization.organization_id', $oid)
                    ->where('classes.status', "1")
                    ->whereIn('classes.id', $class)
                    ->get();
                $data = array(
                    'data' => $class_arr
                );
            }else{
                $data = NULL;
            }
    
            $target = json_encode($data);

            $fees = DB::table('fees_new')->insertGetId([
                'name'              => $name,
                'desc'              => $desc,
                'category'          => $category,
                'quantity'          => $quantity,
                'price'             => $price,
                'totalAmount'       => $total,
                'start_date'        => $date_started,
                'end_date'          => $date_end,
                'status'            => "1",
                'target'            => $target,
                'organization_id'   => $oid,
            ]);
            
            for ($i = 0; $i < count($list_student); $i++) {
                $fees_student = DB::table('class_student')
                    ->where('id', $list_student[$i]->class_student_id)
                    ->update(['fees_status' => 'Not Complete']);
    
                DB::table('student_fees_new')->insert([
                    'status' => 'Debt',
                    'fees_id' => $fees,
                    'class_student_id' => $list_student[$i]->class_student_id,
                ]);
            }
            
            if ($category == "Kategory B") {
                $this->sendEmail($fees, "B");
                return redirect('/fees/B')->with('success', 'Yuran Kategori B telah berjaya dimasukkan');
            } else {
                $this->sendEmail($fees, "C");
                return redirect('/fees/C')->with('success', 'Yuran Kategori C telah berjaya dimasukkan');
            }
        }
        else{
            $fees = [
                'name'              => $name,
                'desc'              => $desc,
                'category'          => $category,
                // 'quantity'          => $quantity,
                // 'price'             => $price,
                // 'totalAmount'       => $total,
                'start_date'        => $date_started,
                'end_date'          => $date_end,
                // 'status'            => "1",
                // 'target'            => $target,
                // 'organization_id'   => $oid,
            ];

            $result = DB::table('fees_new')
            ->where('fees_new.id', $id)
            ->update($fees);

            if($result){
                if ($category == "Kategory B") {
                    $this->sendEmail($id, "B");
                    return redirect('/fees/B')->with('success', 'Yuran Kategori B telah berjaya dikemaskini');
                } else {
                    $this->sendEmail($id, "C");
                    return redirect('/fees/C')->with('success', 'Yuran Kategori C telah berjaya dikemaskini');
                }
            }
            else{
                if ($category == "Kategory B") {
                    return redirect('/fees/B');
                } else {
                    return redirect('/fees/C');
                }
            }
        }
    }

    public function allType($id, $name, $desc, $quantity, $price, $total, $repeat, $date_started, $date_end, $oid, $grade, $category)
    {
        $list = DB::table('class_organization')
            ->join('class_student', 'class_student.organclass_id', '=', 'class_organization.id')
            ->join('classes', 'classes.id', '=', 'class_organization.class_id')
            ->select('class_student.id as class_student_id')
            ->where('class_organization.organization_id', $oid)
            ->where('classes.status', "1")
            ->get();

        $data = array(
            'data'      => $grade,
            'repeat'    => $repeat,
        );

        // $target = ['data' => 'ALL_TYPE'];
        $target = json_encode($data);

        // dd($target);
        if($id == NULL){
            $fees = DB::table('fees_new')->insertGetId([
                'name'              =>  $name,
                'desc'              =>  $desc,
                'category'          =>  $category,
                'quantity'          =>  $quantity,
                'price'             =>  $price,
                'totalAmount'       =>  $total,
                'start_date'        =>  $date_started,
                'end_date'          =>  $date_end,
                'status'            =>  "1",
                'target'            =>  $target,
                'organization_id'   =>  $oid,
            ]);

            // dd($fees);

            for ($i = 0; $i < count($list); $i++) {

                $fees_student = DB::table('class_student')
                    ->where('id', $list[$i]->class_student_id)
                    ->update(['fees_status' => 'Not Complete']);
                
                DB::table('student_fees_new')->insert([
                    'status' => 'Debt',
                    'fees_id' => $fees,
                    'class_student_id' => $list[$i]->class_student_id,
                ]);
            }
            $this->sendEmail($fees, "D");

            return redirect('/fees/D')->with('success', 'Yuran Kategori D telah berjaya dimasukkan');
        }
        else{
            $fees = [
                'name'          => $name,
                'desc'          => $desc,
                'start_date'    => $date_started,
                'end_date'      => $date_end,
                'target'        => $target,
            ];
            
            $result = DB::table('fees_new')
            ->where('fees_new.id', $id)
            ->update($fees);

            if($result){
                $this->sendEmail($id, "D");
                return redirect('/fees/D')->with('success', 'Yuran Kategori D telah berjaya dikemaskini');
            }
            else{
                return redirect('/fees/D');
            }
        }
    }

    public function allDorm($id, $name, $desc, $quantity, $price, $total, $repeat, $date_started, $date_end, $dorm, $oid, $grade, $category)
    {
        if($dorm){
            $list = DB::table('dorms')
            ->whereIn('dorms.id', $dorm)
            ->get();

            // dd(count($list));
            for($i=0; $i < count($list); $i++){
                $dorm_arr[] = $list[$i]->id;
            }

            $data = array(
                'data'   => $grade,
                'dorm'   => $dorm_arr,
                'repeat' => $repeat,
            );
        }
        else
        {
            $data = NULL;
        }

        $target = json_encode($data);

        if($id == NULL)
        {
            $fees = DB::table('fees_new')->insertGetId([
                'name'              =>  $name,
                'desc'              =>  $desc,
                'category'          =>  $category,
                'quantity'          =>  $quantity,
                'price'             =>  $price,
                'totalAmount'       =>  $total,
                'start_date'        =>  $date_started,
                'end_date'          =>  $date_end,
                'status'            =>  "1",
                'target'            =>  $target,
                'organization_id'   =>  $oid,
            ]);

            // dd($fees);
    
            $list = DB::table('class_organization')
                ->join('class_student', 'class_student.organclass_id', '=', 'class_organization.id')
                ->join('classes', 'classes.id', '=', 'class_organization.class_id')
                ->where('class_organization.organization_id', $oid)
                ->where('classes.status', "1")
                ->whereNull('class_student.end_date_time')
                ->where('class_student.dorm_id', $dorm)
                ->whereNotNull('class_student.dorm_id')
                ->select('class_student.id as class_student_id')
                ->get();

            // dd(count($list));
    
            for ($i = 0; $i < count($list); $i++) {
                $fees_student = DB::table('class_student')
                    ->where('id', $list[$i]->class_student_id)
                    ->update(['fees_status' => 'Not Complete']);
                
                DB::table('student_fees_new')->insert([
                    'status' => 'Debt',
                    'fees_id' => $fees,
                    'class_student_id' => $list[$i]->class_student_id,
                ]);
            }

            $this->sendEmail($fees, "D");
    
            return redirect('/fees/D')->with('success', 'Yuran Kategori D telah berjaya dimasukkan');
        }
        else
        {   
            $fees = [
                'name'          => $name,
                'desc'          => $desc,
                'start_date'    => $date_started,
                'end_date'      => $date_end,
                'target'        => $target,
            ];

            $result = DB::table('fees_new')
            ->where('fees_new.id', $id)
            ->update($fees);

            if($result){
                $this->sendEmail($id, "D");
                return redirect('/fees/D')->with('success', 'Yuran Kategori D telah berjaya dikemaskini');
            }
            else{
                return redirect('/fees/D');
            }

        }
    }

    public function dependent_fees_org(){
        $organization = $this->getOrganizationByUserId();
        return view('fee.pay.index', compact('organization'));
    }

    public function dependent_fees()
    {
        // if(Auth::user()->hasRole('Superadmin')){
        //     // ************************* get list dependent from user id  *******************************

        //     $list = DB::table('organizations')
        //     ->join('organization_user', 'organization_user.organization_id', '=', 'organizations.id')
        //     ->join('users', 'users.id', '=', 'organization_user.user_id')
        //     ->join('organization_user_student', 'organization_user_student.organization_user_id', '=', 'organization_user.id')
        //     ->join('students', 'students.id', '=', 'organization_user_student.student_id')
        //     ->join('class_student', 'class_student.student_id', '=', 'students.id')
        //     ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
        //     ->join('classes', 'classes.id', '=', 'class_organization.class_id')
        //     ->select('organizations.id as oid', 'organizations.nama as nschool', 'students.id as studentid', 'students.nama as studentname', 'classes.nama as classname')
        //     // ->where('organization_user.user_id', $userid)
        //     ->where('organization_user.role_id', 6)
        //     ->where('organization_user.status', 1)
        //     ->where('class_student.status', 1)
        //     ->orderBy('organizations.id')
        //     ->orderBy('classes.nama')
        //     ->get();

        //     // ************************* get list organization by parent  *******************************

        //     $organization = DB::table('organizations')
        //         ->join('organization_user', 'organization_user.organization_id', '=', 'organizations.id')
        //         ->join('organization_user_student', 'organization_user_student.organization_user_id', '=', 'organization_user.id')
        //         ->join('students', 'students.id', '=', 'organization_user_student.student_id')
        //         ->select('organizations.*', 'organization_user.user_id')
        //         ->distinct()
        //         // ->where('organization_user.user_id', $userid)
        //         ->where('organization_user.role_id', 6)
        //         ->where('organization_user.status', 1)
        //         ->groupBy('organizations.id')
        //         ->orderBy('organizations.nama')
        //         ->get();


        //     // dd($organization);
        //     // ************************* get list fees  *******************************

        //     $getfees = DB::table('students')
        //         ->join('class_student', 'class_student.student_id', '=', 'students.id')
        //         ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
        //         ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
        //         ->select('fees_new.category', 'fees_new.organization_id', 'students.id as studentid')
        //         ->distinct()
        //         ->orderBy('students.id')
        //         ->orderBy('fees_new.category')
        //         ->where('fees_new.status', 1)
        //         ->where('class_student.status', 1)
        //         ->where('student_fees_new.status', 'Debt')
        //         ->get();

        //     $getfees_bystudent = DB::table('students')
        //         ->join('class_student', 'class_student.student_id', '=', 'students.id')
        //         ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
        //         ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
        //         ->select('fees_new.*', 'students.id as studentid')
        //         ->orderBy('fees_new.name')
        //         ->where('fees_new.status', 1)
        //         ->where('student_fees_new.status', 'Debt')
        //         ->where('class_student.status', 1)
        //         // ->where('fees_new.category', 'Kategory C')
        //         // ->where('fees_new.organization_id', 4)
        //         ->get();

        //     // dd($getfees_bystudent);

        //     // ************************* get fees category A  *******************************

        //     $getfees_category_A = DB::table('fees_new')
        //         ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
        //         ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
        //         ->select('fees_new.category', 'organization_user.organization_id')
        //         ->distinct()
        //         ->orderBy('fees_new.category')
        //         ->where('fees_new.status', 1)
        //         // ->where('organization_user.user_id', $userid)
        //         ->where('organization_user.role_id', 6)
        //         ->where('organization_user.status', 1)
        //         ->where('fees_new_organization_user.status', 'Debt')
        //         ->get();

        //     // dd($getfees_category_A);
        //     $getfees_category_A_byparent  = DB::table('fees_new')
        //         ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
        //         ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
        //         ->select('fees_new.*')
        //         ->orderBy('fees_new.category')
        //         ->where('fees_new.status', 1)
        //         // ->where('organization_user.user_id', $userid)
        //         ->where('organization_user.role_id', 6)
        //         ->where('organization_user.status', 1)
        //         ->where('fees_new_organization_user.status', 'Debt')
        //         ->get();
        // }
        // else{
            $userid = Auth::id();

            // ************************* get list dependent from user id  *******************************

            $list = DB::table('organizations')
            ->join('organization_user', 'organization_user.organization_id', '=', 'organizations.id')
            ->join('users', 'users.id', '=', 'organization_user.user_id')
            ->join('organization_user_student', 'organization_user_student.organization_user_id', '=', 'organization_user.id')
            ->join('students', 'students.id', '=', 'organization_user_student.student_id')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('classes', 'classes.id', '=', 'class_organization.class_id')
            ->select('organizations.id as oid', 'organizations.nama as nschool', 'students.id as studentid', 'students.nama as studentname', 'classes.nama as classname')
            ->where('organization_user.user_id', $userid)
            ->where('organization_user.role_id', 6)
            ->where('organization_user.status', 1)
            ->where('class_student.status', 1)
            ->orderBy('organizations.id')
            ->orderBy('classes.nama')
            ->get();

            // ************************* get list organization by parent  *******************************

            $organization = DB::table('organizations')
                ->join('organization_user', 'organization_user.organization_id', '=', 'organizations.id')
                ->join('organization_user_student', 'organization_user_student.organization_user_id', '=', 'organization_user.id')
                ->join('students', 'students.id', '=', 'organization_user_student.student_id')
                ->select('organizations.*', 'organization_user.user_id')
                ->distinct()
                ->where('organization_user.user_id', $userid)
                ->where('organization_user.role_id', 6)
                ->where('organization_user.status', 1)
                ->groupBy('organizations.id')
                ->orderBy('organizations.nama')
                ->get();


            // dd($organization);
            // ************************* get list fees  *******************************

            $getfees = DB::table('students')
                ->join('class_student', 'class_student.student_id', '=', 'students.id')
                ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
                ->select('fees_new.category', 'fees_new.organization_id', 'students.id as studentid')
                ->distinct()
                ->orderBy('students.id')
                ->orderBy('fees_new.category')
                ->where('fees_new.status', 1)
                ->where('class_student.status', 1)
                ->where('student_fees_new.status', 'Debt')
                ->get();

            $getfees_bystudent = DB::table('students')
                ->join('class_student', 'class_student.student_id', '=', 'students.id')
                ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
                ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
                ->select('fees_new.*', 'students.id as studentid')
                ->orderBy('fees_new.name')
                ->where('fees_new.status', 1)
                ->where('student_fees_new.status', 'Debt')
                ->where('class_student.status', 1)
                // ->where('fees_new.category', 'Kategory C')
                // ->where('fees_new.organization_id', 4)
                ->get();

            // dd($getfees_bystudent);

            // ************************* get fees category A  *******************************

            $getfees_category_A = DB::table('fees_new')
                ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
                ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
                ->select('fees_new.category', 'organization_user.organization_id')
                ->distinct()
                ->orderBy('fees_new.category')
                ->where('fees_new.status', 1)
                ->where('organization_user.user_id', $userid)
                ->where('organization_user.role_id', 6)
                ->where('organization_user.status', 1)
                ->where('fees_new_organization_user.status', 'Debt')
                ->get();

            // dd($getfees_category_A);
            $getfees_category_A_byparent  = DB::table('fees_new')
                ->join('fees_new_organization_user', 'fees_new_organization_user.fees_new_id', '=', 'fees_new.id')
                ->join('organization_user', 'organization_user.id', '=', 'fees_new_organization_user.organization_user_id')
                ->select('fees_new.*')
                ->orderBy('fees_new.category')
                ->where('fees_new.status', 1)
                ->where('organization_user.user_id', $userid)
                ->where('organization_user.role_id', 6)
                ->where('organization_user.status', 1)
                ->where('fees_new_organization_user.status', 'Debt')
                ->get();
        // }

        // dd($organization);
        return view('fee.pay.index', compact('list', 'organization', 'getfees', 'getfees_bystudent', 'getfees_category_A', 'getfees_category_A_byparent'));
    }

    public function student_fees(Request $request)
    {
        $student_id = $request->student_id;

        if(isset($request->cat)){
            $cat = 'Kategory ' . $request->cat;
            $getfees_bystudent = DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
            ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
            ->select('fees_new.*','students.id as studentid', 'students.nama as studentnama', 'student_fees_new.status')
            ->where('students.id', $student_id)
            ->where('class_student.status', 1)
            ->where('fees_new.status', 1)
            ->where('fees_new.category', $cat)
            ->orderBy('fees_new.name')
            ->get();
        }
        else{
            $getfees_bystudent     = DB::table('students')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('student_fees_new', 'student_fees_new.class_student_id', '=', 'class_student.id')
            ->join('fees_new', 'fees_new.id', '=', 'student_fees_new.fees_id')
            ->select('fees_new.*','students.id as studentid', 'students.nama as studentnama', 'student_fees_new.status')
            ->where('students.id', $student_id)
            ->where('class_student.status', 1)
            ->where('fees_new.status', 1)
            ->orderBy('fees_new.name')
            ->get();
        }

        return response()->json($getfees_bystudent, 200);
    }

    public function parent_dependent(Request $request)
    {
        $case = explode("-", $request->data);

        $user_id         = $case[0];
        $organization_id = $case[1];

        $get_dependents = DB::table('organizations')
            ->join('organization_user', 'organization_user.organization_id', '=', 'organizations.id')
            ->join('organization_user_student', 'organization_user_student.organization_user_id', '=', 'organization_user.id')
            ->join('users', 'users.id', 'organization_user.user_id')
            ->join('students', 'students.id', '=', 'organization_user_student.student_id')
            ->join('class_student', 'class_student.student_id', '=', 'students.id')
            ->join('class_organization', 'class_organization.id', '=', 'class_student.organclass_id')
            ->join('classes', 'classes.id', '=', 'class_organization.class_id')
            ->select('students.*', 'classes.nama as classname', 'users.name as username')
            ->where('organization_user.user_id', $user_id)
            ->where('organization_user.role_id', 6)
            ->where('organization_user.organization_id', $organization_id)
            ->where('organization_user.status', 1)
            ->where('class_student.status', 1)
            ->get();

        return response()->json($get_dependents, 200);
    }

    public function searchreport()
    {
        $organization = $this->getOrganizationByUserId();

        $listclass = DB::table('classes')
            ->join('class_organization', 'class_organization.class_id', '=', 'classes.id')
            ->select('classes.id as id', 'classes.nama', 'classes.levelid')
            ->where([
                ['class_organization.organization_id', $organization[0]->id]
            ])
            ->orderBy('classes.nama')
            ->get();

        return view('fee.report-search.index', compact('organization', 'listclass'));
    }

    public function collectreport()
    {
        $organization = $this->getOrganizationByUserId();
        $role = 0;
        $listclass = DB::table('classes')
            ->join('class_organization', 'class_organization.class_id', '=', 'classes.id')
            ->select('classes.id as id', 'classes.nama', 'classes.levelid')
            ->where([
                ['class_organization.organization_id', $organization[0]->id]
            ])
            ->orderBy('classes.nama')
            ->get();

        if(Auth::user()->hasRole('Superadmin') || Auth::user()->hasRole('Pentadbir')){
            $role = 1;
        }

        return view('fee.fee-report.index', compact('organization', 'listclass', 'role'));
    }

    public function getFeesReceiptDataTable(Request $request){
        // yuqin added listHistory1 to get the transaction with category A only
        $start = date('Y-m-d 00:00:00', strtotime($request->date_started));
        $end = date('Y-m-d 00:00:00', strtotime($request->date_end));
        // dd($start, $end);
        if(Auth::user()->hasRole('Superadmin'))
        {
            if($request->oid === NULL)
            {
                $listHisotry = DB::table('transactions as t')
                    ->where('t.description', "like", 'YS%')
                    ->whereBetween('datetime_created', [$start, $end])
                    ->where('t.status', 'success')
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->get();

                $listHistory1 = DB::table('transactions as t')
                    ->where('t.description', "like", 'YS%')
                    ->whereBetween('datetime_created', [$start, $end])
                    ->where('t.status', 'success')
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->get();
            }
            else{
                $listHisotry = DB::table('transactions as t')
                    ->join('fees_transactions_new as ftn', 'ftn.transactions_id', 't.id')
                    ->join('student_fees_new as sfn', 'sfn.id', 'ftn.student_fees_id')
                    ->join('class_student as cs', 'cs.id', 'sfn.class_student_id')
                    ->join('class_organization as co', 'co.id', 'cs.organclass_id')
                    ->where('t.description', "like", 'YS%')
                    ->where('t.status', 'success')
                    ->where('co.organization_id', $request->oid)
                    ->whereBetween('datetime_created', [$start, $end])
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->distinct('name')
                    ->get();

                $listHistory1 = DB::table('transactions as t')
                    ->join('fees_new_organization_user as fnou', 'fnou.transaction_id', 't.id')
                    ->join('organization_user as ou', 'ou.id', '=', 'fnou.organization_user_id')
                    ->where('t.description', "like", 'YS%')
                    ->where('t.status', 'success')
                    ->where('ou.organization_id', $request->oid)
                    ->whereBetween('datetime_created', [$start, $end])
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->distinct('name')
                    ->get();
            }
        }
        else{
            if($request->oid === NULL)
            {
                $listHisotry = DB::table('transactions as t')
                    ->where('t.user_id', Auth::id())
                    ->where('t.description', "like", 'YS%')
                    ->where('t.status', 'success')
                    ->whereBetween('datetime_created', [$start, $end])
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->get();

                $listHistory1 = DB::table('transactions as t')
                    ->where('t.user_id', Auth::id())
                    ->where('t.description', "like", 'YS%')
                    ->where('t.status', 'success')
                    ->whereBetween('datetime_created', [$start, $end])
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->get();
            }
            else if(Auth::user()->hasRole('Pentadbir'))
            {
                $listHisotry = DB::table('transactions as t')
                    ->join('fees_transactions_new as ftn', 'ftn.transactions_id', 't.id')
                    ->join('student_fees_new as sfn', 'sfn.id', 'ftn.student_fees_id')
                    ->join('class_student as cs', 'cs.id', 'sfn.class_student_id')
                    ->join('class_organization as co', 'co.id', 'cs.organclass_id')
                    ->where('t.description', "like", 'YS%')
                    ->where('t.status', 'success')
                    ->where('co.organization_id', $request->oid)
                    ->whereBetween('datetime_created', [$start, $end])
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->distinct('name')
                    ->get();

                $listHistory1 = DB::table('transactions as t')
                    ->join('fees_new_organization_user as fnou', 'fnou.transaction_id', 't.id')
                    ->join('organization_user as ou', 'ou.id', '=', 'fnou.organization_user_id')
                    ->where('t.description', "like", 'YS%')
                    ->where('t.status', 'success')
                    ->where('ou.organization_id', $request->oid)
                    ->whereBetween('datetime_created', [$start, $end])
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->distinct('name')
                    ->get();
            }
            else if(Auth::user()->hasRole('Guru'))
            {
                $listHisotry = DB::table('transactions as t')
                    ->join('fees_transactions_new as ftn', 'ftn.transactions_id', 't.id')
                    ->join('student_fees_new as sfn', 'sfn.id', 'ftn.student_fees_id')
                    ->join('class_student as cs', 'cs.id', 'sfn.class_student_id')
                    ->join('class_organization as co', 'co.id', 'cs.organclass_id')
                    ->join('organization_user', 'co.organ_user_id', 'organization_user.id')
                    ->where('t.description', "like", 'YS%')
                    ->where('t.status', 'success')
                    ->where('organization_user.user_id', Auth::id())
                    ->where('co.organization_id', $request->oid)
                    ->whereBetween('datetime_created', [$start, $end])
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->distinct('name')
                    ->get();

                $listHistory1 = DB::table('transactions as t')
                    ->join('fees_new_organization_user as fnou', 'fnou.transaction_id', 't.id')
                    ->join('organization_user as ou', 'ou.id', '=', 'fnou.organization_user_id')
                    ->where('t.description', "like", 'YS%')
                    ->where('t.status', 'success')
                    ->where('ou.user_id', Auth::id())
                    ->where('ou.organization_id', $request->oid)
                    ->whereBetween('datetime_created', [$start, $end])
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->distinct('name')
                    ->get();
            }
            else{
                $listHisotry = DB::table('transactions as t')
                    ->join('fees_transactions_new as ftn', 'ftn.transactions_id', 't.id')
                    ->join('student_fees_new as sfn', 'sfn.id', 'ftn.student_fees_id')
                    ->join('class_student as cs', 'cs.id', 'sfn.class_student_id')
                    ->join('class_organization as co', 'co.id', 'cs.organclass_id')
                    ->where('t.user_id', Auth::id())
                    ->where('t.description', "like", 'YS%')
                    ->where('t.status', 'success')
                    ->where('co.organization_id', $request->oid)
                    ->whereBetween('datetime_created', [$start, $end])
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->distinct('name')
                    ->get();

                $listHistory1 = DB::table('transactions as t')
                    ->join('fees_new_organization_user as fnou', 'fnou.transaction_id', 't.id')
                    ->join('organization_user as ou', 'ou.id', '=', 'fnou.organization_user_id')
                    ->where('t.user_id', Auth::id())
                    ->where('t.description', "like", 'YS%')
                    ->where('t.status', 'success')
                    ->where('ou.organization_id', $request->oid)
                    ->whereBetween('datetime_created', [$start, $end])
                    ->select('t.id as id', 't.nama as name', 't.description as desc', 't.amount as amount', 't.datetime_created as date')
                    ->distinct('name')
                    ->get();
            }
        }

        $listHisotry = array_merge($listHisotry->toArray(), $listHistory1->toArray());
        $listHisotry = $this->array_multidimensional_unique($listHisotry);
        
        if (request()->ajax()) {
            return datatables()->of($listHisotry)
                ->editColumn('amount', function ($data) {
                    return number_format($data->amount, 2);
                })
                ->editColumn('date', function ($data) {
                    $formatedDate = Carbon::createFromFormat('Y-m-d H:i:s', $data->date)->format('d/m/Y');
                    return $formatedDate;
                })
                ->addColumn('action', function ($data) {

                    $token = csrf_token();
                    $btn = '<div class="d-flex justify-content-center">';
                    $btn = $btn . '<a href=" ' . route('receipttest', $data->id) . ' " class="btn btn-primary m-1">Papar Resit</a></div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function array_multidimensional_unique($input){
        $output = array_map("unserialize",
        array_unique(array_map("serialize", $input)));
        return $output;
    }

    public function cetegoryReportIndex(){

        $organization = $this->getOrganizationByUserId();

        return view('fee.categoryReport.index', compact('organization'));
    }

    public function fetchClassForCateYuran(Request $request)
    {

        // dd($request->get('schid'));
        $oid = $request->get('oid');

        if(Auth::user()->hasRole('Superadmin') || Auth::user()->hasRole('Pentadbir'))
        {
            $list = DB::table('classes')
                ->join('class_organization', 'class_organization.class_id', '=', 'classes.id')
                ->select('classes.id as cid', 'classes.nama as cname')
                ->where([
                    ['class_organization.organization_id', $oid],
                    ['classes.status', 1]
                ])
                ->orderBy('classes.nama')
                ->get();
        }
        else
        {
            $list = DB::table('class_organization')
                ->leftJoin('classes', 'class_organization.class_id', '=', 'classes.id')
                ->leftJoin('organization_user', 'class_organization.organ_user_id', 'organization_user.id')
                ->select('classes.id as cid', 'classes.nama as cname')
                ->where([
                    ['class_organization.organization_id', $oid],
                    ['classes.status', 1],
                    ['organization_user.user_id', Auth::id()]
                ])
                ->orderBy('classes.nama')
                ->get();
        }

        return response()->json(['success' => $list]);
    }

    public function fetchYuran(Request $request)
    {
        $oid = $request->oid;
        if($request->classid == "ALL"){
            $lists = DB::table('fees_new')
            ->select('fees_new.*', DB::raw("CONCAT(fees_new.category, ' - ', fees_new.name) AS name"))
            ->where('organization_id', $oid)
            ->where('status', 1)
            // ->groupBy('category')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
        }
        else{
            $class = ClassModel::find($request->classid);

            $lists = DB::table('fees_new')
            ->select('fees_new.*', DB::raw("CONCAT(fees_new.category, ' - ', fees_new.name) AS name"))
            ->where('organization_id', $oid)
            ->where('status', 1)
            // ->groupBy('category')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

            foreach($lists as $key=>$list)
            {
                $std = DB::table('students')
                ->join('class_student as cs', 'cs.student_id', '=', 'students.id')
                ->join('class_organization as co', 'co.id', '=', 'cs.organclass_id')
                ->where('co.class_id', $request->classid)
                ->whereNotNull('cs.dorm_id')
                ->select('cs.dorm_id')
                ->groupBy('cs.dorm_id')
                ->get();

                if($list->category == "Kategory D"){
                    $target = json_decode($list->target);
                    if($target->data == "ALL_TYPE")
                    {
                        continue;
                    }

                    if(isset($target->dorm))
                    {
                        $count = 0;
                        foreach($std as $std){
                            if(is_array($target->dorm)){
                                foreach($target->dorm as $dorm){
                                    if($std->dorm_id == $dorm)
                                    {
                                        // dd($std->dorm_id,  $dorm);
                                        $count = 1;
                                    }
                                }
                            } 
                        }
                        if($count == 1)
                            continue;
                    }
                }
                else{
                    $target = json_decode($list->target);
                    // dd($target->data);

                    if($target->data == "All_Level" || $target->data == "ALL" || $target->data == $class->levelid)
                    {
                        continue;
                    }

                    if(is_array($target->data))
                    {
                        if(in_array($class->id, $target->data))
                        {
                            continue;
                        }
                    }
                }
                unset($lists[$key]);
            }
        }
        
        return response()->json(['success' => $lists]);
    }

    public function fetchInactiveYuranByCategory(Request $request){
        $oid = $request->oid;
        $catname = $request->catname;

        $yurans = DB::table('fees_new')
        ->where('organization_id', $oid)
        ->where('category', $catname)
        ->where('status', 0)
        ->where('end_date', '<', date("Y-m-d"))
        ->select('fees_new.*', DB::raw("CONCAT(fees_new.category, ' - ', fees_new.name) AS name"))
        ->groupBy('id')
        ->get();

        return response()->json(['success' => $yurans]);
    }

    public function fetchYuranByOrganizationId(Request $request)
    {
        $oid = $request->oid;

        $yurans = DB::table('fees_new')
            ->where('organization_id', $oid)
            ->where('status', 1)
            ->select('fees_new.*', DB::raw("CONCAT(fees_new.category, ' - ', fees_new.name) AS name"))
            ->orderBy('category')
            ->orderBy('name')
            ->get();
        
        return response()->json(['success' => $yurans]);
    }

    public function fetchCategoryByOrganizationId(Request $request)
    {
        $oid = $request->oid;

        if($request->classid == "ALL"){
            $lists = DB::table('fees_new')
            ->select('fees_new.*', DB::raw("CONCAT(fees_new.category, ' - ', fees_new.name) AS name"))
            ->where('organization_id', $oid)
            ->groupBy('category')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
        }
        else{
            $class = ClassModel::find($request->classid);

            $lists = DB::table('fees_new')
            ->select('fees_new.*', DB::raw("CONCAT(fees_new.category, ' - ', fees_new.name) AS name"))
            ->where('organization_id', $oid)
            ->groupBy('category')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

            foreach($lists as $key=>$list)
            {
                $target = json_decode($list->target);
                // dd($target->data);

                if($target->data == "All_Level" || $target->data == "ALL" || $target->data == $class->levelid)
                {
                    continue;
                }

                if(is_array($target->data))
                {
                    if(in_array($class->id, $target->data))
                    {
                        continue;
                    }
                }

                if(isset($target->dorm)){
                    
                }

                unset($lists[$key]);
            }
        }

        return response()->json(['success' => $lists]);
    }

    public function studentDebtDatatable(Request $request)
    {
        $fees = Fee_New::find($request->feeid);

        if (request()->ajax()) {
            if($fees->category == "Kategory A")
            {
                $data = DB::table('students as s')
                    ->leftJoin('organization_user_student as ous', 'ous.student_id', 's.id')
                    ->leftJoin('organization_user as ou', 'ou.id', 'ous.organization_user_id', 'ou.id')
                    ->leftJoin('class_student as cs', 'cs.student_id', 's.id')
                    ->leftJoin('class_organization as co', 'co.id', 'cs.organclass_id')
                    ->leftJoin('fees_new_organization_user as fou', 'fou.organization_user_id', 'ou.id')
                    ->where('fou.fees_new_id', $fees->id)
                    ->where('cs.status', 1)
                    ->where('co.class_id', $request->classid)
                    ->select('s.*', 'fou.status')
                    ->orderBy('s.nama')
                    ->get();

            }
            else
            {
                $data = DB::table('students as s')
                    ->leftJoin('class_student as cs', 'cs.student_id', 's.id')
                    ->leftJoin('class_organization as co', 'co.id', 'cs.organclass_id')
                    ->leftJoin('student_fees_new as sfn', 'sfn.class_student_id', 'cs.id')
                    ->where('sfn.fees_id', $fees->id)
                    ->where('co.class_id', $request->classid)
                    ->where('cs.status', 1)
                    ->select('s.*', 'sfn.status')
                    ->orderBy('s.nama')
                    ->get();
            }

            $table = Datatables::of($data);

            $table->addColumn('status', function ($row) {
                if ($row->status == 'Debt') {
                    $btn = '<div class="d-flex justify-content-center">';
                    $btn = $btn . '<span class="badge badge-danger"> Masih Berhutang </span></div>';

                    return $btn;
                } else {
                    $btn = '<div class="d-flex justify-content-center">';
                    $btn = $btn . '<span class="badge badge-success"> Telah Bayar </span></div>';

                    return $btn;
                }
            });


            $table->rawColumns(['status']);

            return $table->make(true);
        }
    }

    public function collectedFeeDatatable(Request $request)
    {
        $start = date('Y-m-d 00:00:00', strtotime($request->date_started));
        $end = date('Y-m-d 00:00:00', strtotime($request->date_end));

        // dd($start, $end);
        // $fees = Fee_New::find($request->feeid);
        if (request()->ajax()) {
            if($request->classid == "ALL" && $request->feeid == "ALL"){
                $data = DB::table('transactions as t')
                ->leftJoin('fees_new_organization_user as fnou', 'fnou.transaction_id', '=', 't.id')
                ->leftJoin('fees_new as fn', 'fn.id', '=', 'fnou.fees_new_id')
                ->where('t.status', 'Success')
                ->where('fn.organization_id', $request->oid)
                ->where('fnou.status', 'Paid')
                ->whereBetween('t.datetime_created', [$start, $end])
                ->whereNotNull('fn.name')
                ->select('fn.name as fee_name', 'fn.category', 'fn.totalAmount', DB::raw('count("fnou.organization_user_id") as total'), DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"))
                ->groupBy('fn.id')
                ->orderBy('fn.name')
                ->get();

                $data1 = DB::table('transactions as t')
                ->leftJoin('fees_transactions_new as ftn', 'ftn.transactions_id', '=', 't.id')
                ->leftJoin('student_fees_new as sfn', 'sfn.id', '=', 'ftn.student_fees_id')
                ->leftJoin('fees_new as fn', 'fn.id', '=', 'sfn.fees_id')
                ->where('t.status', 'Success')
                ->where('fn.organization_id', $request->oid)
                ->where('sfn.status', 'Paid')
                ->whereBetween('t.datetime_created', [$start, $end])
                ->whereNotNull('fn.name')
                ->select('fn.name as fee_name', 'fn.category', 'fn.totalAmount', DB::raw('count("fn.class_student_id") as total'), DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"))
                ->groupBy('fn.id')
                ->orderBy('fn.name')
                ->get();

                $data = array_merge($data->toArray(), $data1->toArray());
            }
            elseif($request->classid == "ALL" && $request->feeid != "ALL"){
                if($request->feeid == "Kategory A")
                {
                    $data = DB::table('transactions as t')
                    ->leftJoin('fees_new_organization_user as fnou', 'fnou.transaction_id', '=', 't.id')
                    ->leftJoin('fees_new as fn', 'fn.id', '=', 'fnou.fees_new_id')
                    ->where('t.status', 'Success')
                    ->where('fn.organization_id', $request->oid)
                    ->where('fnou.status', 'Paid')
                    ->where('fn.category', $request->feeid)
                    ->whereBetween('t.datetime_created', [$start, $end])
                    ->whereNotNull('fn.name')
                    ->select('fn.name as fee_name', 'fn.category', 'fn.totalAmount', DB::raw('count("fnou.organization_user_id") as total'), DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"))
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
                    ->where('fn.organization_id', $request->oid)
                    ->where('sfn.status', 'Paid')
                    ->where('fn.category', $request->feeid)
                    ->whereBetween('t.datetime_created', [$start, $end])
                    ->whereNotNull('fn.name')
                    ->select('fn.name as fee_name', 'fn.category', 'fn.totalAmount', DB::raw('count("fn.class_student_id") as total'), DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"))
                    ->groupBy('fn.id')
                    ->orderBy('fn.name')
                    ->get();
                }
            }
            elseif($request->classid != "ALL" && $request->feeid != "ALL"){
                if($request->feeid == "Kategory A")
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
                    ->where('fn.organization_id', $request->oid)
                    ->where('fnou.status', 'Paid')
                    ->where('fn.category', $request->feeid)
                    ->where('c.id', $request->classid)
                    ->whereBetween('t.datetime_created', [$start, $end])
                    ->whereNotNull('fn.name')
                    ->select('fn.name as fee_name', 'fn.category', 'c.nama as class_name', 'fn.totalAmount', DB::raw('count("fnou.organization_user_id") as total'), DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"))
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
                    ->where('fn.organization_id', $request->oid)
                    ->where('sfn.status', 'Paid')
                    ->where('c.id', $request->classid)
                    ->where('fn.category', $request->feeid)
                    ->whereBetween('t.datetime_created', [$start, $end])
                    ->whereNotNull('fn.name')
                    ->select('fn.name as fee_name', 'fn.category', 'c.nama as class_name', 'fn.totalAmount', DB::raw('count("fn.class_student_id") as total'), DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"))
                    ->groupBy('fn.id')
                    ->orderBy('c.nama')
                    ->orderBy('fn.name')
                    ->get();
                }

                // dd($data);
            }

            $table = Datatables::of($data);

            $table->addColumn('sum', function ($row) {
                $sum = $row->totalAmount * $row->total;
                return number_format($sum, 2);
            });

            $table->addColumn('totalAmount', function ($row) {
                return number_format($row->totalAmount, 2);
            });

            $table->addColumn('class_name', function ($row) {
                if(isset($row->class_name)){
                    return $row->class_name;
                }
                else{
                    return "Semua Kelas";
                }
            });

            $table->rawColumns(['sum', 'class_name', 'totalAmount']);

            return $table->make(true);
        }
    }

    public function fetchDorm(Request $request)
    {
        $oid = $request->get('oid');

        $list = DB::table('dorms')
            ->where('dorms.organization_id', $oid)
            ->orderBy('dorms.name')
            ->get();

        if($request->get('grade')){
            $list = DB::table('dorms')
            ->where([
                ['dorms.organization_id', $oid],
                ['dorms.grade', $request->get('grade')]
            ])
            ->orderBy('dorms.name')
            ->distinct()
            ->get();
        }

        return response()->json(['success' => $list]);
    }

    public function ExportAllYuranStatus(Request $request)
    {
        $this->validate($request, [
            'organExport'      =>  'required',
            'yuranExport'      =>  'required',
        ]);

        $yuran = DB::table('fees_new')
            ->where('id', $request->yuranExport)
            ->first();

        return Excel::download(new ExportYuranStatus($yuran), $yuran->name . '.xlsx');
    }

    public function ExportClassYuranStatus(Request $request)
    {
        $this->validate($request, [
            'organExport'      =>  'required',
            'yuranExport'      =>  'required',
            'classesExport'      =>  'required',
        ]);

        $class = $request->classesExport;
        $classname = DB::table('classes')
            ->where('id', $class)
            ->value('nama');

        $yuran = DB::table('fees_new')
            ->where('id', $request->yuranExport)
            ->first();

        return Excel::download(new ExportClassYuranStatus($yuran, $class), $yuran->name . ' (' . $classname . ').xlsx');
    }

    public function ExportCollectedYuran(Request $request)
    {
        $this->validate($request, [
            'organExport'      =>  'required',
            'yuranExport'      =>  'required',
            'classesExport'      =>  'required',
            'startExport'      =>  'required',
            'endExport'        =>  'required',
        ]);

        $class  = $request->classesExport;
        $oid    = $request->organExport;
        $yuran  = $request->yuranExport;
        $start = date('Y-m-d 00:00:00', strtotime($request->startExport));
        $end = date('Y-m-d 00:00:00', strtotime($request->endExport));

        if($yuran == "ALL")
            $setYuran = "Semua Kategori";
        else 
            $setYuran = $yuran;
        if($class == "ALL")
            $setClass = "Semua Kelas";
        else
            $setClass = $class;

        return Excel::download(new ExportCollectedYuran($yuran, $class, $oid, $start, $end), 'Kutipan Yuran ' . $setYuran . ' (' . $setClass . ').xlsx');
    }

    public function PrintAllYuranStatus(Request $request)
    {
        $this->validate($request, [
            'organPDF'      =>  'required',
            'yuranPDF'      =>  'required',
        ]);

        $yuran = DB::table('fees_new')
            ->where('id', $request->yuranPDF)
            ->first();

        $organization = DB::table('organizations')
        ->where('id', $request->organPDF)
        ->first();

        if($yuran->category == "Kategory A")
        {
            $data = DB::table('students as s')
                ->leftJoin('organization_user_student as ous', 'ous.student_id', 's.id')
                ->leftJoin('organization_user as ou', 'ou.id', 'ous.organization_user_id', 'ou.id')
                ->leftJoin('class_student as cs', 'cs.student_id', 's.id')
                ->leftJoin('class_organization as co', 'co.id', 'cs.organclass_id')
                ->leftJoin('classes as c', 'c.id', 'co.class_id')
                ->leftJoin('fees_new_organization_user as fou', 'fou.organization_user_id', 'ou.id')
                ->where('fou.fees_new_id', $yuran->id)
                ->where('cs.status', 1)
                ->select('s.nama', 'c.nama as nama_kelas', 's.gender', 'fou.status')
                ->orderBy('c.nama')
                ->orderBy('s.nama')
                ->get();
        }
        else
        {
            $data = DB::table('students as s')
                ->leftJoin('class_student as cs', 'cs.student_id', 's.id')
                ->leftJoin('class_organization as co', 'co.id', 'cs.organclass_id')
                ->leftJoin('classes as c', 'c.id', 'co.class_id')
                ->leftJoin('student_fees_new as sfn', 'sfn.class_student_id', 'cs.id')
                ->where('sfn.fees_id', $yuran->id)
                ->where('cs.status', 1)
                ->select('s.nama', 'c.nama as nama_kelas', 's.gender', 'sfn.status')
                ->orderBy('c.nama')
                ->orderBy('s.nama')
                ->get();
        }

        foreach ($data as $key => $student) {
            $student->status = $student->status == "Debt" ? "Masih Berhutang" : "Telah Bayar";
        }

        // $pdf = PDF::loadView('fee.categoryReport.reportAllYuranStatusPDFTemplate', compact('yuran', 'organization', 'data'));

        // return $pdf->download('Report ' . $yuran->name . '.pdf');
        return view('fee.categoryReport.reportAllYuranStatusPDFTemplate', compact('yuran', 'organization', 'data'));
    }

    public function PrintClassYuranStatus(Request $request)
    {
        $this->validate($request, [
            'organPDF'      =>  'required',
            'yuranPDF'      =>  'required',
            'classesPDF'    =>  'required'
        ]);

        $yuran = DB::table('fees_new')
            ->where('id', $request->yuranPDF)
            ->first();

        $organization = DB::table('organizations')
        ->where('id', $request->organPDF)
        ->first();

        if($yuran->category == "Kategory A")
        {
            $data = DB::table('students as s')
                ->leftJoin('organization_user_student as ous', 'ous.student_id', 's.id')
                ->leftJoin('organization_user as ou', 'ou.id', 'ous.organization_user_id', 'ou.id')
                ->leftJoin('class_student as cs', 'cs.student_id', 's.id')
                ->leftJoin('class_organization as co', 'co.id', 'cs.organclass_id')
                ->leftJoin('classes as c', 'c.id', 'co.class_id')
                ->leftJoin('fees_new_organization_user as fou', 'fou.organization_user_id', 'ou.id')
                ->where([
                    ['fou.fees_new_id', $yuran->id],
                    ['c.id', $request->classesPDF]
                ])
                ->where('cs.status', 1)
                ->select('s.nama', 'c.nama as nama_kelas', 's.gender', 'fou.status')
                ->orderBy('c.nama')
                ->orderBy('s.nama')
                ->get();
        }
        else
        {
            $data = DB::table('students as s')
                ->leftJoin('class_student as cs', 'cs.student_id', 's.id')
                ->leftJoin('class_organization as co', 'co.id', 'cs.organclass_id')
                ->leftJoin('classes as c', 'c.id', 'co.class_id')
                ->leftJoin('student_fees_new as sfn', 'sfn.class_student_id', 'cs.id')
                ->where([
                    ['sfn.fees_id', $yuran->id],
                    ['c.id', $request->classesPDF]
                ])
                ->where('cs.status', 1)
                ->select('s.nama', 'c.nama as nama_kelas', 's.gender', 'sfn.status')
                ->orderBy('c.nama')
                ->orderBy('s.nama')
                ->get();
        }

        foreach ($data as $key => $student) {
            $student->status = $student->status == "Debt" ? "Masih Berhutang" : "Telah Bayar";
        }

        // $pdf = PDF::loadView('fee.report-search.reportClassYuranStatusPDF', compact('yuran', 'organization', 'data'));

        // return $pdf->download('Report ' . $yuran->name . ' (' . $data[0]->nama_kelas . ').pdf');
        return view('fee.report-search.reportClassYuranStatusPDF', compact('yuran', 'organization', 'data'));
    }

    public function PrintCollectedYuran(Request $request)
    {
        $this->validate($request, [
            'organPDF'      =>  'required',
            'yuranPDF'      =>  'required',
            'classesPDF'    =>  'required',
            'startPDF'      =>  'required',
            'endPDF'        =>  'required',
        ]);

        $class  = $request->classesPDF;
        $oid    = $request->organPDF;
        $yuran  = $request->yuranPDF;
        $start = date('Y-m-d 00:00:00', strtotime($request->startPDF));
        $end = date('Y-m-d 00:00:00', strtotime($request->endPDF));

        $organization = DB::table('organizations')
        ->where('id', $oid)
        ->first();

        if($yuran == "ALL")
            $setYuran = "Semua Kategori";
        else 
            $setYuran = $yuran;

        if($class == "ALL")
            $setClass = "Semua Kelas";
        else
            $setClass = $class;

        if($class == "ALL" && $yuran == "ALL"){
            $data = DB::table('transactions as t')
            ->leftJoin('fees_new_organization_user as fnou', 'fnou.transaction_id', '=', 't.id')
            ->leftJoin('fees_new as fn', 'fn.id', '=', 'fnou.fees_new_id')
            ->where('t.status', 'Success')
            ->where('fn.organization_id', $oid)
            ->where('fnou.status', 'Paid')
            ->whereBetween('t.datetime_created', [$start, $end])
            ->whereNotNull('fn.name')
            ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 't.status', 'fn.totalAmount', DB::raw('count("fnou.organization_user_id") as total'), DB::raw('fn.totalAmount * count("fnou.organization_user_id") as sum'))
            ->groupBy('fn.id')
            ->orderBy('fn.name')
            ->get();

            $data1 = DB::table('transactions as t')
            ->leftJoin('fees_transactions_new as ftn', 'ftn.transactions_id', '=', 't.id')
            ->leftJoin('student_fees_new as sfn', 'sfn.id', '=', 'ftn.student_fees_id')
            ->leftJoin('fees_new as fn', 'fn.id', '=', 'sfn.fees_id')
            ->where('t.status', 'Success')
            ->where('fn.organization_id', $oid)
            ->where('sfn.status', 'Paid')
            ->whereBetween('t.datetime_created', [$start, $end])
            ->whereNotNull('fn.name')
            ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 't.status', 'fn.totalAmount', DB::raw('count("fn.class_student_id") as total'), DB::raw('fn.totalAmount * count("fn.class_student_id") as sum'))
            ->groupBy('fn.id')
            ->orderBy('fn.name')
            ->get();

            $data = array_merge($data->toArray(), $data1->toArray());
        }
        elseif($class == "ALL" && $yuran != "ALL"){
            if($yuran == "Kategory A")
            {
                $data = DB::table('transactions as t')
                ->leftJoin('fees_new_organization_user as fnou', 'fnou.transaction_id', '=', 't.id')
                ->leftJoin('fees_new as fn', 'fn.id', '=', 'fnou.fees_new_id')
                ->where('t.status', 'Success')
                ->where('fn.organization_id', $oid)
                ->where('fnou.status', 'Paid')
                ->where('fn.category', $yuran)
                ->whereBetween('t.datetime_created', [$start, $end])
                ->whereNotNull('fn.name')
                ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 't.status', 'fn.totalAmount', DB::raw('count("fnou.organization_user_id") as total'), DB::raw('fn.totalAmount * count("fnou.organization_user_id") as sum'))
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
                ->where('fn.organization_id', $oid)
                ->where('sfn.status', 'Paid')
                ->where('fn.category', $yuran)
                ->whereBetween('t.datetime_created', [$start, $end])
                ->whereNotNull('fn.name')
                ->select(DB::raw("CONCAT(fn.category, ' - ', fn.name) AS name"), 't.status', 'fn.totalAmount', DB::raw('count("fn.class_student_id") as total'), DB::raw('fn.totalAmount * count("fn.class_student_id") as sum'))
                ->groupBy('fn.id')
                ->orderBy('fn.name')
                ->get();
            }
        }
        elseif($class != "ALL" && $yuran != "ALL"){
            if($yuran == "Kategory A")
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
                ->where('fn.organization_id', $oid)
                ->where('fnou.status', 'Paid')
                ->where('fn.category', $yuran)
                ->where('c.id', $class)
                ->whereBetween('t.datetime_created', [$start, $end])
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
                ->where('fn.organization_id', $oid)
                ->where('sfn.status', 'Paid')
                ->where('c.id', $class)
                ->where('fn.category', $yuran)
                ->whereBetween('t.datetime_created', [$start, $end])
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
            if($class == "ALL")
            {
                $list->class_name = "Semua Kelas";
            }
        }

        // $pdf = PDF::loadView('fee.fee-report.reportCollectedYuranPDF', compact('setYuran', 'organization', 'data', 'start', 'end'));

        // return $pdf->download('Report Kutipan Yuran ' . $setYuran . ' (' . $setClass . ').pdf');
        return view('fee.fee-report.reportCollectedYuranPDF', compact('setYuran', 'organization', 'data', 'start', 'end'));
    }

    public function ExportStudentStatus(Request $request)
    {
        $yuran = DB::table('fees_new')
            ->where('id', $request->kelasExport)
            ->first();

        return Excel::download(new ExportYuranStatus($yuran), $yuran->name . '.xlsx');
    }

    public function PrintStudentStatus(Request $request)
    {
        $this->validate($request, [
            'organPDF'      =>  'required',
            'yuranPDF'      =>  'required',
        ]);

        $yuran = DB::table('fees_new')
            ->where('id', $request->yuranPDF)
            ->first();

        $organization = DB::table('organizations')
        ->where('id', $request->organPDF)
        ->first();

        if($yuran->category == "Kategory A")
        {
            $data = DB::table('students as s')
                ->leftJoin('organization_user_student as ous', 'ous.student_id', 's.id')
                ->leftJoin('organization_user as ou', 'ou.id', 'ous.organization_user_id', 'ou.id')
                ->leftJoin('class_student as cs', 'cs.student_id', 's.id')
                ->leftJoin('class_organization as co', 'co.id', 'cs.organclass_id')
                ->leftJoin('classes as c', 'c.id', 'co.class_id')
                ->leftJoin('fees_new_organization_user as fou', 'fou.organization_user_id', 'ou.id')
                ->where('fou.fees_new_id', $yuran->id)
                ->select('s.nama', 'c.nama as nama_kelas', 's.gender', 'fou.status')
                ->orderBy('c.nama')
                ->orderBy('s.nama')
                ->get();
        }
        else
        {
            $data = DB::table('students as s')
                ->leftJoin('class_student as cs', 'cs.student_id', 's.id')
                ->leftJoin('class_organization as co', 'co.id', 'cs.organclass_id')
                ->leftJoin('classes as c', 'c.id', 'co.class_id')
                ->leftJoin('student_fees_new as sfn', 'sfn.class_student_id', 'cs.id')
                ->where('sfn.fees_id', $yuran->id)
                ->select('s.nama', 'c.nama as nama_kelas', 's.gender', 'sfn.status')
                ->orderBy('c.nama')
                ->orderBy('s.nama')
                ->get();
        }

        foreach ($data as $key => $student) {
            $student->status = $student->status == "Debt" ? "Masih Berhutang" : "Telah Bayar";
        }

        $start = $request->startPDF;
        $end = $request->endPDF;

        $pdf = PDF::loadView('fee.categoryReport.reportAllYuranStatusPDFTemplate', compact('yuran', 'organization', 'data'));

        return $pdf->download('Report ' . $yuran->name . '.pdf');
        // return view('fee.categoryReport.reportAllYuranStatusPDFTemplate', compact('yuran', 'organization', 'data'));
    }
}
