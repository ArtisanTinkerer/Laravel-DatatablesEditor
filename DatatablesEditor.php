<?php
namespace App\Helpers;
/**
 * Created by PhpStorm.
 * User: John Kirkpatrick
 * Date: 9/1/2015
 * Time: 12:00 AM
 *
 * Mick Byrne amended.
 * Now works with inline editing - but only for models
 * 26/11/2015
 */



use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\User;


class DatatablesEditor
{

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
    public static function process(Request $req, $validator, $model)
    {
        $input = $req->input();

        $rowFirstId = array_keys($input['data'])[0];
        $rowIdArray = array_keys($input['data']);

        $data = null;

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
            if (gettype($model) != 'object' & gettype($model) != 'string')
                return array(
                    'error' => 'System Error: var passed to DT Editor is not a string or an object'
                );
            //if its a model
            if (gettype($model) != "string") {

                $className = get_class($model);
                $classNameArray = explode("\\", $className);
                //search for which object it is
                if (in_array('Eloquent', $classNameArray) & in_array('Builder', $classNameArray)) {
                    // Model is Builder
                    $model = $model->get()[0];
                } else if (!in_array('Eloquent', $classNameArray) | !in_array('Collection', $classNameArray)) {
                    // is NOT an Elequent Collection
                    return array(
                        'error' => 'System Error: var passed to DT Editor is not an Eloquent Builder or Collection'
                    );
                    // Model is Eloquent

                }
            } else {
                // It's a string!
                $model = $model::all();
            }


            //the model we are modifying/updating is returned after doing the DB action
            $modelCollection = self::doDBAction($input,$model,$rowIdArray);

            //return the Ajax response.
           return  self::sendResponse($rowIdArray,$model,$modelCollection);

        }
    }


    /**
     * @param $input - from the POST
     * @param $model - the model we are using
     * @param $rowIdArray - can be one or multiple row ids
     *
     */
    private static function doDBAction($input,$model,$rowIdArray)
    {

        switch ($input['action']) {
            case 'create':

                $rowIdArray = array();
                //MB 25/11/2015 - we still need all the attributes, so that we can return the new row to the Datatable
                $modelCollection = User::create($input['data'][0]);
                $rowIdArray[] = $modelCollection['attributes']['id'];

                break;
            case 'edit':

                foreach ($rowIdArray as $rowId) {
                    $modelCollection = $model->find($rowId);
                    $modelCollection->update($input['data'][$rowId]);
                }

                break;
            case 'remove':

                foreach ($rowIdArray as $rowId) {
                    $modelCollection = $model->find($rowId);
                    $modelCollection->delete();
                }

                break;

        }
        return  $modelCollection;
    }

    /**
     * @param $rowIdArray - can be one or many, if we do a multiselect
     * @param $model
     *
     * @return \Illuminate\Http\JsonResponse
     */


Private static function sendResponse($rowIdArray,$model,$modelCollection){
        //MB This assembles the response but previously used the input, so would only contain those fields
        //In the case of an inline edit, this may only be one field but the response needs to contain all fields, or the Datagrid won't refresh
        //All the new fields will be in $modelCollection['attributes'] - but this will include some we don't want to display


        $arrReturn = array(); //the final array will will jsonise and return
        $arrFieldsToReturn = array(); // fields and values which will go in the return array

        //This is the array of fields which we want to display in the grid (don't want created_at etc).
        //Mine is stored in the model but could also be defined here - I can use this in lots of places, so that I can have a generic table template for multiple models
        $arrDisplayInTable   = $model['displayInTable'];


        foreach( $rowIdArray as $rowId) { //iterate through the rows - will just be one, unless multiline edit
            if ($rowId != 0) {//the rowId will be 0 if this a new record
                $modelCollection = $model->find($rowId);//find the model which matches this rowId
            }
            foreach ($arrDisplayInTable as $returnField) {//go through the fields we need to return
                //add this field to the return array, from the $modelCollection['attributes']

                $arrFieldsToReturn[$returnField] = $modelCollection['attributes'][$returnField];
            }

            //now this needs to be added to the return array.
            $arrReturn[] = array('DT_RowId' => 'row_' .$rowId) + $arrFieldsToReturn ;
        }



        return response()->json(
            array(
                'data' =>  $arrReturn //$resSuccessful
            )
        );
    }

}

