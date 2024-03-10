<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Device;
use App\Models\UserPlant;
use App\Models\UserPlantActivity;

class APIController extends Controller
{
    function create_activity_log($plant_id, $title, $remarks){
        $userplant_activity = new UserPlantActivity;
        $userplant_activity -> plant_id = $plant_id;
        $userplant_activity -> title = $title;
        $userplant_activity -> remarks = $remarks;
        $userplant_activity -> save();
    }

    public function user_auth(Request $request){
        validator(request()->all(), [
            'email' => ['required', 'email'],
            'password' => ['required']
        ])->validate();

        $user = DB::table('users')
            ->where('email', '=', $request->email)
            ->select('id', 'email', 'password')
            ->first();
        if($user && Hash::check($request->password, $user->password)){
            $response = ['user' => $user];
            return response()->json($response, 200);
        }

        $response = ['message' => 'Incorrect email or password'];
        return response()->json($response, 400);
    }

    public function get_plant_info(Request $request){
        if(!is_null($request->apiKey) && !is_null($request->q)){
            $apiKey = $request->apiKey;
            $plant = $request->q;
            $url = "https://trefle.io/api/v1/plants/search?q=" . urlencode($plant) . "&token=" . $apiKey;

            $result = file_get_contents($url);
            $data = json_decode($result);
            $common_name = "";
            if($data->data){
                if($data->data[0]->common_name){
                    $common_name = $data->data[0]->common_name;
                }
            }
            else{
                $common_name = $plant;
            }

            $plant_info = array(); 

            $databaseDir = "plant-database-master/json/";
            $plants = scandir($databaseDir);

            for($i = 2; $i < count($plants); $i++){
                try{
                    $jsonString = file_get_contents($databaseDir . "$plants[$i]");
                    $jsonData = json_decode($jsonString, true);
                    if($jsonData){
                        if(ucwords($jsonData["pid"]) == ucwords($plant)){
                            $plant_info =  $jsonData;
                            break;
                        }
                    }
                }
                catch(Exception){
                    return 'error';
                }
            }

            if($common_name != ""){
                $plant_info['common_name'] = $common_name;
            }
            else{
                $plant_info['common_name'] = $plant_info['display_pid'];
            }

            
            if(array_key_exists('pid', $plant_info)){
                return $plant_info;
            }
            else{
                return  [
                    'title' => 'Plant Not Found.',
                    'content' => 'Sorry, ' . $plant_info['common_name'] . ' is not yet registered on our database.',
                    'note' => 'You can create a customized plant care for unidentified species.',
                    'redirect' => '/scan',
                    'action' => 'Okay'
                ];
            }
        }
    }

    public function add_user_plant(Request $request){
        $userplant = new UserPlant;
        $userplant -> user_id = $request->userId;
        $userplant -> plant_name = $request->plantId;
        $userplant -> label = $request->label;
        $userplant -> save();

        $this->create_activity_log($request->userId, 'Plant Added', $request->label);
        $response = ['message' => $request->userId];
        return response()->json($response, 200);
    }

    public function get_user_plants(Request $request){
        $data = UserPlant::where('user_id', $request->userId)->orderBy('updated_at', 'desc')->get();
        
        $response = array();
        for($i = 0; $i < count($data); $i++){
            $jsonString = file_get_contents('plant-database-master/json/' . $data[$i]->plant_name . '.json');
            $plant_info = json_decode($jsonString, true);

            array_push(
                $response,
                array(
                    "plant_id" => $data[$i]->plant_id,
                    "user_id" => $data[$i]->user_id,
                    "plant_name" => $data[$i]->plant_name,
                    "label" => $data[$i]->label,
                    "created_at" => date_format(date_create($data[$i]->created_at), "F d, Y"),
                    "category" => $plant_info["basic"]["category"],
                    "image" => $plant_info["image"],
                    "soil_moisture" => $plant_info["parameter"]["min_soil_moist"] . ' - ' . $plant_info["parameter"]["max_soil_moist"] . '%',
                    "sunlight" => number_format($plant_info["parameter"]["min_light_lux"]) . ' - ' . number_format($plant_info["parameter"]["max_light_lux"]) . 'lx',
                    "humidity" => $plant_info["parameter"]["min_env_humid"] . ' - ' . $plant_info["parameter"]["max_env_humid"] . '%',
                    "temperature" => $plant_info["parameter"]["min_temp"] . ' - ' . $plant_info["parameter"]["max_temp"] . '°C'
                )
            );
        }

        return $response;
    }

    public function remove_user_plant(Request $request){
        UserPlant::where('plant_id', '=', $request->plantId)->delete();
        
        Device::where('plant_id', '=', $request->plantId)
                ->update(['plant_id' => '', 'status' => 'idle']);
        
        $response = ['message' => "Plant removed successfully!"];
        return response()->json($response, 200);
    }

    public function get_user_plant_info($plant_id){
        $data = UserPlant::where('plant_id', '=', $plant_id)->first();

        $device = Device::where('plant_id', '=', $plant_id)->first();

        $jsonString = file_get_contents('plant-database-master/json/' . $data->plant_name . '.json');
        $plant_info = json_decode($jsonString, true);

        return  ['plant_info' => $plant_info, 'data' => $data, 'device' => $device];
    }
}