<?php

namespace App\Http\Controllers;

use App\Exports\OutingExport;
use App\Exports\DormExport;
use App\Imports\DormImport;
use Illuminate\Http\Request;
use App\Models\Dorm;
use App\Models\Outing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TeacherExport;
use App\Imports\TeacherImport;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\User;
use Illuminate\Validation\Rule;
use App\Models\TypeOrganization;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;

class DormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    //
    //
    //index functions
    public function index()
    {
        //
        $organization = $this->getOrganizationByUserId();

        return view('dorm.outing.index', compact('organization'));
    }

    public function indexOuting()
    {
        // 
        $organization = $this->getOrganizationByUserId();

        return view('dorm.outing.index', compact('organization'));
    }

    public function indexResident()
    {
        // 
        $organization = $this->getOrganizationByUserId();

        return view('dorm.resident.index', compact('organization'));
    }

    public function indexDorm()
    {
        // 
        $organization = $this->getOrganizationByUserId();

        return view('dorm.management.index', compact('organization'));
    }

    //
    //
    //import and export functions
    public function outingexport()
    {
        return Excel::download(new OutingExport, 'outing.xlsx');
    }

    public function dormexport(Request $request)
    {
        return Excel::download(new DormExport($request->organ), 'dorm.xlsx');
    }

    public function dormimport(Request $request)
    {
        $file       = $request->file('file');
        $namaFile   = $file->getClientOriginalName();
        $file->move('uploads/excel/', $namaFile);

        $etx = $file->getClientOriginalExtension();
        $formats = ['xls', 'xlsx', 'ods', 'csv'];
        if (!in_array($etx, $formats)) {

            return redirect('/dorm/dorm/indexDorm')->withErrors(['format' => 'Only supports upload .xlsx, .xls files']);
        }

        Excel::import(new DormImport($request->organ), public_path('/uploads/excel/' . $namaFile));

        return redirect('/dorm/dorm/indexDorm')->with('success', 'Dorms have been added successfully');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    //
    //
    //create or add files
    public function create()
    {
        //

    }

    public function createOuting()
    {
        //
        $organization = $this->getOrganizationByUserId();
        return view('dorm.outing.add', compact('organization'));
    }

    public function createResident()
    {
        //
        $userid     = Auth::id();

        $school = DB::table('organizations')
            ->join('organization_user', 'organization_user.organization_id', '=', 'organizations.id')
            ->select('organizations.id as schoolid')
            ->where('organization_user.user_id', $userid)
            ->first();

        // dd($userid);

        $listclass = DB::table('classes')
            ->join('class_organization', 'class_organization.class_id', '=', 'classes.id')
            ->select('classes.id as id', 'classes.nama', 'classes.levelid')
            ->where([
                ['class_organization.organization_id', $school->schoolid]
            ])
            ->orderBy('classes.nama')
            ->get();

        $organization = $this->getOrganizationByUserId();


        return view('dorm.resident.add', compact('listclass', 'organization'));
    }

    public function createDorm()
    {
        //
        $organization = $this->getOrganizationByUserId();
        return view('dorm.management.add', compact('organization'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    //
    //
    // store functions
    public function store(Request $request)
    {
        // 

    }

    public function storeOuting(Request $request)
    {
        // 
        $this->validate($request, [
            'start_date'        =>  'required',
            'end_date'          =>  'required',
            'organization'      =>  'required',
        ]);

        DB::table('outings')->insert([
            'start_date_time' => $request->get('start_date'),
            'end_date_time'   => $request->get('end_date'),
            'organization_id' => $request->get('organization'),
        ]);

        return redirect('/dorm/dorm/indexOuting')->with('success', 'New outing date and time has been added successfully');
    }

    //haven't modify yet
    public function storeDorm(Request $request)
    {
        // 
        $this->validate($request, [
            'start_date'        =>  'required',
            'end_date'          =>  'required',
            'organization'      =>  'required',
        ]);

        DB::table('outings')->insert([
            'start_date_time' => $request->get('start_date'),
            'end_date_time'   => $request->get('end_date'),
            'organization_id' => $request->get('organization'),
        ]);

        return redirect('/dorm/dorm/indexOuting')->with('success', 'New outing date and time has been added successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //

    }

    public function editOuting($id)
    {
        //  
        $teacher = DB::table('users')
            ->join('organization_user', 'organization_user.user_id', '=', 'users.id')
            ->join('organizations', 'organization_user.organization_id', '=', 'organizations.id')
            ->where('users.id', $id)
            ->where('organization_user.role_id', 5)
            ->select('organizations.id as organization_id', 'users.id as uid', 'users.name as tcname', 'users.icno as icno', 'users.email as email', 'users.telno as telno', 'organization_user.role_id as role_id')
            ->first();

        $outing = DB::table('outings')
            ->where('outings.id', $id)
            ->select('outings.id', 'outings.start_date_time', 'outings.end_date_time', 'outings.organization_id')
            ->first();

        $organization = $this->getOrganizationByUserId();

        return view('dorm.outing.update', compact('outing', 'organization', 'id'));
    }

    public function update(Request $request, $id)
    {
        //

    }

    public function updateOuting(Request $request, $id)
    {
        //
        // dd($id);
        $this->validate($request, [
            'start_date'        =>  'required',
            'end_date'          =>  'required',
            'organization'      =>  'required',
        ]);

        DB::table('outings')
            ->where('id', $id)
            ->update(
                [
                    'start_date_time' => $request->get('start_date'),
                    'end_date_time'   => $request->get('end_date')
                ]
            );

        // DB::table('class_organization')->where('class_id', $id)
        //     ->update([
        //         'organization_id' => $request->get('organization'),
        //         'organ_user_id'    =>  $request->get('classTeacher')
        //     ]);

        return redirect('/dorm/dorm/indexOuting')->with('success', 'The data has been updated!');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $organization = $this->getOrganizationByUserId();
        return view('dorm.outing.add', compact('organization'));
    }

    public function destroyOuting($id)
    {
        //
        $result = DB::table('outings')->where('outings.id', $id);

        if ($result->delete()) {
            Session::flash('success', 'Outing Berjaya Dipadam');
            return View::make('layouts/flash-messages');
        } else {
            Session::flash('error', 'Outing Gagal Dipadam');
            return View::make('layouts/flash-messages');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getOrganizationByUserId()
    {
        $userId = Auth::id();
        if (Auth::user()->hasRole('Superadmin')) {

            return Organization::all();
        } else {
            // user role pentadbir 
            return Organization::whereHas('user', function ($query) use ($userId) {
                $query->where('user_id', $userId)->Where(function ($query) {
                    $query->where('organization_user.role_id', '=', 4)
                        ->Orwhere('organization_user.role_id', '=', 5);
                });
            })->get();
        }
    }

    public function updateOutTime($id)
    {
        // $asrama = Asrama::findOrFail($id);
        // $asrama->update(array('outing_time' => new DateTime()));
        // return redirect('/asrama')->with('success', 'Data is successfully updated');
    }

    public function updateInTime($id)
    {
        // $asrama = Asrama::findOrFail($id);
        // $asrama->update(array('in_time' => new DateTime()));
        // return redirect('/asrama')->with('success', 'Data is successfully updated');
    }

    public function updateOutArriveTime($id)
    {
        // $asrama = Asrama::findOrFail($id);
        // $asrama->update(array('out_arrive_time' => new DateTime()));
        // return redirect('/asrama')->with('success', 'Data is successfully updated');
    }

    public function updateInArriveTime($id)
    {
        // $asrama = Asrama::findOrFail($id);
        // $asrama->update(array('in_arrive_time' => new DateTime()));
        // return redirect('/asrama')->with('success', 'Data is successfully updated');
    }

    public function updateOutingTime($id)
    {
        // $outing = Outing::findOrFail($id);
        // $name = $request->input('stud_name');
        // DB::update('update student set name = ? where id = ?',[$name,$id]);
        // echo "Record updated successfully.<br/>";
        // echo '<a href = "/edit-records">Click Here</a> to go back.';

        // $outing->update(array('start_date_time' => new DateTime()));
        // return redirect('/asrama')->with('success', 'Data is successfully updated');
    }

    public function getOutingsDatatable(Request $request)
    {
        // dd($request->oid);
        if (request()->ajax()) {
            $oid = $request->oid;
            $hasOrganizaton = $request->hasOrganization;

            $userId = Auth::id();

            if ($oid != '' && !is_null($hasOrganizaton)) {

                $data = DB::table('outings')
                    ->select('outings.id', 'outings.start_date_time', 'outings.end_date_time')
                    ->where('outings.organization_id', $oid)
                    ->orderBy('outings.start_date_time');
            }

            $table = Datatables::of($data);

            $table->addColumn('action', function ($row) {
                $token = csrf_token();
                $btn = '<div class="d-flex justify-content-center">';
                $btn = $btn . '<a href="' . route('dorm.editOuting', $row->id) . '" class="btn btn-primary m-1">Edit</a>';
                $btn = $btn . '<button id="' . $row->id . '" data-token="' . $token . '" class="btn btn-danger m-1">Buang</button></div>';
                return $btn;
            });

            $table->rawColumns(['action']);
            return $table->make(true);
        }
    }
}
