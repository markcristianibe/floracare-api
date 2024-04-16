<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Device;
use App\Models\PlantDiagnose;
use App\Models\ReadingLog;
use App\Models\Reminder;
use App\Models\User;
use App\Models\UserPlant;
use App\Models\UserPlantActivity;
use PhpParser\Node\Expr\Cast\Array_;

class APIController extends Controller
{
    function iot_send_data(Request $request){
        $device_id = $request->serial;

        $device = Device::where('serial_no', '=', $device_id)->first();

        if($device->plant_id != ''){
            Device::where('serial_no', '=', $device_id)
            ->where('status', '!=', 'idle')
            ->update([
                'light_intensity' => $request->lightIntensity,
                'temperature' => $request->temperature,
                'humidity' => $request->humidity,
                'battery_percentage' => $request->batteryLevel,
                'soil_fertility' => $request->soilEC,
                'nitrogen' => $request->nitrogen,
                'phosphorus' => $request->phosporus,
                'potassium' => $request->potassium,
                'status' => 'online'
            ]);

            if($request->soilMoisture <= 100){
                Device::where('serial_no', '=', $device_id)
                ->where('status', '!=', 'idle')
                ->update([
                    'soil_moisture' => $request->soilMoisture
                ]);
            }
            if($request->soilPH <= 16){
                Device::where('serial_no', '=', $device_id)
                ->where('status', '!=', 'idle')
                ->update([
                    'soil_ph' => $request->soilPH
                ]);
            }
        }

        $device = Device::where('serial_no', '=', $request->serial)->first();

        $readingLog = new ReadingLog;
        $readingLog -> serial_no = $request->serial;
        $readingLog -> plant_id = $device->plant_id;
        $readingLog -> light_intensity = $request->lightIntensity;
        $readingLog -> temperature = $request->temperature;
        $readingLog -> humidity = $request->humidity;
        $readingLog -> soil_moisture = $request->soilMoisture;
        $readingLog -> soil_fertility = $request->soilEC;
        $readingLog -> soil_ph = $request->soilPH;
        $readingLog -> nitrogen = $request->nitrogen;
        $readingLog -> phosphorus = $request->phosporus;
        $readingLog -> potassium = $request->potassium;
        $readingLog -> save();
    }

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
            ->where('status', '=', 'active')
            ->select('id', 'email', 'password')
            ->first();
        if($user && Hash::check($request->password, $user->password)){
            $response = ['user' => $user];
            return response()->json($response, 200);
        }

