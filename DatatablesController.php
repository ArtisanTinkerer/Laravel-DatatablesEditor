<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use App\Role;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Input;
use yajra\Datatables\Facades\Datatables;
use DB;
use Validator;
use App\Helpers\DatatablesEditor;

use App\Http\Requests\DatatablesFormRequest;


class DatatablesController extends Controller
{

/*    public function __construct()
    {
        $this->middleware('auth');
    }*/
    /**
     * Displays datatables front end view
     *
     * @return \Illuminate\View\View
     */
    public function getIndex()
    {

        return view('laraboot.laraboot');
    }

    /**
     * Process datatables ajax request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function anyData()

    {
        $test =Datatables::of(User::select('*'))->make(true);

        return $test;
    }


    /**[dataResponse2 description]
     *
     * @param  Request $req
     * @return JSON       JSON response
     */
    public function dataResponse(Request $req) {

        //https://github.com/CodeToWeb/Laravel-DatatablesEditor/blob/master/DatatablesController.php
        $input = $req->input();
        $action = $input['action'];
        $rowIdArray = array_keys($input['data']);
        foreach($rowIdArray as $rowId) {
            $dataArray[] = $input['data'][$rowId];
        }


        //MB 25/11/2015 - Ok this is what the problem is
        // The editor package is only made for the separate editor form which always sends the whole row - so this gets returned
        // The inline editor only sends the edited field so these are the only ones that are in the response
        //The datagrid only refreshes when it gets a full row
        //So, the fix is to get the row and send it back


        //get the fields we have posted (only 1 if inline edit, otherwise all)
        $arrFieldsToValidate = array_keys($input['data'][$rowId]);
        //iterate through them and add the validation rules
        foreach($arrFieldsToValidate as $fieldToValidate){
            switch($fieldToValidate){
                case 'name':
                    $arrFieldsToValidate['name']='required|max:20';
                    break;
                case 'email':
                    $arrFieldsToValidate['email']='required|max:30';
                    break;

            }
        }
        //MB End*/

        //original code
       //$validator = Validator::make($input['data'][$rowIdArray[0]], ['name' => 'required|max:20','email' => 'required|max:30']);


        $validator = Validator::make($input['data'][$rowIdArray[0]],$arrFieldsToValidate );

        /*$validator->setCustomMessages([
            'name.required' => 'Please provide your first name.',
            'name.max' => 'Please shorten your first name.',
            'email.required' => 'Please provide your last name.',
            'email.max' => 'Please shorten your last name.',
        ]);*/


        $returnArray =  DatatablesEditor::process($req,  $validator,   /* 'App\Models\User' */  /* User::all() */  User::where ('id',   '<=', 50) );


        //$returnArray =  DatatablesEditor::process($req,  $validator,   User::all());
        // This was used to create static json responses for testing purposes
        // $returnArray =  DatatablesEditor::processStaticTest($req,  $validator, 'App\Models\User');

        return $returnArray;

    }



}
