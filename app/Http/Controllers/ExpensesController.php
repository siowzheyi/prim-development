<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class ExpensesController extends Controller
{
    //
    public function create()
    {
        // dd("ehere");
        // // $organization = $this->getOrganizationByUserId();
        // if(Auth::user()->hasRole('Superadmin')){
        //     $organization = Organization::all();
        // }
        // else{
        //     $organization = DB::table('organizations')
        //     ->join('organization_user as ou', 'ou.organization_id', '=', 'organizations.id')
        //     ->where([
        //         ['ou.user_id', Auth::user()->id],
        //         ['ou.role_id', 6],
        //     ])
        //     ->select('organizations.id', 'organizations.nama')
        //     ->distinct()
        //     ->get();
        // }

        // $start = date('Y-m-d', strtotime(DB::table('outings')
        //     ->where([
        //         ['outings.organization_id', $organization[0]->id],
        //         ['outings.end_date_time', '>', now()],
        //     ])
        //     ->orderBy("outings.end_date_time")
        //     ->value("outings.end_date_time as end_date_time")));

        // $end = date('Y-m-d', strtotime(DB::table('outings')
        //     ->where([
        //         ['outings.organization_id', $organization[0]->id],
        //         ['outings.end_date_time', '>', now()],
        //     ])
        //     ->orderBy("outings.end_date_time")
        //     ->value("outings.end_date_time as end_date_time")));
        // return view('dorm.index', compact('roles', 'checkin', 'checkNum', 'organization', 'isblacklisted'));

 
        return view('pentadbir.recurring-fees.add');
    }

    public function store(Request $request)
    {
        //
        $this->validate($request, [
            'name'         =>  'required',
            // 'email'        =>  'required',
            'category'     =>  'required',
            'reason'       =>  'required',
            'start_date'   =>  'required',
            'organization' =>  'required',
        ]);

        $categoryReal = $this->categoryReal;

        $classstudentid = DB::table('students')
            ->join('class_student', 'class_student.id', '=', 'students.id')
            ->where([
                ['students.id', $request->get('name')],
                // ['students.email', $request->get('email')],
                // ['students.parent_tel', Auth::user()->telno],
                ['class_student.outing_status', NULL],
            ])
            ->orWhere([
                ['students.id', $request->get('name')],
                // ['students.email', $request->get('email')],
                // ['students.parent_tel', Auth::user()->telno],
                ['class_student.outing_status', 0],
            ])
            ->value("class_student.id");


        $outingtype = DB::table('classifications')
            ->where([
                ['classifications.id', $request->get('category')],
            ])
            ->value('classifications.name');

        // $categoryReal[2] = "OUTINGS"
        if (strtoupper($outingtype) == $categoryReal[2]) {
            $outingid = DB::table('outings')
                ->where('outings.organization_id', $request->get('organization'))
                ->where([
                    // ['outings.start_date_time', '>=', $request->get('start_date')],
                    ['outings.end_date_time', '>', $request->get('start_date')],
                ])
                ->value('outings.id');

            if ($outingid == NULL) {
                return redirect('/dorm/create')->withErrors('Selected outings date and time is not available');
            }
        } else {
            $outingid = NULL;
        }

        if (isset($classstudentid)) {
            DB::table('student_outing')
                ->insert([
                    'reason'            => $request->get('reason'),
                    'apply_date_time'   => $request->get('start_date'),
                    'status'            => 0,
                    'classification_id' => $request->get('category'),
                    'class_student_id'  => $classstudentid,
                    'outing_id'         => $outingid,
                    'created_at'        => now(),
                ]);

            $arrayRecipientEmail = DB::table('users')
                ->join('organization_user', 'organization_user.user_id', '=', 'users.id')
                ->where('organization_user.organization_id', $request->get('organization'))
                ->where('organization_user.check_in_status', '=', 1)
                ->orWhere('organization_user.role_id', '=', 4)
                ->select('users.email')
                ->get();
            // dd($arrayRecipientEmail);

            if (isset($arrayRecipientEmail)) {
                foreach ($arrayRecipientEmail as $email) {
                    // dd("here inside foreach");
                    // Mail::to($email)->send(new NotifyMail());
                    Mail::to($email)->send(new NotifyMail());

                    if (Mail::failures()) {
                        dd("fail");
                        return response()->Fail('Sorry! Please try again latter');
                    } else {
                        // return redirect('/dorm')->with('success', 'Great! Successfully send in your mail');

                        // return response()->json(['success' => 'Great! Successfully send in your mail']);
                        // dd("successs", $email);
                    }
                }
            } else {
                // do nothing 1st
            }

            return redirect('/sekolah/dorm/indexRequest/6')->with('success', 'New application has been added successfully');
        } else {
            return redirect('/sekolah/dorm/indexRequest/6')->withErrors('Failed to submit application');
        }
    }
}