        $response = ['message' => 'Incorrect email or password'];
        return response()->json($response, 400);
    }

    public function user_registration(Request $request){
        validator(request()->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'firstname' => ['required'],
            'lastname' => ['required']
        ])->validate();

        $user = User::where('email', '=', $request->email)->get();

        if($user->count() == 0){
            $user = new User; 
            $user->email = $request->email;
            $user->password = $request->password;
            $user->firstname = $request->firstname;
            $user->lastname = $request->lastname;
            $user->status = 'deactivated';
            $user->save();

            include("PHPMailer/mailer.php");

            $subject = "Floracare Account Verification";
            $body = "
            <html>
            <head>
            <title></title>
            </head>
            <body>
            ";
            $body .= "<h2>Welcome to Floracare!</h2>";
            $body .= "<h3>Hi ". $request->firstname .",</h3>";
            $body .= "<small>We're happy you signed up for Floracare. Before being able to use your account you need to verify that this is your email address by clicking the button below.</small><br><br><br>";
            $body .= "<a href='192.168.1.9/api/user/verify/". $request->email ."' style='padding: 10px; border-radius: 10px; border: 1px solid #d9ead3; background: #6aa84f; text-decoration: none; color: #fff;'>Verify Account</a>";
            $body .= "
            </body>
            </html>
            ";

            SendEmail($request->email, $subject, $body);

            $response = ['user' => $user];
            return response()->json($response, 200);
        }
        else{
            $response = ['message' => 'Email is already exists.'];
            return response()->json($response, 400);
        }
    }

    public function verify_account($email){
        User::where('email', '=', $email)->update(['status' => 'active']);
        return view('verify');
    }

    public function update_user_info(Request $request){
        validator(request()->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'firstname' => ['required'],
            'lastname' => ['required']
        ])->validate();
        
        $user = new User; 
        $user->email = $request->email;
        $user->password = $request->password;
        $user->firstname = $request->firstname;
        $user->lastname = $request->lastname;
        $user->status = 'deactivated';
        $user->save();

        $response = ['user' => $user];
        return response()->json($response, 200);
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

        $this->create_activity_log($userplant->id, 'Plant Added', $request->label);
        return $userplant->id;
    }

    public function get_user_plants($userId){
        $data = UserPlant::where('user_id', '=', $userId)->orderBy('updated_at', 'desc')->get();
        
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

        if($device != null){
            if($device->status != "idle"){
                $today = date_create();
                $interval = date_create($device->updated_at)->diff($today);
                $totalSeconds = $interval->s // Seconds
                        + $interval->i * 60 // Minutes converted to seconds
                        + $interval->h * 3600 // Hours converted to seconds
                        + $interval->days * 86400;

                if($totalSeconds > 7){
                    Device::where('serial_no', '=', $device->serial_no)->update(['status' => 'offline']);
                }
            }
        }

        $device = Device::where('plant_id', '=', $plant_id)->first();

        $activities = UserPlantActivity::where('plant_id', '=', $plant_id)
        ->orderBy('created_at', 'desc')->get();

        $jsonString = file_get_contents('plant-database-master/json/' . $data->plant_name . '.json');
        $plant_info = json_decode($jsonString, true);

        return  ['plant_info' => $plant_info, 'data' => $data, 'device' => $device, 'activities' => $activities];
    }

    public function get_user_plant_monitoring($plant_id){
        $data = UserPlant::where('plant_id', '=', $plant_id)->first();
        $jsonString = file_get_contents('plant-database-master/json/' . $data->plant_name . '.json');
        $plant_info = json_decode($jsonString, true);
        $device = Device::where('plant_id', '=', $plant_id)->first();

        return ['device' => $device, 'plant_info' => $plant_info];
    }

    public function get_plant_activities($plant_id){
        $activity = UserPlantActivity::where('plant_id', '=', $plant_id)->orderBy('id', 'desc')->get();
        return $activity;
    }

    public function get_plant_diagnoses($plant_id){
        $diagnosis = PlantDiagnose::where('plant_id', '=', $plant_id)->orderBy('id', 'desc')->get();
        return $diagnosis;
    }

    public function get_plant_reminders($plant_id){
        $reminder = Reminder::where('plant_id', '=', $plant_id)->orderBy('id', 'desc')->get();
        return $reminder;
    }

    public function get_user_reminders($user_id){
        $userPlants = UserPlant::where('user_id', '=', $user_id)
        ->with(['reminders' => function($query) {
            $query->where('status', '!=', 'done');
        }])->get();
        return $userPlants;
    }

    public function get_user_devices($user_id){
        $paired_devices = Device::where('user_id', '=', $user_id)
        ->where('status', '!=', 'idle')
        ->where('status', '!=', 'disconnected')
        ->get();

        $available_devices = Device::where('user_id', '=', $user_id)
        ->where('status', '=', 'idle')
        ->get();

        return ["paired_devices" => $paired_devices, "available_devices" => $available_devices];
    }

    public function create_diagnosis(Request $request){
        $diagnosis = new PlantDiagnose;
        $diagnosis -> user_id = $request->user_id;
        $diagnosis -> plant_id = $request->plant_id;
        $diagnosis -> data = $request->data;
        $diagnosis -> is_user_plant = 1;
        $diagnosis -> save();

        $this->create_activity_log($request->plant_id, 'Plant Health Assessment', $diagnosis->id);
        $response = ['message' => $request->userId];
        return response()->json($response, 200);
    }

    public function pair_user_devices(Request $request){
        Device::where('plant_id', '=', $request->plant_id)
        ->update(['status' => 'idle', 'plant_id' => '']);

        Device::where('serial_no', '=', $request->device_id)
        ->update(['status' => 'offline', 'plant_id' => $request->plant_id]);

        $this->create_activity_log($request->plant_id, 'Paired Device to Plant', $request->device_id);
    }

    public function unpair_user_devices(Request $request){
        $device = Device::where('serial_no', '=', $request->device_id)->first();
        $plant_id = $device->plant_id;

        Device::where('serial_no', '=', $request->device_id)
        ->update(['status' => 'idle', 'plant_id' => '']);

        $this->create_activity_log($plant_id, 'Unpaired Device to Plant', $request->device_id);
        return $plant_id . ' - ' . $request->device_id;
    }

    public function get_plant_device(Request $request){
        $device = Device::where('plant_id', '=', $request->plant_id)->first();
        if($device != null){
            if($device->status != "idle"){
                $today = date_create();
                $interval = date_create($device->updated_at)->diff($today);
                $totalSeconds = $interval->s // Seconds
                        + $interval->i * 60 // Minutes converted to seconds
                        + $interval->h * 3600 // Hours converted to seconds
                        + $interval->days * 86400;

                if($totalSeconds > 7){
                    Device::where('serial_no', '=', $device->serial_no)->update(['status' => 'offline']);
                }
            }
        }

        $data = UserPlant::where('plant_id', '=', $request->plant_id)->first();
        $jsonString = file_get_contents('plant-database-master/json/' . $data->plant_name . '.json');
        $plant_info = json_decode($jsonString, true);
        $device = Device::where('plant_id', '=', $request->plant_id)->get();
        return ['device' => $device, 'plant_info' => $plant_info];
    }

    public function rename_device(Request $request){
        $device = Device::where('serial_no', '=', $request->device_id)
        ->update(['device_name' => $request->name]);
    }

    public function connect_device(Request $request){
        $device_id = $request->device_id;

        $device = Device::where('serial_no', '=', $device_id)->get();

        if($device[0]->status != "disconnected"){
            return 'device is paired';
        }
        else{
            Device::where('serial_no', '=', $device_id)->update(["status" => "idle"]);
            return 'device connected';
        }
    }

    public function disconnect_device(Request $request){
        $device_id = $request->device_id;

        $device = Device::where('serial_no', '=', $device_id)->get();

        if($device[0]->plant_id != ''){
            return 'device is paired';
        }
        else{
            Device::where('serial_no', '=', $device_id)->update(["status" => "disconnected"]);
            return 'device deleted';
        }
    }

    public function create_reminder(Request $request){
        $time = date_format(date_create($request->time), "H:i:s");

        $reminder = new Reminder();
        $reminder -> plant_id = $request->plant_id;
        $reminder -> activity = $request->activity;
        $reminder -> scheduled_at = $request->date;
        $reminder -> time = $time;
        $reminder -> status = "pending";
        $reminder -> save();

        return $reminder;
    }

    public function get_plant_reminder_info(Request $request){
        return Reminder::where('id', '=', $request->id)->first();
    }

    public function complete_plant_reminder(Request $request){
        Reminder::where('id', '=', $request->id)->update(['status' => 'done']);
        $reminder = Reminder::where('id', '=', $request->id)->first();
        $this->create_activity_log($reminder->plant_id, $reminder->activity . " (Done)", "Completed");
        return $reminder;
    }

    public function delete_plant_reminder(Request $request){
        Reminder::where('id', '=', $request->id)->delete();
    }

    public function get_user_info($user_id){
        $user = User::where('id', '=', $user_id)->first();
        return $user;
    }

    public function get_plant_scores(Request $request){
        $today = date_create();
        $date = date_format($today, "Y-m-d H") . "%";
        $data = array();
        for($i = 0; $i < 12; $i++){
            $logs = ReadingLog::where('plant_id', '=', $request->plant_id)
            ->where('created_at', 'like', $date)
            ->avg($request->parameter);
            array_push($data, $logs);

            date_sub($today, date_interval_create_from_date_string('1 hour'));  
            $date = date_format($today, "Y-m-d H") . "%";
        }
        return $data;
    }   
}
