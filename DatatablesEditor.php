<?php
namespace App\Helpers;
/**
 * Created by PhpStorm.
 * User: John Kirkpatrick
 * Date: 9/1/2015
 * Time: 12:00 AM
 */


use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;


class DatatablesEditor {


    public function __construct()
    {

    }


    /**
     * @param Request $req
     * @param Validator $validator
     * @param Model $model
     * @return \Illuminate\Http\JsonResponse
     *
     * Process AJAX request from Datatables Editor http://editor.datatables.net/
     * Create, Update, Delete (soft)
     */
    public static function process(Request $req, $validator, $model) {

        $input = $req->input();

        $rowFirstId = array_keys($input['data'])[0];
        $rowIdArray = array_keys($input['data']);


        $data = null;

        // This is done outside of the class and passed in
        // do form validation
        /*
        $validator = Validator::make($input['data'][$rowFirstId], [
            //  'name_first' => 'required|max:10|same:name_last|ip',
            'name_first' => 'required|max:10',
            'name_last' => 'required|max:10'
        ]);

        $validator->setCustomMessages([
            'name_first.required' => 'Please provide your first name.',
            'name_first.max' => 'Please shorten your first name.',
            'name_last.required' => 'Please provide your last name.',
            'name_last.max' => 'Please shorten your last name.',
        ]);
        */

        if ($validator->fails()) {
            $messages = $validator->errors();
            $msgKeys = $messages->keys();

            foreach ($msgKeys as $msgKey) {
                $msgError[] =
                    array(
                        'name' => $msgKey,
                        'status' => $messages->get($msgKey)
                    );
            }
            return response()->json(
                array(
                    'fieldErrors' => $msgError
                )
            );
        } else {

            // condition the data Obj or string
            if (gettype($model) != 'object' & gettype($model) != 'string' )
                return array(
                    'error' => 'System Error: var passed to DT Editor is not a string or an object'
                );

            if (gettype($model) != "string" ) {

                $className = get_class($model);
                $classNameArray = explode("\\", $className);
                //search for which object it is
                if (in_array('Eloquent', $classNameArray) & in_array('Builder', $classNameArray)) {
                    // Model is Builder
                    $model = $model->get()[0];
                }
                else if (!in_array('Eloquent', $classNameArray) | !in_array('Collection', $classNameArray)) {
                    // is NOT an Elequent Collection
                    return array(
                        'error' => 'System Error: var passed to DT Editor is not an Eloquent Builder or Collection'
                    );
                    // Model is Eloquent

                }
            }

            else {
                // It's a string!
                $model = $model::all();
            }

            // Save, update or delete in DB
            if ($input['action'] == 'create') {

                $model[0]->create($input['data'][$rowFirstId]);

            }
            if ($input['action'] == 'edit') {
                foreach( $rowIdArray as $rowId) {
                    $modelCollection = $model->find($rowId);
                    $modelCollection->update($input['data'][$rowId]);
                    // $modelCollection->save();
                }
            }
            if ($input['action'] == 'remove') {
                foreach( $rowIdArray as $rowId) {
                    $modelCollection = $model->find($rowId);
                    $modelCollection->delete();
                }
            }
            // assemble the successful data response
            $resSuccessful = [];


            //MB This assembles the response but  uses the input, so will only contain those fields
            //MB In the case of an inline edit, this may online be one field but the response need to contain all fields, or the datagrid won't refresh
            //All the new fields will be in $modelCollection['attributes'] - but this will include some we don't want to display



            $arrReturn = array(); //the final array will will jsonise and return
            $arrFieldsToReturn = array(); // fields and values which will go in the return array

            //This is the array of fields which we want to display in the grid (don't want created_at etc
            //Mine is stored in the model but could also be defined here - I can use this in lots of places, so that I can have a generic table template for multiple models
            //$arrDisplayInTable   = $modelCollection['displayInTable']; //The model stores which fields we want to display
            $arrDisplayInTable = ['id','name','email'];


            foreach($arrDisplayInTable as $returnField){//go through the fields we need to return
                //add this field to the return array, from the $modelCollection['attributes']
                $arrFieldsToReturn[$returnField] = $modelCollection['attributes'][$returnField];
            }
            //now this needs to be added to the return array, with the row id as a key
            $arrReturn[] = array('DT_RowId' => 'row_' .$rowId) + $arrFieldsToReturn ;



/*            foreach( $rowIdArray as $rowId) {
                $resSuccessful[] = array('DT_RowId' => 'row_' .$rowId) + $input['data'][$rowId] ;
            }*/

            return response()->json(
                array(
                    'data' =>  $arrReturn //$resSuccessful
                 )
            );
        }
    }
}

