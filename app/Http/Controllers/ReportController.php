<?php

namespace App\Http\Controllers;

use App\config\Constants;
use App\Http\Requests\createReport;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * This method is used to Report users or post using dynamic relationship
     */
    public function createReport(createReport $request)
    {
        $user   =   Auth::user();
        $name  =   $request->input('reportee');
        $reporteeId =   $request->input('reportee_id');

        $modelName  =  'App\\Models\\' . $name;
        $reporteeObj    =   ($name == 'Post') ? $modelName::find($reporteeId) : $modelName::find($reporteeId);
        if ($reporteeObj == null) {
            return response()->json([
                'status'    =>  400,
                'success'   =>  false,
                'error'     =>  $name . ' does not exist'
            ],400);
        }

        $ReportModel    =   new Report();
        $ReportModel->Reportable()->create([
            'reporter_id'       =>  $user->id,
            'reportable_type'   =>  $modelName,
            'reportable_id'     =>  $reporteeId,
            'type'              =>  $request->input('type'),
            'body'              =>  $request->input('body'),
            'status'            =>  Constants::STATUS_ACTIVE,
            'created_at'        =>  $request->input('current_time')
        ]);

        if($name == 'Post')
        {
            $postModel          =   new Post();
            $data['status']     =   Constants::POSTS_TYPE_REPORTED;
            $updatedPost        =   $postModel->updatePost($reporteeId,$data);
        }

        return  response()->json([
            'status' => '200',
            'success' => true,
            'message' => $name.' has been reported'
        ], 200);
    }
}
