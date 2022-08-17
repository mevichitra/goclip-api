<?php

App::uses('Utility', 'Lib');
App::uses('Extended', 'Lib');
App::uses('Regular', 'Lib');
App::uses('Message', 'Lib');
App::uses('CustomEmail', 'Lib');
class ApiController extends AppController
{

    //public $components = array('Email');

    public $autoRender = false;
    public $layout = false;


    public function beforeFilter()
    {

        if ($this->request->isPost()) {

            return true;
            $this->loadModel('User');
            $json = file_get_contents('php://input');
            $json_error = Utility::isJsonError($json);


            if (!function_exists('apache_request_headers')) {
                $headers = Utility::apache_request_headers();
            } else {
                $headers = apache_request_headers();
            }





            $user_id_header = 0;
            $device = "";
            $version = "";
            if (array_key_exists("User-Id", $headers)) {
                $user_id_header = $headers['User-Id'];

            } else if (array_key_exists("USER-ID", $headers)) {

                $user_id_header = $headers['USER-ID'];
            }


            if (array_key_exists("Auth-Token", $headers)) {
                $auth_token = $headers['Auth-Token'];

            } else if (array_key_exists("AUTH-TOKEN", $headers)) {

                $auth_token = $headers['AUTH-TOKEN'];
            }


            $client_api_key = 0;
            if (array_key_exists("Api-Key", $headers)) {
                $client_api_key = $headers['Api-Key'];

            } else if (array_key_exists("API-KEY", $headers)) {

                $client_api_key = $headers['API-KEY'];
            } else if (array_key_exists("api-key", $headers)) {

                $client_api_key = $headers['api-key'];
            }


            if ($client_api_key > 0) {


                if ($client_api_key != API_KEY) {

                    Message::ACCESSRESTRICTED();
                    die();

                }
            } else {
                $output['code'] = 201;
                $output['msg'] = "API KEY is missing";

                echo json_encode($output);
                die();

            }

            if ($user_id_header > 0) {


                $userDetails_header = $this->User->getUserDetailsFromID($user_id_header);

                if (count($userDetails_header) > 0) {


                    $social = $userDetails_header['User']['social'];
                    $db_auth_token = $userDetails_header['User']['auth_token'];
                    $active = $userDetails_header['User']['active'];


                    if ($active > 1) {


                        $output['code'] = 501;
                        $output['msg'] = "You have been blocked by the admin. Contact support";
                        echo json_encode($output);
                        die();

                    }

                    $device_token = 0;
                    $ip = 0;
                    if (array_key_exists("Device-Token", $headers)) {
                        $device_token = $headers['Device-Token'];

                    } else if (array_key_exists("DEVICE-TOKEN", $headers)) {

                        $device_token = $headers['DEVICE-TOKEN'];
                    } else if (array_key_exists("device-token", $headers)) {

                        $device_token = $headers['device-token'];
                    }

                    if (array_key_exists("IP", $headers)) {
                        $ip = $headers['IP'];

                    } else if (array_key_exists("ip", $headers)) {

                        $ip = $headers['ip'];
                    }else if (array_key_exists("Ip", $headers)) {

                        $ip = $headers['Ip'];
                    }


                    if (array_key_exists("DEVICE", $headers)) {
                        $device = $headers['DEVICE'];

                    } else if (array_key_exists("device", $headers)) {

                        $device = $headers['device'];
                    }else if (array_key_exists("Device", $headers)) {

                        $device = $headers['Device'];
                    }


                    if (array_key_exists("VERSION", $headers)) {
                        $version = $headers['VERSION'];

                    } else if (array_key_exists("version", $headers)) {

                        $version = $headers['version'];

                    }else if (array_key_exists("Version", $headers)) {

                        $version = $headers['Version'];
                    }




                    if(strlen($device_token) > 5 && strlen($ip) > 2) {




                        $this->addPhoneDeviceData($device_token, $ip, $device, $version,$user_id_header);

                    }
                    if ($social == "facebook") {

                        $verify = Utility::getFacebookUserInfo($auth_token);
                        if ($verify) {

                            return true;
                        } else {

                            return true;

                            $output['code'] = 501;
                            $output['msg'] = "invalid facebook token";

                            echo json_encode($output);
                            die();
                        }

                    } else if ($social == "google") {

                        $verify = Utility::getGoogleUserInfo($auth_token);
                        if ($verify) {

                            return true;


                        } else {
                            return true;
                            $output['code'] = 501;
                            $output['msg'] = "invalid google token";

                            echo json_encode($output);
                            die();

                        }

                    } else if (strlen($social) < 2) {


                        if ($db_auth_token == $auth_token) {

                            return true;

                        } else {

                            return true;

                            $output['code'] = 501;
                            $output['msg'] = "invalid application token";

                            echo json_encode($output);
                            die();
                        }
                    }


                }

            }

            if ($json_error == "false") {



                return true;


            } else {
                $privacy_type = $this->request->data('privacy_type');
                if (strlen($privacy_type) > 0) {


                }
                return true;
                $output['code'] = 202;
                $output['msg'] = $json_error;

                echo json_encode($output);
                die();


            }
        }
    }







    public function index(){


        echo "Congratulations!. You have configured your mobile api correctly";

    }


    public function registerUser()
    {


        $this->loadModel('User');
        $this->loadModel('PushNotification');
        $this->loadModel('PrivacySetting');
        $this->loadModel('VerificationRequest');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $user['created'] = date('Y-m-d H:i:s', time());




                $user['role'] = "user";





            if(isset($data['first_name'])){

                $first_name = $data['first_name'];
                $last_name = $data['last_name'];


                $user['first_name'] = $first_name;


                $user['last_name'] = $last_name;
            }

            if(isset($data['username'])){

                $username = $data['username'];

                if(preg_match('/[^a-z_\-0-9]/i', $username)){
                    $output['code'] = 201;
                    $output['msg'] = "invalid username";
                    echo json_encode($output);
                    die();

                }



                $user['username'] = $username;



            }


            if(isset($data['social']) && !isset($data['dob'])){
                $social_id = $data['social_id'];
                $auth_token = $data['auth_token'];
                $social = $data['social'];
                $user_details = $this->User->isSocialIDAlreadyExist($social_id);
                if(count($user_details) > 0) {
                    $verification_details = $this->VerificationRequest->getVerificationDetailsAgainstUserID($user_details['User']['id']);

                    if (count($verification_details) > 0) {

                        $user_details['User']['verification_applied'] = 1;
                    }else{

                        $user_details['User']['verification_applied'] = 0;

                    }
                }
                if(count($user_details) > 0 ){

                    $active = $user_details['User']['active'];

                    if($active > 1){


                        $output['code'] = 201;
                        $output['msg'] = "You have been blocked by the admin. Contact support";
                        echo json_encode($output);
                        die();

                    }

                    if($social == "facebook"){

                        $verify = Utility::getFacebookUserInfo($auth_token);
                        $verify = true;
                        if($verify){

                            //$this->User->id = $user_details['User']['id'];
                            //$this->User->saveField('auth_token',$auth_token);

                            $output['code'] = 200;
                            $output['msg'] = $user_details;
                            echo json_encode($output);
                            die();

                        }else{

                            $output['code'] = 201;
                            $output['msg'] = "token invalid";
                            echo json_encode($output);
                            die();


                        }

                    }

                    if($social == "google"){

                        $verify = Utility::getGoogleUserInfo($auth_token);
                        $verify = true;
                        if($verify){

                            // $this->User->id = $user_details['User']['id'];
                            // $this->User->saveField('auth_token',$auth_token);

                            $output['code'] = 200;
                            $output['msg'] = $user_details;
                            echo json_encode($output);
                            die();

                        }else{
                            return true;

                            $output['code'] = 201;
                            $output['msg'] = "token invalid";
                            echo json_encode($output);
                            die();


                        }

                    }


                    if (isset($data['profile_pic'])) {


                        $user['profile_pic'] = $data['profile_pic'];
                    }
                    $output['code'] = 200;
                    $output['msg'] = $user_details;
                    echo json_encode($output);
                    die();

                }else{


                    $output['code'] = 201;
                    $output['msg'] = "open registration screen";
                    echo json_encode($output);
                    die();

                }
            }


            if(isset($data['social']) && isset($data['dob'])){
                $social = $data['social'];
                $auth_token = $data['auth_token'];
                $user['social_id'] = $data['social_id'];
                $user['social'] = $social;
                $user['dob'] = $data['dob'];

                if(isset($data['gender'])){

                    $user['gender'] = $data['gender'];
                }

                if (isset($data['profile_pic'])) {


                    $user['profile_pic'] = $data['profile_pic'];
                }
                $user['email'] = $data['email'];
                $username_count = $this->User->isUsernameAlreadyExist($username);
                if($username_count > 0){

                    $output['code'] = 201;
                    $output['msg'] = "This username isn't available";
                    echo json_encode($output);
                    die();
                }




                if($social == "facebook") {

                    $verify = Utility::getFacebookUserInfo($auth_token);
                    $verify = true;
                    if (!$verify) {


                        $output['code'] = 201;
                        $output['msg'] = "invalid token";
                        echo json_encode($output);
                        die();

                    }
                }

                if($social == "google") {

                    $verify = Utility::getGoogleUserInfo($auth_token);
                    $verify = true;
                    if (!$verify) {


                        $output['code'] = 201;
                        $output['msg'] = "invalid token";
                        echo json_encode($output);
                        die();

                    }
                }

                $this->User->save($user);
                $user_id = $this->User->getInsertID();


                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);
                if(count($userDetails) > 0) {
                    $verification_details = $this->VerificationRequest->getVerificationDetailsAgainstUserID($user_id);

                    if (count($verification_details) > 0) {

                        $userDetails['User']['verification_applied'] = 1;
                    }else{

                        $userDetails['User']['verification_applied'] = 0;

                    }
                }
                $notification['likes'] = 1;
                $notification['comments'] = 1;
                $notification['new_followers'] = 1;
                $notification['mentions'] = 1;
                $notification['direct_messages'] = 1;
                $notification['video_updates'] = 1;
                $notification['id'] = $user_id;

                $this->PushNotification->save($notification);


                $settings['videos_download'] = 1;
                $settings['videos_repost'] = 1;
                $settings['direct_message'] = "everyone";
                $settings['duet'] = "everyone";
                $settings['liked_videos'] = "me";
                $settings['video_comment'] = "everyone";
                $settings['id'] = $user_id;

                $this->PrivacySetting->save($settings);
                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
                die();

            }

            if(!isset($data['social']) && isset($data['email'])){

                $session_token = Utility::generateSessionToken();
                $user['dob'] = $data['dob'];
                $user['auth_token'] = $session_token;
                $user['username'] = $username;
                $user['password'] = $data['password'];
                $user['email'] = $data['email'];

                if(isset($data['gender'])){

                    $user['gender'] = $data['gender'];
                }

                if (isset($data['profile_pic'])) {


                    $image = $data['profile_pic'];
                    $folder_url = UPLOADS_FOLDER_URI;

                    $filePath = Utility::uploadFileintoFolder(1, $image, $folder_url);
                    $user['profile_pic'] = $filePath;
                }


                $email_count = $this->User->isEmailAlreadyExist($data['email']);
                if($email_count > 0){

                    $user_details  = $this->User->getUserDetailsAgainstEmail($data['email']);
                    $active = $user_details['User']['active'];

                    if($active > 1){


                        $output['code'] = 201;
                        $output['msg'] = "You have been blocked by the admin. Contact support";
                        echo json_encode($output);
                        die();

                    }

                    $output['code'] = 201;
                    $output['msg'] = "The account already exist with this email";
                    echo json_encode($output);
                    die();
                }
                $username_count = $this->User->isUsernameAlreadyExist($data['username']);
                if($username_count > 0){

                    $user_details  = $this->User->getUserDetailsAgainstUsername($data['username']);
                    $active = $user_details['User']['active'];

                    if($active > 1){


                        $output['code'] = 201;
                        $output['msg'] = "You have been blocked by the admin. Contact support";
                        echo json_encode($output);
                        die();

                    }

                    $output['code'] = 201;
                    $output['msg'] = "This username isn't available";
                    echo json_encode($output);
                    die();
                }






                $this->User->save($user);
                $user_id = $this->User->getInsertID();


                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);
                if(count($userDetails) > 0) {
                    $verification_details = $this->VerificationRequest->getVerificationDetailsAgainstUserID($user_id);

                    if (count($verification_details) > 0) {

                        $userDetails['User']['verification_applied'] = 1;
                    }else{

                        $userDetails['User']['verification_applied'] = 0;

                    }
                }
                $notification['likes'] = 1;
                $notification['comments'] = 1;
                $notification['new_followers'] = 1;
                $notification['mentions'] = 1;
                $notification['direct_messages'] = 1;
                $notification['video_updates'] = 1;
                $notification['id'] = $user_id;

                $this->PushNotification->save($notification);

                $settings['videos_download'] = 1;
                $settings['videos_repost'] = 1;
                $settings['direct_message'] = "everyone";
                $settings['duet'] = "everyone";
                $settings['liked_videos'] = "me";
                $settings['video_comment'] = "everyone";
                $settings['id'] = $user_id;

                $this->PrivacySetting->save($settings);
                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
                die();





            }


            if(isset($data['phone']) && !isset($data['dob'])) {
                //login

                $user['phone'] = $data['phone'];


                $phone_exist = $this->User->isphoneNoAlreadyExist($data['phone']);

                if (count($phone_exist) > 0) {


                    $active = $phone_exist['User']['active'];

                    if($active > 1){


                        $output['code'] = 201;
                        $output['msg'] = "You have been blocked by the admin. Contact support";
                        echo json_encode($output);
                        die();

                    }

                    if (isset($data['profile_pic'])) {


                        $image = $data['profile_pic'];
                        $folder_url = UPLOADS_FOLDER_URI;

                        $filePath = Utility::uploadFileintoFolder(1, $image, $folder_url);
                        $user['profile_pic'] = $filePath;
                    }
                    $session_token = Utility::generateSessionToken();
                    $user['auth_token'] = $session_token;
                    $this->User->id = $phone_exist['User']['id'];




                    $this->User->save($user);
                    $userDetails = $this->User->getUserDetailsFromID($phone_exist['User']['id']);


                    if(count($userDetails) > 0) {
                        $verification_details = $this->VerificationRequest->getVerificationDetailsAgainstUserID(($userDetails['User']['id']));

                        if (count($verification_details) > 0) {

                            $userDetails['User']['verification_applied'] = 1;
                        }else{

                            $userDetails['User']['verification_applied'] = 0;

                        }
                    }
                    $output['code'] = 200;
                    $output['msg'] = $userDetails;
                    echo json_encode($output);
                    die();
                } else {

                    $output['code'] = 201;
                    $output['msg'] = "open register screen";
                    echo json_encode($output);
                    die();

                }

            }else  if(isset($data['phone']) && isset($data['dob'])){

                //register
                $session_token = Utility::generateSessionToken();
                $user['phone'] = $data['phone'];
                $user['auth_token'] = $session_token;

                $user['username'] = $username;
                $user['dob'] = $data['dob'];

                if(isset($data['gender'])){

                    $user['gender'] = $data['gender'];
                }

                if(isset($data['first_name'])){

                    $user['first_name'] = $data['first_name'];
                    $user['last_name'] = $data['last_name'];
                }

                $username_count = $this->User->isUsernameAlreadyExist($data['username']);
                if($username_count > 0){

                    $output['code'] = 201;
                    $output['msg'] = "This username isn't available";
                    echo json_encode($output);
                    die();
                }
                if (isset($data['profile_pic'])) {


                    $image = $data['profile_pic'];
                    $folder_url = UPLOADS_FOLDER_URI;

                    $filePath = Utility::uploadFileintoFolder(1, $image, $folder_url);
                    $user['profile_pic'] = $filePath;
                }
                $this->User->save($user);
                $user_id = $this->User->getInsertID();


                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);

                if(count($userDetails) > 0) {
                    $verification_details = $this->VerificationRequest->getVerificationDetailsAgainstUserID(($userDetails['User']['id']));

                    if (count($verification_details) > 0) {

                        $userDetails['User']['verification_applied'] = 1;
                    }
                }
                $notification['likes'] = 1;
                $notification['comments'] = 1;
                $notification['new_followers'] = 1;
                $notification['mentions'] = 1;
                $notification['direct_messages'] = 1;
                $notification['video_updates'] = 1;
                $notification['id'] = $user_id;
                $settings['videos_download'] = 1;
                $settings['videos_repost'] = 1;
                $settings['direct_message'] = "everyone";
                $settings['duet'] = "everyone";
                $settings['liked_videos'] = "me";
                $settings['video_comment'] = "everyone";
                $settings['id'] = $user_id;

                $this->PrivacySetting->save($settings);
                $this->PushNotification->save($notification);
                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
                die();


            }











        }
    }






    public function login()
    {
        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');

            $data = json_decode($json, TRUE);


            $password = $data['password'];







            if (isset($data['email'])) {

                $email = strtolower($data['email']);
                $userData = $this->User->verify($email, $password,"user");


                if(count($userData) < 0){

                    $userData = $this->User->verifyWithUsername($email, $password,"user");

                }
            }


            if (($userData)) {


                $user_id = $userData[0]['User']['id'];
                $active = $userData[0]['User']['active'];

                if($active > 1){

                    $output['code'] = 201;
                    $output['msg'] = "You have been blocked by the admin. Contact support";
                    echo json_encode($output);
                    die();
                }

                $session_token = Utility::generateSessionToken();
                $this->User->id = $user_id;
                $this->User->saveField('auth_token',$session_token);
                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);

                //CustomEmail::welcomeStudentEmail($email);
                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
                die();


            } else {
                echo Message::INVALIDDETAILS();
                die();

            }


        }
    }


    public function showFollowers()
    {


        $this->loadModel("Follower");
        $this->loadModel("Video");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = 0;
            if(isset($data['user_id'])){

                $user_id = $data['user_id'];
            }


            $starting_point = 0;
            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }

            $followers = $this->Follower->getUserFollowers($user_id,$starting_point);

            if (count($followers) > 0) {
                foreach ($followers as $key => $follow) {

                    $person_user_id = $follow['FollowerList']['id'];


                    $follower_details = $this->Follower->ifFollowing($user_id, $person_user_id);
                    $follower_back_details = $this->Follower->ifFollowing($person_user_id, $user_id);

                    $followers_count = $this->Follower->countFollowers($person_user_id);

                    $video_count = $this->Video->getUserVideosCount($person_user_id);
                    $followers[$key]['FollowerList']['follower_count'] = $followers_count;
                    $followers[$key]['FollowerList']['video_count'] = $video_count;
                    if (count($follower_details) > 0 && count($follower_back_details) > 0) {

                        $followers[$key]['FollowerList']['button'] = "Friends";
                    } else if (count($follower_details) > 0) {

                        $followers[$key]['FollowerList']['button'] = "Following";

                    } else if (count($follower_back_details) > 0 && $follower_details < 0) {

                        $followers[$key]['FollowerList']['button'] = "Follow Back";

                    } else {

                        $followers[$key]['FollowerList']['button'] = "Follow";

                    }

                }
            }






            if(isset($data['other_user_id'])) {
                $other_user_id = $data['other_user_id'];

                $followers = $this->Follower->getUserFollowers($other_user_id,$starting_point);


                if (count($followers) > 0) {
                    foreach ($followers as $key => $follow) {

                        $person_user_id = $follow['FollowerList']['id'];

                        if ($user_id == $person_user_id) {

                            $followers[$key]['FollowerList']['button'] = "0";

                        } else {
                            $follower_details = $this->Follower->ifFollowing($user_id, $person_user_id);
                            $follower_back_details = $this->Follower->ifFollowing($person_user_id, $user_id);

                            $followers_count = $this->Follower->countFollowers($person_user_id);

                            $video_count = $this->Video->getUserVideosCount($person_user_id);
                            $followers[$key]['FollowerList']['follower_count'] = $followers_count;
                            $followers[$key]['FollowerList']['video_count'] = $video_count;
                            if (count($follower_details) > 0 && count($follower_back_details) > 0) {

                                $followers[$key]['FollowerList']['button'] = "Friends";
                            } else if (count($follower_details) > 0) {

                                $followers[$key]['FollowerList']['button'] = "Following";

                            } else if (count($follower_back_details) > 0 && $follower_details < 0) {

                                $followers[$key]['FollowerList']['button'] = "Follow Back";

                            } else {

                                $followers[$key]['FollowerList']['button'] = "Follow";

                            }
                        }
                    }
                }
            }
            if(count($followers) > 0 ) {

                $output['code'] = 200;

                $output['msg'] = $followers;


                echo json_encode($output);


                die();
            }else{

                Message::EMPTYDATA();
                die();

            }

        }
    }
    public function deleteSound(){

        $this->loadModel('Sound');
        $this->loadModel('Video');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $sound_id = $data['sound_id'];

            $details = $this->Sound->getDetails($sound_id);
            if(count($details) > 0 ) {
                $audio_url =  $details['Sound']['audio'];
                $key = 'http';


                if (strpos($audio_url, $key) !== false) {

                    $if_method_exist = method_exists('Extended', 'deleteObjectS3');

                    if ($if_method_exist) {
                        $result1 = Extended::deleteObjectS3($audio_url);

                        if ($result1) {

                            $code = 200;
                            $msg = "deleted successfully";
                        } else {

                            $code = 201;
                            $msg = "something went wrong in deleting the file from the cdn";
                        }
                    }else{


                        $code =  201;
                        $msg = "Buy an extended license and setup S3. or delete S3 urls from database";
                    }
                }else{
                    Utility::unlinkFile($audio_url);


                    $code =  200;
                    $msg = "successfully deleted";


                }
                $this->Sound->delete($sound_id);

                $all_videos = $this->Video->getAllVideosAgainstSoundID($sound_id);

                if(count($all_videos) > 0) {
                    foreach ($all_videos as $key => $val) {

                        $video_ids[$key] = $val['Video']['id'];

                    }
                    $this->Video->updateSoundIDs($video_ids);

                }


                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }

    public function showFollowing()
    {


        $this->loadModel("Follower");
        $this->loadModel("Video");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = 0;
            if(isset($data['user_id'])){

                $user_id = $data['user_id'];
            }
            $starting_point = 0;


            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }
            $following = $this->Follower->getUserFollowing($user_id,$starting_point);

            if (count($following) > 0) {
                foreach ($following as $key => $follow) {

                    $person_user_id = $follow['FollowingList']['id'];



                    $follower_details = $this->Follower->ifFollowing($user_id, $person_user_id);
                    $follower_back_details = $this->Follower->ifFollowing($person_user_id, $user_id);

                    $following_count = $this->Follower->countFollowing($person_user_id);

                    $video_count = $this->Video->getUserVideosCount($person_user_id);
                    $following[$key]['FollowingList']['following_count'] = $following_count;
                    $following[$key]['FollowingList']['video_count'] = $video_count;

                    if (count($follower_details) > 0 && count($follower_back_details) > 0) {

                        $following[$key]['FollowingList']['button'] = "Friends";
                    } else if (count($follower_details) > 0) {

                        $following[$key]['FollowingList']['button'] = "Following";

                    } else if (count($follower_back_details) > 0 && $follower_details < 0) {

                        $following[$key]['FollowingList']['button'] = "Follow Back";

                    } else {

                        $following[$key]['FollowingList']['button'] = "Follow";

                    }

                }
            }

            if(isset($data['other_user_id'])) {
                $other_user_id = $data['other_user_id'];

                $following = $this->Follower->getUserFollowing($other_user_id,$starting_point);



                if (count($following) > 0) {
                    foreach ($following as $key => $follow) {

                        $person_user_id = $follow['FollowingList']['id'];

                        if ($user_id == $person_user_id) {

                            $following[$key]['FollowingList']['button'] = "0";

                        } else {
                            $follower_details = $this->Follower->ifFollowing($user_id, $person_user_id);
                            $follower_back_details = $this->Follower->ifFollowing($person_user_id, $user_id);

                            $following_count = $this->Follower->countFollowing($person_user_id);

                            $video_count = $this->Video->getUserVideosCount($person_user_id);
                            $following[$key]['FollowingList']['following_count'] = $following_count;
                            $following[$key]['FollowingList']['video_count'] = $video_count;
                            if (count($follower_details) > 0 && count($follower_back_details) > 0) {

                                $following[$key]['FollowingList']['button'] = "Friends";
                            } else if (count($follower_details) > 0) {

                                $following[$key]['FollowingList']['button'] = "Following";

                            } else if (count($follower_back_details) > 0 && $follower_details < 0) {

                                $following[$key]['FollowingList']['button'] = "Follow Back";

                            } else {

                                $following[$key]['FollowingList']['button'] = "Follow";

                            }
                        }
                    }
                }
            }


            if(count($following) > 0) {
                $output['code'] = 200;

                $output['msg'] = $following;


                echo json_encode($output);


                die();

            }else{



                Message::EMPTYDATA();
                die();


            }
        }
    }




   
    public function deleteVideo(){

        $this->loadModel('Video');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $video_id = $data['video_id'];

            $details = $this->Video->getDetails($video_id);
            if(count($details) > 0 ) {


                $video_url =  $details['Video']['video'];
                $thum_url =  $details['Video']['thum'];
                $gif_url =  $details['Video']['gif'];
                $key = 'http';


                if (strpos($video_url, $key) !== false) {

                    $if_method_exist = method_exists('Extended', 'deleteObjectS3');

                    if ($if_method_exist) {
                        $result1 = Extended::deleteObjectS3($video_url);

                        $result2 = Extended::deleteObjectS3($thum_url);
                        $result3 = Extended::deleteObjectS3($gif_url);
                        if ($result1 && $result2 && $result3) {

                            $code = 200;
                            $msg = "deleted successfully";
                        } else {

                            $code = 201;
                            $msg = "something went wrong in deleting the file from the cdn";
                        }
                    }else{


                        $code =  201;
                        $msg = "Buy an extended license and setup S3. or delete S3 urls from database";
                    }
                }else{

                    unlink($video_url);
                    unlink($thum_url);
                    unlink($gif_url);
                    $code =  200;
                    $msg = "successfully deleted";


                }
            } else {

                $code =  200;
                $msg = "video has been already deleted";

            }


            $this->Video->delete($video_id,true);

            $output['code'] = $code;

            $output['msg'] = $msg;


            echo json_encode($output);


            die();

        }




    }

    public function followUser()
    {

        //$this->loadModel("FollowRequest");
        $this->loadModel("User");
        $this->loadModel("Notification");
        $this->loadModel("PushNotification");
        $this->loadModel("Follower");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $sender_id = $data['sender_id'];
            $receiver_id = $data['receiver_id'];

            $created = date('Y-m-d H:i:s', time());


            $friend['sender_id'] = $sender_id;
            $friend['receiver_id'] = $receiver_id;
            $friend['created'] = $created;

            $sender_details = $this->User->getUserDetailsFromID($sender_id);

            if(count($sender_details) < 1){

                $output['code'] = 201;
                $output['msg'] = "Login First";
                echo json_encode($output);


                die();

            }

            $follower_details = $this->Follower->ifFollowing($sender_id, $receiver_id);

            $receiver_details = $this->User->getUserDetailsFromID($receiver_id);

            if (count($follower_details) < 1) {

                $sender_details = $this->User->getUserDetailsFromID($sender_id);


                $this->Follower->save($friend);
                $output = array();
                $id = $this->Follower->getInsertID();
                $details = $this->Follower->getDetails($id);
                //$unread = $this->FollowRequest->getUnreadRequests($receiver_id);
                $msg = $sender_details['User']['username'] . ' started following you';



                if (strlen($receiver_details['User']['device_token']) > 8) {
                    $notification['to'] = $receiver_details['User']['device_token'];

                    $notification['notification']['title'] = $msg;
                    $notification['notification']['body'] = "";
                    $notification['notification']['badge'] = "1";
                    $notification['notification']['sound'] = "default";
                    $notification['notification']['icon'] = "";
                    $notification['notification']['type'] = "follow";
                    $notification['notification']['receiver_id'] = $receiver_id;
                    $notification['data']['receiver_id'] = $receiver_id;
                    $notification['data']['title'] = $msg;
                    $notification['data']['body'] = '';
                    $notification['data']['icon'] = "";
                    $notification['data']['badge'] = "1";
                    $notification['data']['sound'] = "default";
                    $notification['data']['type'] = "follow";



                    $if_exist = $this->PushNotification->getDetails($receiver_details['User']['id']);
                    if(count($if_exist) > 0) {

                        $likes = $if_exist['PushNotification']['new_followers'];
                        if($likes > 0) {
                            Utility::sendPushNotificationToMobileDevice(json_encode($notification));
                        }
                    }




                    $notification_data['sender_id'] = $sender_details['User']['id'];
                    $notification_data['receiver_id'] = $receiver_details['User']['id'];
                    $notification_data['type'] = "following";
                    $notification_data['video_id'] = 0;

                    $notification_data['string'] =  $msg;
                    $notification_data['created'] =  $created;

                    $this->Notification->save($notification_data);

                }

                $follower_details = $this->Follower->ifFollowing($sender_details['User']['id'], $receiver_details['User']['id']);
                $following_details = $this->Follower->ifFollowing($receiver_details['User']['id'], $sender_details['User']['id']);
                //$follow_request = $this->FollowRequest->checkIfDuplicate($sender_details['User']['id'], $receiver_details['User']['id']);

                if(count($follower_details) > 0 && count($following_details) > 0){

                    $receiver_details['User']['button'] = "Friends";

                } else   if(count($follower_details) > 0 && count($following_details) < 1){

                    $receiver_details['User']['button'] = "following";



                }

                $output['code'] = 200;
                $output['msg'] = $receiver_details;
                echo json_encode($output);




                die();
            }else{
                $receiver_details['User']['button'] = "follow";
                $id = $follower_details['Follower']['id'];
                $this->Follower->id = $id;
                $this->Follower->delete();
                $output['code'] = 200;
                $output['msg'] = $receiver_details;
                echo json_encode($output);


                die();
            }


        }
    }

    public function registerDevice()
    {

        $this->loadModel("Device");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $name = $data['key'];


            $created = date('Y-m-d H:i:s', time());


            $device['key'] = $name;
            $friend['created'] = $created;


            $device_details = $this->Device->ifExist($name);


            if (count($device_details) < 1) {

                $this->Device->save($device);



                $id = $this->Device->getInsertID();
                $details = $this->Device->getDetails($id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();
            }else{

                $output['code'] = 200;
                $output['msg'] = $device_details;
                echo json_encode($output);


                die();
            }


        }
    }


    public function updatePushNotificationSettings()
    {

        $this->loadModel("PushNotification");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $user_id =  $data['user_id'];

            if(isset($data['likes'])){

                $likes =  $data['likes'];
                $notification['likes'] = $likes;

            }

            if(isset($data['comments'])){

                $comments =  $data['comments'];
                $notification['comments'] = $comments;

            }

            if(isset($data['new_followers'])){

                $new_followers =  $data['new_followers'];
                $notification['new_followers'] = $new_followers;

            }

            if(isset($data['mentions'])){

                $mentions =  $data['mentions'];
                $notification['mentions'] = $mentions;

            }

            if(isset($data['direct_messages'])){

                $direct_messages =  $data['direct_messages'];
                $notification['direct_messages'] = $direct_messages;

            }

            if(isset($data['video_updates'])){

                $video_updates =  $data['video_updates'];
                $notification['video_updates'] = $video_updates;

            }


            $details = $this->PushNotification->getDetails($user_id);

            if(count($details) > 0) {


                $this->PushNotification->id = $user_id;

                $this->PushNotification->save($notification);



                $details = $this->PushNotification->getDetails($user_id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();

            }else {

                $this->PushNotification->save($notification);
                $id = $this->PushNotification->getInsertID();
                $details = $this->PushNotification->getDetails($id);



                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();

            }


        }
    }

    public function addPrivacySetting()
    {

        $this->loadModel("PrivacySetting");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $user_id =  $data['user_id'];

            if(isset($data['videos_download'])){

                $videos_download =  $data['videos_download'];
                $setting['videos_download'] = $videos_download;

            }

            if(isset($data['direct_message'])){

                $direct_message =  $data['direct_message'];
                $setting['direct_message'] = $direct_message;

            }

            if(isset($data['duet'])){

                $duet =  $data['duet'];
                $setting['duet'] = $duet;

            }

            if(isset($data['liked_videos'])){

                $liked_videos =  $data['liked_videos'];
                $setting['liked_videos'] = $liked_videos;

            }

            if(isset($data['direct_messages'])){

                $direct_messages =  $data['direct_messages'];
                $setting['direct_messages'] = $direct_messages;

            }

            if(isset($data['video_comment'])){

                $video_comment =  $data['video_comment'];
                $setting['video_comment'] = $video_comment;

            }


            $details = $this->PrivacySetting->getDetails($user_id);

            if(count($details) > 0) {


                $this->PrivacySetting->id = $user_id;

                $this->PrivacySetting->save($setting);



                $details = $this->PrivacySetting->getDetails($user_id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();

            }else {

                $this->PrivacySetting->save($setting);
                $id = $this->PrivacySetting->getInsertID();
                $details = $this->PrivacySetting->getDetails($id);



                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();

            }


        }
    }
    public function followerNotification(){

        $this->loadModel('Follower');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $not['sender_id'] = $data['sender_id'];
            $not['receiver_id'] = $data['receiver_id'];
            $not['notification'] = $data['notification'];

            $details = $this->Follower->ifFollowing($data['sender_id'],$data['receiver_id']);


            if(count($details) > 0) {


                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();
            }else{


                Message::EMPTYDATA();
                die();
            }

        }


    }




    public function showDeviceDetail(){

        $this->loadModel('Device');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $key = $data['key'];

            $details = $this->Device->ifExist($key);


            if(count($details) > 0) {


                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();
            }else{


                Message::EMPTYDATA();
                die();
            }

        }


    }

    public function showRegisteredContacts(){

        $this->loadModel('User');
        $this->loadModel('Follower');
        $this->loadModel('Video');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $user_id =  $data['user_id'];

            if(isset($data['phone_numbers'])) {
                $phone_numbers = $data['phone_numbers'];


                $i = 0;
                $new_data = array();
                if (count($phone_numbers) > 0) {

                    foreach ($phone_numbers as $key => $val) {


                        $phone = $val['phone'];

                        $phone_exist = $this->User->editIsphoneNoAlreadyExist($phone, $user_id);

                        if($phone_exist > 0){

                            $new_data[$i] = $phone;
                            $i++;
                        }

                    }


                }


                if(count($new_data) > 0) {


                    $output['code'] = 200;

                    $output['msg'] = $new_data;


                    echo json_encode($output);


                    die();
                }else{

                    Message::EMPTYDATA();
                    die();
                }
            }else if(isset($data['facebook_ids'])) {
                $facebook_ids = $data['facebook_ids'];





                if (count($facebook_ids) > 0) {

                    foreach ($facebook_ids as $key => $val) {


                        $fb_ids[$key] = $val['fb_id'];


                    }

                    if(count($fb_ids) > 0){




                        $facebook_users = $this->User->getAllFacebookUsers($fb_ids,$user_id);

                        if(count($facebook_users)){

                            foreach($facebook_users as $fb=>$fbval){



                                $person_user_id = $fbval['User']['id'];


                                $follower_details = $this->Follower->ifFollowing($user_id, $person_user_id);
                                $follower_back_details = $this->Follower->ifFollowing($person_user_id, $user_id);

                                $followers_count = $this->Follower->countFollowers($person_user_id);

                                $video_count = $this->Video->getUserVideosCount($person_user_id);
                                $facebook_users[$fb]['User']['follower_count'] = $followers_count;
                                $facebook_users[$fb]['User']['video_count'] = $video_count;
                                if (count($follower_details) > 0 && count($follower_back_details) > 0) {

                                    $facebook_users[$fb]['User']['button'] = "Friends";
                                } else if (count($follower_details) > 0) {

                                    $facebook_users[$fb]['User']['button'] = "Following";

                                } else if (count($follower_back_details) > 0 && $follower_details < 0) {

                                    $facebook_users[$fb]['User']['button'] = "Follow Back";

                                } else {

                                    $facebook_users[$fb]['User']['button'] = "Follow";

                                }

                            }


                        }

                    }
                }

                if(count($facebook_users) > 0) {


                    $output['code'] = 200;

                    $output['msg'] = $facebook_users;


                    echo json_encode($output);


                    die();
                }else{


                    Message::EMPTYDATA();
                    die();
                }
            }





        }


    }
    public function addUserLanguage()
    {

        $this->loadModel("Language");




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = $data['user_id'];
            $language = $data['language'];













            if(count($language) > 0){
                foreach ($language as $key=>$lang){



                    $language_data[$key]['lang_id'] = $lang['lang_id'];

                    $language_data[$key]['user_id'] = $user_id;
                }

                $this->Language->saveAll($language_data);


            }
            $userDetails = $this->User->getUserDetailsFromID($user_id);

            $output['code'] = 200;
            $output['msg'] = $userDetails;
            echo json_encode($output);


            die();








        }
    }

    public function addUserInterest()
    {

        $this->loadModel("UserInterest");




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = $data['user_id'];
            $interests = $data['interests'];










            $this->UserInterest->deleteAllInterests($user_id);

            $interest_data = array();
            if(count($interests) > 0){
                foreach ($interests as $key=>$interest){



                    $if_exist = $this->UserInterest->getUserInterests($user_id,$interest['interest_id']);
                    if(count($if_exist) < 1){
                        $interest_data[$key]['interest_id'] = $interest['interest_id'];

                        $interest_data[$key]['user_id'] = $user_id;
                    }
                }

                if(count($interest_data) > 0){
                    $this->UserInterest->saveAll($interest_data);
                }

            }
            $userDetails = $this->User->getUserDetailsFromID($user_id);

            $output['code'] = 200;
            $output['msg'] = $userDetails;
            echo json_encode($output);


            die();








        }
    }

    public function addUserLanguagePreferences()
    {

        $this->loadModel("LanguagePreference");




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = $data['user_id'];
            $language = $data['language'];










            $this->LanguagePreference->deleteAllPreference($user_id);


            if(count($language) > 0){


                foreach ($language as $key=>$lang){



                    //$if_exist = $this->LanguagePreference->getUserLanguagePreferences($user_id,$lang['lang_id']);


                    $lang_data[$key]['lang_id'] = $lang['lang_id'];

                    $lang_data[$key]['user_id'] = $user_id;

                }


                if(count($lang_data) > 0){

                    $this->LanguagePreference->saveAll($lang_data);
                }


            }
            $userDetails = $this->User->getUserDetailsFromID($user_id);

            $output['code'] = 200;
            $output['msg'] = $userDetails;
            echo json_encode($output);


            die();








        }
    }
    public function showLanguages(){

        $this->loadModel('Language');
        $this->loadModel('LanguagePreference');




        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];


            $details = $this->Language->getAll();




            if(count($details) > 0) {

                foreach($details as $key=>$detail){
                    $details[$key]['Language']['selected'] = 0;
                    if(isset($data['user_id'])){

                        $if_exist = $this->LanguagePreference->ifLanguageIsSelected($data['user_id'],$detail['Language']['id']);

                        if(count($if_exist) > 0){

                            $details[$key]['Language']['selected'] = 1;

                        }

                    }
                }


                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();
            }else{


                Message::EMPTYDATA();
                die();
            }

        }


    }

    public function showInterests(){

        $this->loadModel('Interest');
        $this->loadModel('UserInterest');




        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $lang_id = $data['lang_id'];


            $details = $this->Interest->getInterestsAgainstLanguage($lang_id);


            if(count($details) > 0) {


                foreach($details as $key=>$detail){
                    $details[$key]['Interest']['selected'] = 0;
                    if(isset($data['user_id'])){

                        $if_exist = $this->UserInterest->getUserInterests($data['user_id'],$detail['Interest']['id']);

                        if(count($if_exist) > 0){

                            $details[$key]['Interest']['selected'] = 1;

                        }

                    }
                }

                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();
            }else{


                Message::EMPTYDATA();
                die();
            }

        }


    }




    public function watchVideo()
    {

        $this->loadModel("VideoWatch");
        $this->loadModel("Promotion");
        $this->loadModel("Video");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_id = $data['video_id'];
            $device_id = $data['device_id'];



            $created = date('Y-m-d H:i:s', time());


            if(isset($data['user_id'])){


                $watch['user_id'] = $data['user_id'];
            }
            $watch['video_id'] = $video_id;
            $watch['device_id'] = $device_id;
            $watch['created'] = $created;

            $promotion_details = $this->Promotion->getActivePromotionAgainstVideoID($video_id,$created);


            if(count($promotion_details) > 0){

                $watch['promotion_id'] = $promotion_details['Promotion']['id'];
            }
            //  $watch_details = $this->VideoWatch->ifExist($watch);



            //if (count($watch_details) < 1) {

            $this->VideoWatch->save($watch);
            $this->Video->id = $video_id;
            $views = $this->Video->field('view');
            $this->Video->id = $video_id;
            $this->Video->savefield('view',$views + 1);






            $id = $this->VideoWatch->getInsertID();
            $details = $this->VideoWatch->getDetails($id);


            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);


            die();
            /*   }else{

                   $output['code'] = 201;
                   $output['msg'] = "duplicate";
                   echo json_encode($output);


                   die();
               }*/


        }
    }

    public function purchaseCoin()
    {


        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            if (in_array("PurchaseCoin", App::objects('Model'))) {
                $this->loadModel('PurchaseCoin');


            }else{

                $output['code'] = 201;

                $output['msg'] = "Contact hello@qboxus.com to get these premium features";


                echo json_encode($output);


                die();
            }
            $coin_data['user_id'] = $data['user_id'];
            $coin_data['title'] = $data['title'];
            $coin_data['coin'] = $data['coin'];
            $coin_data['price'] = $data['price'];
            $coin_data['transaction_id'] = $data['transaction_id'];
            $coin_data['device'] = $data['device'];
            $coin_data['created'] = date('Y-m-d H:i:s', time());







            $userDetails = $this->User->getUserDetailsFromID( $data['user_id']);
            if(count($userDetails) > 0) {

                $total_coins_in_db = $userDetails['User']['wallet'];
                $this->PurchaseCoin->save($coin_data);

                $id = $this->PurchaseCoin->getInsertID();

                $output = array();


                $this->User->id = $userDetails['User']['id'];
                $this->User->saveField('wallet',$total_coins_in_db + $data['coin']);
                $details = $this->PurchaseCoin->getDetails($id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();

            }



        }
    }

    public function showCoinWorth()
    {






        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            if (in_array("CoinWorth", App::objects('Model'))) {
                $this->loadModel("CoinWorth");


            }else{

                $output['code'] = 201;

                $output['msg'] = "Contact hello@qboxus.com to get these premium features";


                echo json_encode($output);


                die();
            }

            $details = $this->CoinWorth->getAll();



            if(count($details) > 0) {


                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();
            }else{

                Message::EMPTYDATA();
                die();
            }

        }
    }
    public function showGifts()
    {





        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $if_method_exist = method_exists('Extended', 'deleteObjectS3');

            if($if_method_exist) {

                if (in_array("Gift", App::objects('Model'))) {
                    $this->loadModel("Gift");
                }else{

                    $output['code'] = 201;

                    $output['msg'] = "Contact hello@qboxus.com to get these premium features";


                    echo json_encode($output);


                    die();
                }



                if (isset($data['id'])) {

                    $gifts = $this->Gift->getDetails($data['id']);

                } else {
                    $gifts = $this->Gift->getAll();

                }


                $output['code'] = 200;

                $output['msg'] = $gifts;


                echo json_encode($output);


                die();
            }else{


                $output['code'] = 201;

                $output['msg'] = "Contact hello@qboxus.com to get these premium features";


                echo json_encode($output);


                die();
            }

        }
    }


    public function showAppSlider()
    {

        $this->loadModel("AppSlider");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $images = $this->AppSlider->getAll();


            $output['code'] = 200;

            $output['msg'] = $images;
            echo json_encode($output);


            die();
        }
    }

    public function showDiscoverySections()
    {

        $this->loadModel("HashtagVideo");
        $this->loadModel("Follower");
        $this->loadModel("VideoLike");
        $this->loadModel("VideoFavourite");
        $this->loadModel("VideoComment");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $starting_point = 0;


            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }
            $hashtags = $this->HashtagVideo->getHastagsWhichHasGreaterNoOfVideos($starting_point);



            if(count($hashtags) > 0) {

                $new_array = array();
                foreach ($hashtags as $key => $hashtag) {

                    $hashtag_videos = $this->HashtagVideo->getHashtagVideosLimit($hashtag['Hashtag']['id']);
                    $hashtag_videos_count = $this->HashtagVideo->countHashtagVideos($hashtag['Hashtag']['id']);
                    if(count($hashtag_videos) > 0) {


                        $i = 0;
                        foreach($hashtag_videos as $v) {
                            $hashtag_videos[$i]['Video']['like'] = 0;
                            $hashtag_videos[$i]['Video']['favourite'] = 0;
                            if (isset($data['user_id'])) {
                                $user_id = $data['user_id'];

                                $video_user_id = $v['Video']['user_id'];
                                $video_id = $v['Video']['id'];

                                if ($user_id != $video_user_id) {

                                    $follower_details = $this->Follower->ifFollowing($user_id, $video_user_id);

                                    if (count($follower_details) > 0) {

                                        $hashtag_videos[$i]['Video']['User']['button'] = "unfollow";

                                    } else {


                                        $hashtag_videos[$i]['Video']['User']['button'] = "follow";
                                    }



                                    $video_data['user_id'] = $user_id;
                                    $video_data['video_id'] = $video_id;
                                    $video_like_detail = $this->VideoLike->ifExist($video_data);
                                    $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);

                                    if (count($video_like_detail) > 0) {

                                        $hashtag_videos[$i]['Video']['like'] = 1;

                                    }

                                    if (count($video_favourite_detail) > 0) {

                                        $hashtag_videos[$i]['Video']['favourite'] = 1;

                                    }



                                }

                                $video_like_count = $this->VideoLike->countLikes($video_id);
                                $video_comment_count = $this->VideoComment->countComments($video_id);
                                $hashtag_videos[$i]['Video']['like_count'] = $video_like_count;
                                $hashtag_videos[$i]['Video']['comment_count'] = $video_comment_count;





                                $i++;
                            }

                        }
                        $new_array[$key]["Hashtag"] = $hashtag['Hashtag'];
                        $new_array[$key]["Hashtag"]['views'] = $hashtag[0]['total_views'];

                        $new_array[$key]["Hashtag"]['Videos'] = $hashtag_videos;
                        $new_array[$key]["Hashtag"]['videos_count'] = $hashtag_videos_count;
                    }
                }

            }

            if(count($new_array) > 0) {

                $output['code'] = 200;

                $output['msg'] = $new_array;
                echo json_encode($output);


                die();
            }else{

                Message::EMPTYDATA();
                die();
            }
        }
    }


    public function addOrderSession()
    {

        $this->loadModel("OrderSession");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];

            $string = $json;

            $created = date('Y-m-d H:i:s', time());


            if(isset( $data['string'])){

                $string = $data['string'];
            }



            $session['user_id'] = $user_id;
            $session['string']     = $string;
            $session['created']    = $created;


            $details = $this->OrderSession->getAll();
            if(count($details) > 0){

                foreach($details as $detail) {

                    $datetime1 = new DateTime($created);
                    $datetime2 = new DateTime($detail['OrderSession']['created']);
                    $interval = $datetime1->diff($datetime2);
                    $minutes = $interval->format('%i');
                    $id = $detail['OrderSession']['id'];
                    if ($minutes > 60) {

                        $this->OrderSession->delete($id);

                    }
                }

            }


            $this->OrderSession->save($session);
            $id = $this->OrderSession->getInsertID();
            $details =   $this->OrderSession->getDetails($id);

            $output['code'] = 200;

            $output['msg'] = $details;
            echo json_encode($output);

            die();


        }
    }

    public function showOrderSession()
    {

        $this->loadModel("OrderSession");
        $this->loadModel("User");
        //$this->loadModel("PaymentCard");
        //$this->loadModel("StripeCustomer");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];

            $details = $this->OrderSession->getDetails($id);


            $output['code'] = 200;

            $output['msg'] = $details;

            echo json_encode($output);


            die();
        }else{

            Message::EmptyDATA();
            die();



        }
    }

    public function postCommentOnVideo()
    {

        $this->loadModel("VideoComment");
        $this->loadModel("User");
        $this->loadModel("Video");
        $this->loadModel("Notification");
        $this->loadModel("PushNotification");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_id = $data['video_id'];
            $user_id = $data['user_id'];
            $comment = $data['comment'];


            $created = date('Y-m-d H:i:s', time());


            $comment_video['video_id'] = $video_id;
            $comment_video['user_id'] = $user_id;
            $comment_video['comment'] = $comment;
            $comment_video['created'] = $created;



            $video_details = $this->Video->getDetails($video_id);
            $userDetails = $this->User->getUserDetailsFromID($user_id);
            if(count($userDetails) > 0) {




                $this->VideoComment->save($comment_video);


                $id = $this->VideoComment->getInsertID();
                $details = $this->VideoComment->getDetails($id);


                $notification_msg = $userDetails['User']['username'] . ' commented: ' . $comment;
                $notification['to'] = $video_details['User']['device_token'];
                $notification['notification']['title'] = $notification_msg;
                $notification['notification']['body'] = "";
                $notification['notification']['badge'] = "1";
                $notification['notification']['sound'] = "default";
                $notification['notification']['icon'] = "";
                $notification['notification']['type'] = "comment";
                $notification['data']['title'] = $notification_msg;
                $notification['data']['body'] = '';
                $notification['data']['icon'] = "";
                $notification['data']['badge'] = "1";
                $notification['data']['sound'] = "default";
                $notification['data']['type'] = "comment";
                $notification['notification']['receiver_id'] =  $video_details['User']['id'];
                $notification['data']['receiver_id'] = $video_details['User']['id'];

                $if_exist = $this->PushNotification->getDetails($video_details['User']['id']);
                if (count($if_exist) > 0) {

                    $likes = $if_exist['PushNotification']['new_followers'];
                    if ($likes > 0) {
                        Utility::sendPushNotificationToMobileDevice(json_encode($notification));
                    }
                }

                $notification_data['sender_id'] = $user_id;
                $notification_data['receiver_id'] = $video_details['User']['id'];
                $notification_data['type'] = "video_comment";
                $notification_data['video_id'] = $video_id;

                $notification_data['string'] = $notification_msg;
                $notification_data['created'] = $created;

                $this->Notification->save($notification_data);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();
            }else{

                $output['code'] = 201;
                $output['msg'] = "Login First";
                echo json_encode($output);


                die();

            }


        }
    }

    public function postCommentReply()
    {

        $this->loadModel("VideoComment");
        $this->loadModel("VideoCommentReply");
        $this->loadModel("Video");
        $this->loadModel("User");
        $this->loadModel("Notification");
        $this->loadModel("PushNotification");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $comment_id = $data['comment_id'];
            $user_id = $data['user_id'];
            $comment = $data['comment'];


            $created = date('Y-m-d H:i:s', time());


            $comment_video['comment_id'] = $comment_id;
            $comment_video['user_id'] = $user_id;
            $comment_video['comment'] = $comment;
            $comment_video['created'] = $created;




            $userDetails = $this->User->getUserDetailsFromID($user_id);
            $comment_details = $this->VideoComment->getDetails($comment_id);





            $this->VideoCommentReply->save($comment_video);



            $id = $this->VideoCommentReply->getInsertID();
            $details = $this->VideoCommentReply->getDetails($id);



            $notification_msg = $userDetails['User']['username'].' replied to your comment: '.$comment;
            $notification['to'] = $comment_details['User']['device_token'];
            $notification['notification']['title'] = $notification_msg;
            $notification['notification']['body'] = "";
            $notification['notification']['badge'] = "1";
            $notification['notification']['sound'] = "default";
            $notification['notification']['icon'] = "";
            $notification['notification']['type'] = "comment";
            $notification['data']['title'] = $notification_msg;
            $notification['data']['body'] = '';
            $notification['data']['icon'] = "";
            $notification['data']['badge'] = "1";
            $notification['data']['sound'] = "default";
            $notification['data']['type'] = "comment";



            $notification_data['sender_id'] = $user_id;
            $notification_data['receiver_id'] = $comment_details['User']['id'];
            $notification_data['type'] = "video_comment";
            $notification_data['video_id'] = 0;

            $notification_data['string'] =  $notification_msg;
            $notification_data['created'] =  $created;

            $this->Notification->save($notification_data);



            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);


            die();



        }
    }


    public function downloadVideo()
    {

        $this->loadModel("Video");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_id = $data['video_id'];


            $video_details = $this->Video->getDetails($video_id);
            if(count($video_details) > 0) {
                if(MEDIA_STORAGE == "s3") {

                    if (method_exists('Extended', 'addWaterMarkAndText')) {
                        $download_video = Extended::addWaterMarkAndText($video_details['Video']['video'], 1, $video_details['User']['username'],$video_details['Video']['duration']);

                        if ($download_video) {


                            $output['code'] = 200;
                            $output['msg'] = $download_video;
                            echo json_encode($output);


                            die();
                        } else {


                            $output['code'] = 200;
                            $output['msg'] = $video_details['Video']['video'];
                            echo json_encode($output);


                            die();
                        }


                    }else{

                        $output['code'] = 201;

                        $output['msg'] = "It seems like you do not have extended files. submit ticket on yeahhelp.com for support";


                        echo json_encode($output);


                        die();
                    }
                }else{

                    $output['code'] = 200;
                    $output['msg'] = $video_details['Video']['video'];
                    echo json_encode($output);


                    die();

                }


            }





        }
    }


    public function deleteWaterMarkVideo(){

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $url = $data['video_url'];

            if (strlen($url) > 5) {
                @unlink($url);

                $output['code'] = 200;
                $output['msg'] = "deleted";
                echo json_encode($output);


                die();
            }

        }else{
            $output['code'] = 201;
            $output['msg'] = "invalid url";
            echo json_encode($output);


            die();



        }
    }
    public function showHtmlPage()
    {
        $this->loadModel("HtmlPage");
        $this->autoRender = true;
        $params = $this->params['url'];
        $page_name = $params['page'];
        $page_exist = $this->HtmlPage->ifExist($page_name);
        if(count($page_exist) > 0){

            $this->set("data",$page_exist);
        }
    }

    public function likeVideo()
    {

        $this->loadModel("VideoLike");
        $this->loadModel("PushNotification");
        $this->loadModel("Notification");
        $this->loadModel("User");
        $this->loadModel("Video");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_id = $data['video_id'];
            $user_id = $data['user_id'];



            $created = date('Y-m-d H:i:s', time());


            $like_video['video_id'] = $video_id;
            $like_video['user_id'] = $user_id;
            $like_video['created'] = $created;




            $details = $this->VideoLike->ifExist($like_video);
            $userDetail = $this->User->getUserDetailsFromID($user_id);
            $video_details = $this->Video->getDetails($video_id);


            if(count($userDetail) > 0) {

                if (count($details) > 0) {

                    $this->VideoLike->id = $details['VideoLike']['id'];
                    $this->VideoLike->delete();
                    $msg = "unlike";
                } else {

                    $this->VideoLike->save($like_video);


                    $id = $this->VideoLike->getInsertID();
                    $details = $this->VideoLike->getDetails($id);

                    $msg = $details;


                    $notification_msg = $userDetail['User']['username'] . ' liked your video';
                    $notification['to'] = $video_details['User']['device_token'];
                    $notification['notification']['title'] = $notification_msg;
                    $notification['notification']['body'] = "";
                    $notification['notification']['badge'] = "1";
                    $notification['notification']['sound'] = "default";
                    $notification['notification']['icon'] = "";
                    $notification['notification']['type'] = "";
                    $notification['data']['title'] = $notification_msg;
                    $notification['data']['body'] = '';
                    $notification['data']['icon'] = "";
                    $notification['data']['badge'] = "1";
                    $notification['data']['sound'] = "default";
                    $notification['data']['type'] = "";
                    $notification['notification']['receiver_id'] =  $video_details['User']['id'];
                    $notification['data']['receiver_id'] = $video_details['User']['id'];

                    $if_exist = $this->PushNotification->getDetails($userDetail['User']['id']);
                    if (count($if_exist) > 0) {

                        $likes = $if_exist['PushNotification']['likes'];
                        if ($likes > 0) {
                            Utility::sendPushNotificationToMobileDevice(json_encode($notification));
                        }
                    }
                    $notification_data['video_id'] = $video_id;
                    $notification_data['sender_id'] = $user_id;
                    $notification_data['receiver_id'] = $video_details['User']['id'];
                    $notification_data['type'] = "video_like";
                    $notification_data['string'] = $notification_msg;
                    $notification_data['created'] = $created;

                    $this->Notification->save($notification_data);


                }

                $output['code'] = 200;
                $output['msg'] = $msg;
                echo json_encode($output);


                die();

            } else{

                $output['code'] = 201;
                $output['msg'] = "Login First";
                echo json_encode($output);


                die();

            }


        }
    }

    public function addVideoFavourite()
    {

        $this->loadModel("VideoFavourite");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_id = $data['video_id'];
            $user_id = $data['user_id'];



            $created = date('Y-m-d H:i:s', time());


            $fav_video['video_id'] = $video_id;
            $fav_video['user_id'] = $user_id;
            $fav_video['created'] = $created;




            $details = $this->VideoFavourite->ifExist($fav_video);

            if(count($details) > 0){

                $this->VideoFavourite->id = $details['VideoFavourite']['id'];
                $this->VideoFavourite->delete();
                $msg = "unfavourite";
            }else{

                $this->VideoFavourite->save($fav_video);



                $id = $this->VideoFavourite->getInsertID();
                $details = $this->VideoFavourite->getDetails($id);

                $msg = $details;

            }

            $output['code'] = 200;
            $output['msg'] = $msg;
            echo json_encode($output);


            die();




        }
    }

    public function likeComment()
    {

        $this->loadModel("VideoCommentLike");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $comment_id = $data['comment_id'];
            $user_id = $data['user_id'];



            $created = date('Y-m-d H:i:s', time());


            $fav_comm['comment_id'] = $comment_id;
            $fav_comm['user_id'] = $user_id;
            $fav_comm['created'] = $created;




            $details = $this->VideoCommentLike->ifExist($fav_comm);

            if(count($details) > 0){

                $this->VideoCommentLike->id = $details['VideoCommentLike']['id'];
                $this->VideoCommentLike->delete();
                $msg = "unfavourite";
            }else{

                $this->VideoCommentLike->save($fav_comm);



                $id = $this->VideoCommentLike->getInsertID();
                $details = $this->VideoCommentLike->getDetails($id);

                $msg = $details;

            }

            $output['code'] = 200;
            $output['msg'] = $msg;
            echo json_encode($output);


            die();




        }
    }
    public function likeCommentReply()
    {

        $this->loadModel("VideoCommentReplyLike");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_comment_reply_id = $data['comment_reply_id'];
            $user_id = $data['user_id'];



            $created = date('Y-m-d H:i:s', time());


            $fav_comm['comment_reply_id'] = $video_comment_reply_id;
            $fav_comm['user_id'] = $user_id;
            $fav_comm['created'] = $created;




            $details = $this->VideoCommentReplyLike->ifExist($fav_comm);

            if(count($details) > 0){

                $this->VideoCommentReplyLike->id = $details['VideoCommentReplyLike']['id'];
                $this->VideoCommentReplyLike->delete();
                $msg = "unfavourite";
            }else{

                $this->VideoCommentReplyLike->save($fav_comm);



                $id = $this->VideoCommentReplyLike->getInsertID();
                $details = $this->VideoCommentReplyLike->getDetails($id);

                $msg = $details;

            }

            $output['code'] = 200;
            $output['msg'] = $msg;
            echo json_encode($output);


            die();




        }
    }

    public function addHashtagFavourite()
    {

        $this->loadModel("HashtagFavourite");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $hashtag_id = $data['hashtag_id'];
            $user_id = $data['user_id'];



            $created = date('Y-m-d H:i:s', time());


            $hashtag_fav['hashtag_id'] = $hashtag_id;
            $hashtag_fav['user_id'] = $user_id;
            $hashtag_fav['created'] = $created;




            $details = $this->HashtagFavourite->ifExist($hashtag_fav);

            if(count($details) > 0){

                $this->HashtagFavourite->id = $details['HashtagFavourite']['id'];
                $this->HashtagFavourite->delete();
                $msg = "unfavourite";
            }else{

                $this->HashtagFavourite->save($hashtag_fav);



                $id = $this->HashtagFavourite->getInsertID();
                $details = $this->HashtagFavourite->getDetails($id);

                $msg = $details;

            }

            $output['code'] = 200;
            $output['msg'] = $msg;
            echo json_encode($output);


            die();




        }
    }

    public function showFavouriteHashtags()
    {

        $this->loadModel("HashtagFavourite");
        $this->loadModel("HashtagVideo");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $user_id = $data['user_id'];
            $starting_point = 0;


            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }
            $fav_posts = $this->HashtagFavourite->getUserFavouriteHashtags($user_id,$starting_point);



            if(count($fav_posts) > 0) {


                foreach ($fav_posts as $key=>$hashtag){


                    $hashtag_views = $this->HashtagVideo->countHashtagViews($hashtag['Hashtag']['id']);
                    $hashtag_videos_count = $this->HashtagVideo->countHashtagVideos($hashtag['Hashtag']['id']);

                    $fav_posts[$key]['Hashtag']['videos_count'] = $hashtag_videos_count;
                    $fav_posts[$key]['Hashtag']['views'] = $hashtag_views[0]['total_sum'];
                }
                $output['code'] = 200;

                $output['msg'] = $fav_posts;


                echo json_encode($output);


                die();

            }else{
                Message::EMPTYDATA();
                die();


            }
        }
    }

    public function addSoundFavourite()
    {

        $this->loadModel("SoundFavourite");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_id = $data['sound_id'];
            $user_id = $data['user_id'];



            $created = date('Y-m-d H:i:s', time());


            $fav_sound['sound_id'] = $video_id;
            $fav_sound['user_id'] = $user_id;
            $fav_sound['created'] = $created;




            $details = $this->SoundFavourite->ifExist($fav_sound);

            if(count($details) > 0){

                $this->SoundFavourite->id = $details['SoundFavourite']['id'];
                $this->SoundFavourite->delete();
                $msg = "unfavourite";
            }else{

                $this->SoundFavourite->save($fav_sound);



                $id = $this->SoundFavourite->getInsertID();
                $details = $this->SoundFavourite->getDetails($id);

                $msg = $details;

            }

            $output['code'] = 200;
            $output['msg'] = $msg;
            echo json_encode($output);


            die();




        }
    }
    public function showFavouriteSounds()
    {

        $this->loadModel("SoundFavourite");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $user_id = $data['user_id'];
            $starting_point = 0;


            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }
            $fav_posts = $this->SoundFavourite->getUserFavouriteSounds($user_id,$starting_point);


            if(count($fav_posts) > 0) {
                $output['code'] = 200;

                $output['msg'] = $fav_posts;


                echo json_encode($output);


                die();

            }else{
                Message::EMPTYDATA();
                die();


            }
        }
    }

    public function showFavouriteVideos()
    {

        $this->loadModel("VideoFavourite");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $user_id = $data['user_id'];

            $starting_point = 0;


            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }
            $fav_posts = $this->VideoFavourite->getUserFavouriteVideos($user_id,$starting_point);


            if(count($fav_posts) > 0) {
                $output['code'] = 200;

                $output['msg'] = $fav_posts;


                echo json_encode($output);


                die();

            }else{
                Message::EMPTYDATA();
                die();


            }
        }
    }


    public function showUserDetail()
    {

        $this->loadModel("User");
        $this->loadModel("Follower");
        $this->loadModel("VideoLike");
        $this->loadModel("Video");
        $this->loadModel("ProfileVisit");
        $this->loadModel("BlockUser");





        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if(isset($data['user_id'])){

                $user_id = $data['user_id'];
            }

            $video_ids = $this->Video->getUserVideosIDs($user_id);

            $likes_count = 0;
            if(count($video_ids) > 0) {
                foreach ($video_ids as $v_key => $v_val) {

                    $user_video_ids[$v_key] = $v_val['Video']['id'];

                }
                $likes_count = $this->VideoLike->countLikesOnAllUserVideos($user_video_ids);
            }
            $created = date('Y-m-d H:i:s', time());

            $userDetail = $this->User->getUserDetailsFromID($user_id);
            $sender_user_details = $userDetail;
            $followers_count = $this->Follower->countFollowers($user_id);
            $following_count = $this->Follower->countFollowing($user_id);
            //$likes_count = $this->VideoLike->countUserAllVideoLikes($user_id);

            $video_count = $this->Video->getUserVideosCount($user_id);
            $userDetail['User']['followers_count'] = $followers_count;
            $userDetail['User']['following_count'] = $following_count;
            $userDetail['User']['likes_count'] = $likes_count;
            $userDetail['User']['video_count'] = $video_count;



            if(isset($data['other_user_id'])){

                $other_user_id = $data['other_user_id'];

                if($other_user_id < 1){
                    $username = $data['username'];

                    $user_id_details = $this->User->getUserDetailsFromUsername($username);

                    $other_user_id = $user_id_details['User']['id'];
                }
                $userDetail = $this->User->getUserDetailsFromID($other_user_id);


                $video_ids = $this->Video->getUserVideosIDs($other_user_id);
                $likes_count = 0;

                if(count($video_ids) > 0) {
                    foreach ($video_ids as $v_key => $v_val) {

                        $user_video_ids[$v_key] = $v_val['Video']['id'];
                        $likes_count = $this->VideoLike->countLikesOnAllUserVideos($user_video_ids);

                    }
                }
                $other_person_user_id = $other_user_id;
                $follower_details = $this->Follower->ifFollowing($user_id, $other_person_user_id);
                $following_details = $this->Follower->ifFollowing($other_person_user_id, $user_id);
                $followers_count = $this->Follower->countFollowers($other_user_id);
                $following_count = $this->Follower->countFollowing($other_user_id);
                $if_blocked = $this->BlockUser->ifBlocked($user_id, $other_person_user_id);
                $if_other_blocked = $this->BlockUser->ifBlocked($other_person_user_id,$user_id);

                $video_count = $this->Video->getUserVideosCount($other_user_id);
                $userDetail['User']['followers_count'] = $followers_count;
                $userDetail['User']['following_count'] = $following_count;
                $userDetail['User']['likes_count'] = $likes_count;
                $userDetail['User']['video_count'] = $video_count;
                $userDetail['User']['block'] = 0;
                if(count($follower_details) > 0 && count($following_details) > 0){

                    $userDetail['User']['button'] = "Friends";

                } else   if(count($follower_details) > 0 && count($following_details) < 1){

                    $userDetail['User']['button'] = "following";

                }else if (count($following_details) > 0){


                    $userDetail['User']['button'] = "follow back";
                }else{


                    $userDetail['User']['button'] = "follow";
                }

                if(count($if_blocked) > 0 ){

                    $userDetail['User']['block'] = 1;
                    $userDetail['User']['BlockUser'] = $if_blocked['BlockUser'];

                }

                if(count($if_other_blocked) > 0){


                    $userDetail['User']['block'] = 1;
                    $userDetail['User']['BlockUser'] = $if_other_blocked['BlockUser'];

                }





                $profile_visit['sender_id'] = $user_id;
                $profile_visit['receiver_id'] = $other_user_id;
                $profile_visit['gender'] = $sender_user_details['User']['gender'];
                $profile_visit['dob'] = $sender_user_details['User']['dob'];
                $profile_visit['sender_city'] = $sender_user_details['User']['city'];
                $profile_visit['sender_country'] = $sender_user_details['User']['country'];
                $profile_visit['created'] = $created;

                $this->ProfileVisit->save($profile_visit);
            }


            if(isset($data['username'])){

                $user_id_details = $this->User->getUserDetailsFromUsername($data['username']);
                $other_user_id = $user_id_details['User']['id'];


                $video_ids = $this->Video->getUserVideosIDs($other_user_id);

                $likes_count = 0;
                if(count($video_ids) > 0) {

                    foreach ($video_ids as $v_key => $v_val) {

                        $user_video_ids[$v_key] = $v_val['Video']['id'];

                    }

                    $likes_count = $this->VideoLike->countLikesOnAllUserVideos($user_video_ids);
                }
                if($other_user_id < 1){
                    $username = $data['username'];

                    $user_id_details = $this->User->getUserDetailsFromUsername($username);

                    $other_user_id = $user_id_details['User']['id'];
                }
                $userDetail = $this->User->getUserDetailsFromID($other_user_id);

                $other_person_user_id = $other_user_id;
                $follower_details = $this->Follower->ifFollowing($user_id, $other_person_user_id);
                $following_details = $this->Follower->ifFollowing($other_person_user_id, $user_id);
                $if_blocked = $this->BlockUser->ifBlocked($user_id, $other_person_user_id);
                $if_other_blocked = $this->BlockUser->ifBlocked($other_person_user_id,$user_id);
                $followers_count = $this->Follower->countFollowers($other_user_id);
                $following_count = $this->Follower->countFollowing($other_user_id);

                $video_count = $this->Video->getUserVideosCount($other_user_id);
                $userDetail['User']['followers_count'] = $followers_count;
                $userDetail['User']['following_count'] = $following_count;
                $userDetail['User']['likes_count'] = $likes_count;
                $userDetail['User']['video_count'] = $video_count;
                $userDetail['User']['block'] = 0;
                if(count($follower_details) > 0 && count($following_details) > 0){

                    $userDetail['User']['button'] = "Friends";

                } else   if(count($follower_details) > 0 && count($following_details) < 1){

                    $userDetail['User']['button'] = "following";

                }else if (count($following_details) > 0){


                    $userDetail['User']['button'] = "follow back";
                }else{


                    $userDetail['User']['button'] = "follow";
                }

                if(count($if_blocked) > 0 ){

                    $userDetail['User']['block'] = 1;
                    $userDetail['User']['BlockUser'] = $if_blocked['BlockUser'];

                }

                if(count($if_other_blocked) > 0){


                    $userDetail['User']['block'] = 1;
                    $userDetail['User']['BlockUser'] = $if_other_blocked['BlockUser'];

                }


                $profile_visit['sender_id'] = $user_id;
                $profile_visit['receiver_id'] = $other_user_id;
                $profile_visit['gender'] = $sender_user_details['User']['gender'];
                $profile_visit['dob'] = $sender_user_details['User']['dob'];
                $profile_visit['sender_city'] = $sender_user_details['User']['city'];
                $profile_visit['sender_country'] = $sender_user_details['User']['country'];
                $profile_visit['created'] = $created;

                $this->ProfileVisit->save($profile_visit);
            }







            $output['code'] = 200;

            $output['msg'] = $userDetail;

            echo json_encode($output);


            die();
        }
    }

    public function showVideosAgainstUserID(){

        $this->loadModel('Video');
        $this->loadModel('VideoLike');
        $this->loadModel('VideoFavourite');
        $this->loadModel('VideoComment');
        $this->loadModel('Follower');







        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = 0;
            $starting_point = 0;


            $videos_private = array();
            $videos_public = array();
            if (isset($data['user_id'])) {

                $user_id = $data['user_id'];

            }

            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }



            if($user_id > 0){

                $videos_public = $this->Video->getUserPublicVideos($user_id,$starting_point);
                $videos_private = $this->Video->getUserPrivateVideos($user_id,$starting_point);
            }



            if (isset($data['other_user_id'])) {


                $videos_public = $this->Video->getUserPublicVideos($data['other_user_id'],$starting_point);



            }


            if (count($videos_public) > 0) {


                foreach ($videos_public as $key => $video) {


                    if ($user_id > 0) {
                        $video_data['user_id'] = $user_id;
                        $video_data['video_id'] = $video['Video']['id'];
                        $video_like_detail = $this->VideoLike->ifExist($video_data);
                        $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);

                        if (count($video_like_detail) > 0) {

                            $videos_public[$key]['Video']['like'] = 1;

                        } else {

                            $videos_public[$key]['Video']['like'] = 0;
                        }

                        if (count($video_favourite_detail) > 0) {

                            $videos_public[$key]['Video']['favourite'] = 1;

                        } else {

                            $videos_public[$key]['Video']['favourite'] = 0;
                        }
                    } else {


                        $videos_public[$key]['Video']['like'] = 0;
                        $videos_public[$key]['Video']['favourite'] = 0;


                    }


                    if (isset($data['other_user_id'])) {
                        $other_person_user_id = $data['other_user_id'];
                        $follower_details = $this->Follower->ifFollowing($user_id, $other_person_user_id);
                        $following_details = $this->Follower->ifFollowing($other_person_user_id, $user_id);
                        $followers_count = $this->Follower->countFollowers($other_person_user_id);
                        $following_count = $this->Follower->countFollowing($other_person_user_id);
                        $likes_count = $this->VideoLike->countUserAllVideoLikes($other_person_user_id);
                        $video_count = $this->Video->getUserVideosCount($other_person_user_id);
                        $videos_public[$key]['User']['followers_count'] = $followers_count;
                        $videos_public[$key]['User']['following_count'] = $following_count;
                        $videos_public[$key]['User']['likes_count'] = $likes_count;
                        $videos_public[$key]['User']['video_count'] = $video_count;
                        if (count($follower_details) > 0 && count($following_details) > 0) {

                            $videos_public[$key]['User']['button'] = "Friends";

                        } else if (count($follower_details) > 0 && count($following_details) < 1) {

                            $videos_public[$key]['User']['button'] = "following";

                        } else if (count($following_details) > 0) {


                            $videos_public[$key]['User']['button'] = "follow back";
                        } else {


                            $videos_public[$key]['User']['button'] = "follow";
                        }
                    }

                    $comment_count = $this->VideoComment->countComments($video['Video']['id']);
                    $video_likes_count = $this->VideoLike->countLikes($video['Video']['id']);


                    $videos_public[$key]['Video']['comment_count'] = $comment_count;
                    $videos_public[$key]['Video']['like_count'] = $video_likes_count;

                }


                $output['code'] = 200;

                $output['msg']['public'] = $videos_public;
                $output['msg']['private'] = $videos_private;


                echo json_encode($output);


            }else{

                Message::EMPTYDATA();
                die();

            }

        }
    }

    public function showVideoComments(){

        $this->loadModel('VideoComment');
        $this->loadModel('VideoCommentLike');
        $this->loadModel('VideoCommentReplyLike');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $video_id = $data['video_id'];
            $user_id = 0;
            if (isset($data['user_id'])) {

                $user_id = $data['user_id'];
            }

            $starting_point = 0;


            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }

            $comments = $this->VideoComment->getVideoComments($video_id,$starting_point);

//pr($comments);
            if(count($comments) > 0) {
                foreach ($comments as $key => $comment) {


                    $comment_data['user_id'] = $user_id;
                    $comment_data['comment_id'] = $comment['VideoComment']['id'];

                    $video_like_detail = $this->VideoCommentLike->ifExist($comment_data);

                    if (count($video_like_detail) > 0) {

                        $comments[$key]['VideoComment']['like'] = 1;

                    } else {

                        $comments[$key]['VideoComment']['like'] = 0;
                    }


                    $video_comment_replies = $comment['VideoCommentReply'];

                    if(count($video_comment_replies) > 0){


                        foreach ($video_comment_replies  as $key2=>$comment_reply){



                            $comment_reply_data['user_id'] = $user_id;
                            $comment_reply_data['comment_reply_id'] = $comment_reply['id'];

                            $comment_reply_like_detail = $this->VideoCommentReplyLike->ifExist($comment_reply_data);

                            if (count($comment_reply_like_detail) > 0) {

                                $comments[$key]['VideoCommentReply'][$key2]['like'] = 1;

                            } else {

                                $comments[$key]['VideoCommentReply'][$key2]['like'] = 0;
                            }
                            $like_count = $this->VideoCommentReplyLike->countLikes($comment_reply['id']);
                            $comments[$key]['VideoCommentReply'][$key2]['like_count'] = $like_count;
                        }
                    }



                    $like_count = $this->VideoCommentLike->countLikes($comment['VideoComment']['id']);
                    $comments[$key]['VideoComment']['like_count'] = $like_count;

                }


                $output['code'] = 200;

                $output['msg'] = $comments;


                echo json_encode($output);


                die();

            }else{

                Message::EMPTYDATA();
                die();

            }
        }


    }
    public function showReportReasons(){

        $this->loadModel('ReportReason');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $details = $this->ReportReason->getAll();


            if(count($details) > 0) {


                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();
            }else{


                Message::EMPTYDATA();
                die();
            }

        }


    }



    public function reportVideo()
    {


        $this->loadModel("ReportVideo");
        $this->loadModel("ReportReason");
        $this->loadModel("Video");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_id = $data['video_id'];
            $user_id = $data['user_id'];
            $report_reason_id = $data['report_reason_id'];
            $description = $data['description'];
            $created = date('Y-m-d H:i:s', time());


            $report['video_id'] = $video_id;
            $report['user_id'] = $user_id;
            $report['report_reason_id'] = $report_reason_id;
            $report['description'] = $description;
            $report['created'] = $created;


            $video_details = $this->Video->getDetails($video_id);

            $report_reason_details =  $this->ReportReason->getDetails($report_reason_id);

            if (count($video_details) > 0) {

                if(count($report_reason_details) > 0){

                    $report['report_reason_title'] = $report_reason_details['ReportReason']['title'];

                }

                $this->ReportVideo->save($report);






                $id = $this->ReportVideo->getInsertID();
                $details = $this->ReportVideo->getDetails($id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();
            }else{

                $output['code'] = 201;
                $output['msg'] = "video not available";
                echo json_encode($output);


                die();
            }


        }
    }

    public function NotInterestedVideo()
    {


        $this->loadModel("NotInterestedVideo");




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_id = $data['video_id'];
            $user_id = $data['user_id'];

            $created = date('Y-m-d H:i:s', time());


            $not_interested['video_id'] = $video_id;
            $not_interested['user_id'] = $user_id;

            $not_interested['created'] = $created;


            $details = $this->NotInterestedVideo->getDetails($user_id,$video_id);



            if (count($details) < 1) {

                $this->NotInterestedVideo->save($not_interested);






                $id = $this->NotInterestedVideo->getInsertID();
                $details = $this->NotInterestedVideo->getDetails($id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();
            }else{

                $output['code'] = 201;
                $output['msg'] = "already added";
                echo json_encode($output);


                die();
            }


        }
    }

    public function showUserLikedVideos(){

        $this->loadModel('VideoLike');
        $this->loadModel('Follower');
        $this->loadModel('Video');





        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];
            $starting_point = 0;


            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }
            $videos = $this->VideoLike->getUserAllVideoLikes($user_id,$starting_point);
            if(count($videos) > 0) {
                foreach ($videos as $key => $video) {


                    $video_likes_count = $this->VideoLike->countLikes($video['Video']['id']);
                    $videos[$key]['Video']['like_count'] = $video_likes_count;
                    $videos[$key]['Video']['like'] = 1;



                    if (isset($data['other_user_id'])) {
                        $other_person_user_id = $data['other_user_id'];
                        $follower_details = $this->Follower->ifFollowing($user_id, $other_person_user_id);
                        $following_details = $this->Follower->ifFollowing($other_person_user_id, $user_id);
                        $followers_count = $this->Follower->countFollowers($other_person_user_id);
                        $following_count = $this->Follower->countFollowing($other_person_user_id);
                        $likes_count = $this->VideoLike->countUserAllVideoLikes($other_person_user_id);
                        $video_count = $this->Video->getUserVideosCount($other_person_user_id);
                        $videos[$key]['User']['followers_count'] = $followers_count;
                        $videos[$key]['User']['following_count'] = $following_count;
                        $videos[$key]['User']['likes_count'] = $likes_count;
                        $videos[$key]['User']['video_count'] = $video_count;
                        if (count($follower_details) > 0 && count($following_details) > 0) {

                            $videos[$key]['User']['button'] = "Friends";

                        } else if (count($follower_details) > 0 && count($following_details) < 1) {

                            $videos[$key]['User']['button'] = "following";

                        } else if (count($following_details) > 0) {


                            $videos[$key]['User']['button'] = "follow back";
                        } else {


                            $videos[$key]['User']['button'] = "follow";
                        }
                    }
                }


                $output['code'] = 200;

                $output['msg'] = $videos;


                echo json_encode($output);


                die();

            }else{


                Message::EMPTYDATA();
                die();

            }
        }


    }


    public function showVideoDetail(){

        $this->loadModel('Video');
        $this->loadModel('VideoLike');
        $this->loadModel('VideoFavourite');
        $this->loadModel('VideoComment');
        $this->loadModel('Follower');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $video_id = $data['video_id'];
            $user_id = 0;

            if(isset($data['user_id'])){

                $user_id = $data['user_id'];

            }

            $video_detail = $this->Video->getDetails($video_id);
            $duet_video_id = $video_detail['Video']['duet_video_id'];
            //duet
            if ($duet_video_id > 0) {
                $video_detail_duet = $this->Video->getDetails($duet_video_id);
                if(count($video_detail_duet) > 0){
                    $video_detail['Video']['duet'] = $video_detail_duet;
                }
            }
            if($user_id > 0) {
                $video_data['user_id'] = $user_id;
                $video_data['video_id'] = $video_detail['Video']['id'];
                $video_like_detail = $this->VideoLike->ifExist($video_data);
                $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);

                if (count($video_like_detail) > 0) {

                    $video_detail['Video']['like'] = 1;

                } else {

                    $video_detail['Video']['like'] = 0;
                }

                if (count($video_favourite_detail) > 0) {

                    $video_detail['Video']['favourite'] = 1;

                } else {

                    $video_detail['Video']['favourite'] = 0;
                }

                $follower_details = $this->Follower->ifFollowing($user_id, $video_detail['Video']['user_id']);
                $following_details = $this->Follower->ifFollowing($video_detail['Video']['user_id'], $user_id);
                if(count($follower_details) > 0 && count($following_details) > 0){

                    $video_detail['User']['button'] = "Friends";

                } else   if(count($follower_details) > 0 && count($following_details) < 1){

                    $video_detail['User']['button'] = "following";

                }else if (count($following_details) > 0){


                    $video_detail['User']['button'] = "follow back";
                }else{


                    $video_detail['User']['button'] = "follow";
                }
            }else{


                $video_detail['Video']['like'] = 0;
                $video_detail['Video']['favourite'] = 0;



            }

            $video_like_count = $this->VideoLike->countLikes($video_detail['Video']['id']);
            $video_comment_count = $this->VideoComment->countComments($video_detail['Video']['id']);
            $video_detail['Video']['like_count'] = $video_like_count;
            $video_detail['Video']['comment_count'] = $video_comment_count;



            $output['code'] = 200;

            $output['msg'] = $video_detail;


            echo json_encode($output);


            die();


        }


    }


    public function showVideoDetailAd(){

        $this->loadModel('Video');
        $this->loadModel('VideoLike');
        $this->loadModel('VideoFavourite');
        $this->loadModel('VideoComment');
        $this->loadModel('Follower');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = 0;

            if(isset($data['user_id'])){

                $user_id = $data['user_id'];

            }

            $video_detail = $this->Video->getPromotedVideo();

            if(count($video_detail) > 0) {
                $duet_video_id = $video_detail['Video']['duet_video_id'];
                //duet
                if ($duet_video_id > 0) {
                    $video_detail_duet = $this->Video->getDetails($duet_video_id);
                    if (count($video_detail_duet) > 0) {
                        $video_detail['Video']['duet'] = $video_detail_duet;
                    }
                }
                if ($user_id > 0) {
                    $video_data['user_id'] = $user_id;
                    $video_data['video_id'] = $video_detail['Video']['id'];
                    $video_like_detail = $this->VideoLike->ifExist($video_data);
                    $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);

                    if (count($video_like_detail) > 0) {

                        $video_detail['Video']['like'] = 1;

                    } else {

                        $video_detail['Video']['like'] = 0;
                    }

                    if (count($video_favourite_detail) > 0) {

                        $video_detail['Video']['favourite'] = 1;

                    } else {

                        $video_detail['Video']['favourite'] = 0;
                    }

                    $follower_details = $this->Follower->ifFollowing($user_id, $video_detail['Video']['user_id']);
                    $following_details = $this->Follower->ifFollowing($video_detail['Video']['user_id'], $user_id);
                    if (count($follower_details) > 0 && count($following_details) > 0) {

                        $video_detail['User']['button'] = "Friends";

                    } else if (count($follower_details) > 0 && count($following_details) < 1) {

                        $video_detail['User']['button'] = "following";

                    } else if (count($following_details) > 0) {


                        $video_detail['User']['button'] = "follow back";
                    } else {


                        $video_detail['User']['button'] = "follow";
                    }
                } else {


                    $video_detail['Video']['like'] = 0;
                    $video_detail['Video']['favourite'] = 0;


                }

                $video_like_count = $this->VideoLike->countLikes($video_detail['Video']['id']);
                $video_comment_count = $this->VideoComment->countComments($video_detail['Video']['id']);
                $video_detail['Video']['like_count'] = $video_like_count;
                $video_detail['Video']['comment_count'] = $video_comment_count;


                $output['code'] = 200;

                $output['msg'] = $video_detail;


                echo json_encode($output);


                die();

            }else{

                Message::EMPTYDATA();
                die();
            }


        }


    }

    public function reportUser()
    {


        $this->loadModel("ReportUser");
        $this->loadModel("User");
        $this->loadModel("ReportReason");




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $report_user_id = $data['report_user_id'];
            $user_id = $data['user_id'];
            $report_reason_id = $data['report_reason_id'];
            $description = $data['description'];
            $created = date('Y-m-d H:i:s', time());


            $report['report_user_id'] = $report_user_id;
            $report['user_id'] = $user_id;
            $report['report_reason_id'] = $report_reason_id;
            $report['description'] = $description;
            $report['created'] = $created;


            $details = $this->User->getUserDetailsFromID($report_user_id);


            $report_reason_details =  $this->ReportReason->getDetails($report_reason_id);




            if (count($details) > 0) {

                if(count($report_reason_details) > 0){

                    $report['report_reason_title'] = $report_reason_details['ReportReason']['title'];

                }
                $this->ReportUser->save($report);






                $id = $this->ReportUser->getInsertID();
                $details = $this->ReportUser->getDetails($id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();
            }else{

                $output['code'] = 201;
                $output['msg'] = "user not available";
                echo json_encode($output);


                die();
            }


        }
    }


    public function updateVideoDetail()
    {


        $this->loadModel("Video");





        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_id = $data['video_id'];
            $video['privacy_type'] = $data['privacy_type'];
            $video['allow_duet'] = $data['allow_duet'];
            $video['allow_comments'] = $data['allow_comments'];


            $video_detail = $this->Video->getDetails($video_id);



            if (count($video_detail) > 0) {
                $this->Video->id = $video_id;
                $this->Video->save($video);






                $video_detail = $this->Video->getDetails($video_id);


                $output['code'] = 200;
                $output['msg'] = $video_detail;
                echo json_encode($output);


                die();
            }else{

                $output['code'] = 201;
                $output['msg'] = "user not available";
                echo json_encode($output);


                die();
            }


        }
    }

    public function addSound()
    {


        $this->loadModel('Sound');
        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $sound['name'] = $data['name'];
            $sound['description'] = $data['description'];
            $sound['user_id'] = $data['user_id'];
            $sound['uploaded_by'] = "user";
            $sound['created'] = date('Y-m-d H:i:s', time());


            if (isset($data['id'])) {
                $id = $data['id'];


                $this->Sound->id = $id;
                $this->Sound->save($sound);
                $details = $this->Sound->getDetails($id);
                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();

            }
            if (isset($data['audio'])) {




                if (method_exists('Extended', 'fileUploadToS3')) {

                    $base64_audio = $data['audio'];
                    $base64_decode_audio  = base64_decode($base64_audio);

                    $base64_image = $data['thum'];
                    $base64_decode_image  = base64_decode($base64_image);



                    $result_audio = Extended::fileUploadToS3($base64_decode_audio,"mp3");
                    $result_image = Extended::fileUploadToS3($base64_decode_image,"jpeg");




                    if($result['code'] = 200){
                        $audio_file_duration = Extended::getDurationofAudioFile($result_audio['msg']);
                        $sound['audio'] = $result_audio['msg'];
                        $sound['thum'] = $result_image['msg'];
                        $sound['duration'] = $audio_file_duration;

                    }else{

                        $output['code'] = 201;

                        $output['msg'] = $result['msg'];


                        echo json_encode($output);


                        die();

                    }
                } else {

                    $output['code'] = 201;

                    $output['msg'] = "It seems like you do not have extended files. submit ticket on yeahhelp.com for support";


                    echo json_encode($output);


                    die();
                }

                $this->Sound->save($sound);
                $id = $this->Sound->getInsertID();

                $app_slider = $this->Sound->getDetails($id);
                $output['code'] = 200;

                $output['msg'] = $app_slider;


                echo json_encode($output);


                die();
            }
        }





    }



    public function showAllNotifications(){

        $this->loadModel('Notification');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];
            $starting_point = $data['starting_point'];


            $notifications = $this->Notification->getUserNotifications($user_id, $starting_point);


            if (count($notifications) > 0) {

                $output['code'] = 200;

                $output['msg'] = $notifications;


                echo json_encode($output);


                die();

            } else {

                Message::EMPTYDATA();
                die();

            }
        }


    }


    public function showSounds(){

        $this->loadModel('Sound');
        $this->loadModel('Video');
        $this->loadModel('SoundFavourite');
        $this->loadModel('SoundSection');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $starting_point = $data['starting_point'];

            $user_id = 0;

            if(isset($data['user_id'])){

                $user_id = $data['user_id'];

            }

            $sound_section = $this->SoundSection->getAll($starting_point);






            //pr($sound_section);
            // pr($sound_section);

            $i=0;



            foreach($sound_section as $key=>$section) {




                if(count($sound_section[$i]['Sound']) > 0){


                    foreach ($section['Sound'] as $key2 => $sound) {

                        if ($user_id > 0) {

                            $sound_data['user_id'] = $user_id;
                            $sound_data['sound_id'] = $sound['id'];



                            $sound_favourite_detail = $this->SoundFavourite->ifExist($sound_data);


                            if (count($sound_favourite_detail) > 0) {

                                $sound_section[$i]['Sound'][$key2]['favourite'] = 1;

                            } else {

                                $sound_section[$i]['Sound'][$key2]['favourite'] = 0;
                            }
                        } else {

                            $sound_section[$i]['Sound'][$key2]['favourite'] = 0;
                        }


                    }
                    $i++;
                }else{

                    unset($sound_section[$key]);
                    $sound_section = array_values($sound_section);


                }
            }





            $output['code'] = 200;

            $output['msg'] = $sound_section;



            echo json_encode($output);


            die();
        }else{


            Message::EMPTYDATA();
            die();
        }




    }

    public function showSoundsAgainstSection(){

        $this->loadModel('Sound');
        $this->loadModel('SoundFavourite');





        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $starting_point = $data['starting_point'];
            $sound_section_id = $data['sound_section_id'];

            $user_id = 0;

            if(isset($data['user_id'])){

                $user_id = $data['user_id'];

            }

            $sounds = $this->Sound->getSoundsAgainstSection($sound_section_id,$starting_point);


            //pr($sound_section);
            // pr($sound_section);

            foreach($sounds as $key=>$sound) {






                if ($user_id > 0) {

                    $sound_data['user_id'] = $user_id;
                    $sound_data['sound_id'] = $sound['Sound']['id'];

                    $sound_favourite_detail = $this->SoundFavourite->ifExist($sound_data);


                    if (count($sound_favourite_detail) > 0) {

                        $sounds[$key]['Sound']['favourite'] = 1;

                    } else {

                        $sounds[$key]['Sound']['favourite'] = 0;
                    }
                }else{

                    $sounds[$key]['Sound']['favourite'] = 0;
                }







            }





            $output['code'] = 200;

            $output['msg'] = $sounds;



            echo json_encode($output);


            die();
        }else{


            Message::EMPTYDATA();
            die();
        }




    }
    public function showVideosAgainstHashtag(){

        $this->loadModel('HashtagVideo');
        $this->loadModel('HashtagFavourite');
        $this->loadModel('Hashtag');
        $this->loadModel('Video');
        $this->loadModel('VideoLike');
        $this->loadModel('VideoFavourite');
        $this->loadModel('VideoComment');
        $this->loadModel('Follower');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $user_id = 0;

            if(isset($data['user_id'])){

                $user_id = $data['user_id'];

            }

            $starting_point = 0;
            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }

            $hashtag = $data['hashtag'];

            $hashtag_details = $this->Hashtag->ifExist($hashtag);

            if(count($hashtag_details) > 0) {
                $videos = $this->HashtagVideo->getHashtagVideosWithLimit($hashtag_details['Hashtag']['id'],$starting_point);

                $hashtag_views = $this->HashtagVideo->countHashtagViews($hashtag_details['Hashtag']['id']);

            }else{



                Message::EMPTYDATA();
                die();
            }

            if(count($videos) > 0) {


                foreach($videos as $key=>$video){



                    if($user_id > 0) {
                        $video_data['user_id'] = $user_id;
                        $video_data['video_id'] = $video['Video']['id'];
                        $video_data['hashtag_id'] = $hashtag_details['Hashtag']['id'];
                        $video_like_detail = $this->VideoLike->ifExist($video_data);
                        $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);
                        $hashtag_favourite_detail = $this->HashtagFavourite->ifExist($video_data);

                        if (count($video_like_detail) > 0) {

                            $videos[$key]['Video']['like'] = 1;

                        } else {

                            $videos[$key]['Video']['like'] = 0;
                        }

                        if (count($video_favourite_detail) > 0) {

                            $videos[$key]['Video']['favourite'] = 1;

                        } else {

                            $videos[$key]['Video']['favourite'] = 0;
                        }

                        if (count($hashtag_favourite_detail) > 0) {

                            $videos[$key]['Hashtag']['favourite'] = 1;

                        } else {

                            $videos[$key]['Hashtag']['favourite'] = 0;
                        }


                        $follower_details = $this->Follower->ifFollowing($user_id, $video['Video']['user_id']);
                        $following_details = $this->Follower->ifFollowing($video['Video']['user_id'], $user_id);
                        if(count($follower_details) > 0 && count($following_details) > 0){

                            $videos[$key]['Video']['User']['button'] = "Friends";

                        } else   if(count($follower_details) > 0 && count($following_details) < 1){

                            $videos[$key]['Video']['User']['button'] = "following";

                        }else if (count($following_details) > 0){


                            $videos[$key]['Video']['User']['button'] = "follow back";
                        }else{


                            $videos[$key]['Video']['User']['button'] = "follow";
                        }
                    }else{


                        $videos[$key]['Video']['like'] = 0;
                        $videos[$key]['Video']['favourite'] = 0;
                        $videos[$key]['Video']['User']['button'] = "follower";
                        $videos[$key]['Hashtag']['favourite'] = 0;


                    }

                    $comment_count = $this->VideoComment->countComments($video['Video']['id']);
                    $video_likes_count = $this->VideoLike->countLikes($video['Video']['id']);


                    $videos[$key]['Video']['comment_count'] = $comment_count;
                    $videos[$key]['Video']['like_count'] = $video_likes_count;

                }
                $hashtag_videos_count = $this->HashtagVideo->countHashtagVideos($hashtag_details['Hashtag']['id']);



                $output['code'] = 200;

                $output['msg'] = $videos;
                $output['views'] = $hashtag_views[0]['total_sum'];
                $output['videos_count'] = $hashtag_videos_count;


                echo json_encode($output);


                die();
            }else{


                Message::EMPTYDATA();
                die();

            }

        }


    }




    public function showVideosAgainstSound(){

        $this->loadModel('Sound');
        $this->loadModel('Video');
        $this->loadModel('VideoLike');
        $this->loadModel('VideoFavourite');
        $this->loadModel('VideoComment');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $user_id = 0;
            $device_id = $data['device_id'];
            $starting_point = $data['starting_point'];
            $sound_id = $data['sound_id'];

            if(isset($data['user_id'])){

                $user_id = $data['user_id'];

            }





            $videos = $this->Video->getVideosAgainstSoundID($user_id,$device_id,$starting_point,$sound_id);



            if(count($videos) > 0) {


                foreach($videos as $key=>$video){



                    if($user_id > 0) {
                        $video_data['user_id'] = $user_id;
                        $video_data['video_id'] = $video['Video']['id'];
                        $video_like_detail = $this->VideoLike->ifExist($video_data);
                        $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);

                        if (count($video_like_detail) > 0) {

                            $videos[$key]['Video']['like'] = 1;

                        } else {

                            $videos[$key]['Video']['like'] = 0;
                        }

                        if (count($video_favourite_detail) > 0) {

                            $videos[$key]['Video']['favourite'] = 1;

                        } else {

                            $videos[$key]['Video']['favourite'] = 0;
                        }
                    }else{


                        $videos[$key]['Video']['like'] = 0;
                        $videos[$key]['Video']['favourite'] = 0;



                    }

                    $comment_count = $this->VideoComment->countComments($video['Video']['id']);
                    $video_likes_count = $this->VideoLike->countLikes($video['Video']['id']);


                    $videos[$key]['Video']['comment_count'] = $comment_count;
                    $videos[$key]['Video']['like_count'] = $video_likes_count;

                }

                $output['code'] = 200;

                $output['msg'] = $videos;


                echo json_encode($output);


                die();
            }else{


                Message::EMPTYDATA();
                die();

            }

        }


    }



    public function showRelatedVideos(){

        $this->loadModel('Video');
        $this->loadModel('VideoComment');
        $this->loadModel('VideoLike');
        $this->loadModel('VideoFavourite');
        $this->loadModel('Follower');
        $this->loadModel('User');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = 0;
            $device_id = $data['device_id'];
            $starting_point = $data['starting_point'];
            $lang_ids = array();
            $interest_ids = array();

            if(isset($data['user_id'])) {
                $user_id = $data['user_id'];

            }


            // $user_details = $this->User->getUserDetailsFromID($user_id);

            if(APP_STATUS == "demo") {
                $videos = $this->Video->getRelatedVideosDemo($user_id, $device_id, $starting_point);
            }else{



                $videos = $this->Video->getRelatedVideosNotWatched($user_id, $device_id, $starting_point);



                if (count($videos) < 1) {

                    $videos = $this->Video->getRelatedVideosWatched($user_id, $device_id, $starting_point);
                }
            }



            if(count($videos) > 0) {


                foreach($videos as $key=>$video){



                    if($user_id > 0) {
                        $video_data['user_id'] = $user_id;
                        $video_data['video_id'] = $video['Video']['id'];
                        $video_like_detail = $this->VideoLike->ifExist($video_data);
                        $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);

                        if (count($video_like_detail) > 0) {

                            $videos[$key]['Video']['like'] = 1;

                        } else {

                            $videos[$key]['Video']['like'] = 0;
                        }

                        if (count($video_favourite_detail) > 0) {

                            $videos[$key]['Video']['favourite'] = 1;

                        } else {

                            $videos[$key]['Video']['favourite'] = 0;
                        }




                        $follower_details = $this->Follower->ifFollowing($user_id, $video['Video']['user_id']);
                        $following_details = $this->Follower->ifFollowing($video['Video']['user_id'], $user_id);
                        if(count($follower_details) > 0 && count($following_details) > 0){

                            $videos[$key]['User']['button'] = "Friends";

                        } else   if(count($follower_details) > 0 && count($following_details) < 1){

                            $videos[$key]['User']['button'] = "following";

                        }else if (count($following_details) > 0){


                            $videos[$key]['User']['button'] = "follow back";
                        }else{


                            $videos[$key]['User']['button'] = "follow";
                        }
                    }else{


                        $videos[$key]['Video']['like'] = 0;
                        $videos[$key]['Video']['favourite'] = 0;
                        $videos[$key]['User']['button'] = "follow";



                    }

                    $comment_count = $this->VideoComment->countComments($video['Video']['id']);
                    $video_likes_count = $this->VideoLike->countLikes($video['Video']['id']);


                    $videos[$key]['Video']['comment_count'] = $comment_count;
                    $videos[$key]['Video']['like_count'] = $video_likes_count;

                }



                $output['code'] = 200;

                $output['msg'] = $videos;


                echo json_encode($output);


                die();
            }else{

                Message::EMPTYDATA();
                die();
            }

        }


    }




    public function showSuggestedUsers()
    {

        $this->loadModel("User");
        $this->loadModel("Follower");


        if ($this->request->isPost()) {

            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $starting_point = 0;


            if (isset($data['starting_point'])) {

                $starting_point = $data['starting_point'];

            }
            if(isset($data['user_id'])) {

                $user_id = $data['user_id'];

                $followers = $this->Follower->isFollowerOrFollowed($user_id);
                $newarray = array();
                if (count($followers) > 0) {
                    foreach ($followers as $key => $val) {


                        $sender_id = $val['Follower']['sender_id'];
                        $receiver_id = $val['Follower']['receiver_id'];

                        if ($user_id == $sender_id) {

                            $newarray[$key] = $receiver_id;
                        } else {

                            $newarray[$key] = $sender_id;

                        }
                    }
                }
                $users = $this->User->getRecommendedUsers($user_id, $newarray,$starting_point);
            }else{


                $users = $this->User->getRecommendedRandomUsers();
            }


            if(count($users) > 0) {

                foreach($users as $key=>$user){

                    $followers_count = $this->Follower->countFollowers($user['User']['id']);
                    $users[$key]['User']['followers_count'] = $followers_count;

                }
                $output['code'] = 200;

                $output['msg'] = $users;


                echo json_encode($output);


                die();

            }else{
                Message::EMPTYDATA();
                die();


            }
        }
    }


    public function getPromotionalVideo($user_details){

        $this->loadModel('Promotion');
        $this->loadModel('VideoLike');
        $this->loadModel('VideoFavourite');
        $this->loadModel('Follower');
        $this->loadModel('VideoComment');


        $created = date('Y-m-d H:i:s', time());

        if(count($user_details) > 0) {
            $age = Utility::getAge($user_details['User']['dob']);
            $user_id = $user_details['User']['id'];

            $promotion_details = $this->Promotion->getPromotedVideoWhichHasNotBeenWatchedByTheUser($user_id, $user_details['User']['city_id'], $user_details['User']['state_id'], $user_details['User']['country_id'], $user_details['User']['gender'], $age, $created);



            // $log = $this->Promotion->getDataSource()->getLog(false, false);

            if (count($promotion_details) > 0) {



                $video_id = $promotion_details['Video']['id'];
                if($video_id > 0){
                    $promotion_id = $promotion_details['Promotion']['id'];
                    $reach = $promotion_details['Promotion']['reach'];
                    $price = $promotion_details['Promotion']['price'];
                    $video_user_id = $promotion_details['Video']['user_id'];
                    $video_likes_count = $this->VideoLike->countLikes($promotion_details['Video']['id']);
                    $video_like = $video_likes_count;

                    if($reach < $price) {

                        $promo_video['Video'] = $promotion_details['Video'];
                        $promo_video['Sound'] = $promotion_details['Video']['Sound'];
                        $promo_video['User'] = $promotion_details['Video']['User'];
                        $promo_video['Video']['promote'] = 1;
                        $promo_video['Video']['promotion_id'] = $promotion_details['Promotion']['id'];
                        $promo_video['Video']['Promotion'] = $promotion_details['Promotion'];


                        $video_data['user_id'] = $user_id;
                        $video_data['video_id'] = $video_id;
                        $video_like_detail = $this->VideoLike->ifExist($video_data);
                        $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);

                        if (count($video_like_detail) > 0) {

                            $promo_video['Video']['like'] = 1;

                        } else {

                            $promo_video['Video']['like'] = 0;
                        }

                        if (count($video_favourite_detail) > 0) {

                            $promo_video['Video']['favourite'] = 1;

                        } else {

                            $promo_video['Video']['favourite'] = 0;
                        }


                        $follower_details = $this->Follower->ifFollowing($user_id, $video_user_id);
                        $following_details = $this->Follower->ifFollowing($video_user_id, $user_id);

                        if (count($follower_details) > 0 && count($following_details) > 0) {

                            $promo_video['User']['button'] = "Friends";

                        } else if (count($follower_details) > 0 && count($following_details) < 1) {

                            $promo_video['User']['button'] = "following";

                        } else if (count($following_details) > 0) {


                            $promo_video['User']['button'] = "follow back";
                        } else {


                            $promo_video['User']['button'] = "follow";
                        }

                        $comment_count = $this->VideoComment->countComments($video_id);
                        //  $video_likes_count = $this->VideoLike->countLikes($video['Video']['id']);

                        $video_likes_count = $video_like;
                        $followers_count = $this->Follower->countFollowers($video_user_id);

                        $promo_video['Video']['comment_count'] = $comment_count;
                        $promo_video['User']['followers_count'] = $followers_count;
                        $promo_video['Video']['like_count'] = $video_likes_count;
                        //$video_repost_count = $this->Video->getVideoRepostCount($video['Video']['id']);


                        $this->Promotion->id = $promotion_id;
                        $this->Promotion->saveField('reach', $reach + 1);

                        return $promo_video;
                    }
                }else{

                    return false;

                }
            }

            return false;


        }
    }

    public function postVideo(){

        $this->loadModel('Video');
        $this->loadModel('Sound');
        $this->loadModel('Hashtag');
        $this->loadModel('HashtagVideo');
        $this->loadModel('User');
        $this->loadModel('Notification');
        $this->loadModel('Follower');
        $this->loadModel('PushNotification');


        if ($this->request->isPost()) {


            $created = date('Y-m-d H:i:s', time());
            $user_id = $this->request->data('user_id');

            $description = $this->request->data('description');
            $privacy_type = $this->request->data('privacy_type');
            $allow_comments = $this->request->data('allow_comments');
            $allow_duet = $this->request->data('allow_duet');
            $video_id = $this->request->data('video_id');
            $sound_id = $this->request->data('sound_id');
            $hashtags_json = $this->request->data('hashtags_json');
            $users_json = $this->request->data('users_json');
            $duet = $this->request->data('duet');
            $lang_id = $this->request->data('lang_id');
            $interest_id = $this->request->data('interest_id');

            $privacy_type = strtolower($privacy_type);



            $data_hashtag = json_decode($hashtags_json, TRUE);
            $data_users = json_decode($users_json, TRUE);


            $video_userDetails = $this->User->getUserDetailsFromID($user_id);


            if(count($video_userDetails) > 0) {

                $type = "video";


                $sound_details = $this->Sound->getDetails($sound_id);



                if ($video_id > 0) {

                    //duet
                    $video_details = $this->Video->getDetails($video_id);
                    $video_save['duet_video_id'] = $video_details['Video']['id'];
                    $sound_details = $this->Sound->getDetails($video_details['Video']['sound_id']);

                } else {

                    $video_details = array();


                }


                if (MEDIA_STORAGE == "s3") {
                    if (method_exists('Extended', 's3_video_upload')) {

                        $result_video = Extended::s3_video_upload($user_id, $type, $sound_details, $video_details, $duet);

                        if(strlen(CLOUDFRONT_URL) > 5) {
                            $video_url = Utility::getCloudFrontUrl($result_video['video'], "/video");
                            $gif_url = Utility::getCloudFrontUrl($result_video['gif'], "/gif");
                            $thum_url = Utility::getCloudFrontUrl($result_video['thum'], "/thum");
                        }else{


                            $video_url = $result_video['video'];
                            $gif_url = $result_video['gif'];
                            $thum_url = $result_video['thum'];
                        }
                    } else {


                        $output['code'] = 201;

                        $output['msg'] = "It seems like you do not have extended files. submit ticket on yeahhelp.com for support";


                        echo json_encode($output);


                        die();
                    }



                } else {


                    $result_video = Regular::local_video_upload($user_id, $type, $sound_details, $video_details, $duet);

                    $video_url = $result_video['video'];
                    $gif_url = $result_video['gif'];
                    $thum_url = $result_video['thum'];
                }


                $video_save['sound_id'] = $sound_id;
                if (count($result_video) > 0) {
                    $video_duration = Utility::getDurationOfVideoFile($result_video['video']);
                    if (strlen($result_video['audio']) > 2) {

                        $audio_url = Utility::getCloudFrontUrl($result_video['audio'], "/audio");

                        $duration = Utility::getDurationofAudioFile($result_video['audio']);


                        $sound_date['audio'] = $audio_url;
                        $sound_date['duration'] = $duration;
                        $sound_date['thum'] = $video_userDetails['User']['profile_pic'];
                        $sound_date['name'] = "original sound - " . $video_userDetails['User']['username'];
                        $sound_date['uploaded_by'] = "user";

                        $this->Sound->save($sound_date);
                        $sound_id = $this->Sound->getInsertID();
                        $video_save['sound_id'] = $sound_id;
                    }


                    //$filepath_thumb = Utility::multipartFileUpload($user_id, 'thumb',$type);


                    $video_save['gif'] = $gif_url;
                    $video_save['duration'] = $video_duration;
                    $video_save['video'] = $video_url;
                    $video_save['lang_id'] = $lang_id;

                    $video_save['thum'] = $thum_url;
                    $video_save['description'] = $description;
                    $video_save['privacy_type'] = $privacy_type;
                    $video_save['allow_comments'] = $allow_comments;
                    $video_save['allow_duet'] = $allow_duet;
                    $video_save['user_id'] = $user_id;
                    $video_save['interest_id'] = $interest_id;
                    $video_save['created'] = $created;

                    if($user_id < 1){

                        Message::EMPTYDATA();
                        die();


                    }

                    if (!$this->Video->save($video_save)) {
                        echo Message::DATASAVEERROR();
                        die();
                    }

                    $video_id = $this->Video->getInsertID();


                    /**************hashtag save******************/

                    if (count($data_hashtag) > 0) {
                        foreach ($data_hashtag as $key => $value) {

                            $name = strtolower($value['name']);

                            $if_hashtag_exist = $this->Hashtag->ifExist($name);

                            if (count($if_hashtag_exist) < 1) {

                                $hashtag['name'] = $name;
                                $hashtag['lang_id'] = $lang_id;
                                $this->Hashtag->save($hashtag);
                                $hashtag_id = $this->Hashtag->getInsertID();
                                $this->Hashtag->clear();

                                $hashtag_video[$key]['hashtag_id'] = $hashtag_id;
                                $hashtag_video[$key]['video_id'] = $video_id;


                            } else {

                                $hashtag_id = $if_hashtag_exist['Hashtag']['id'];
                                $hashtag_video[$key]['hashtag_id'] = $hashtag_id;
                                $hashtag_video[$key]['video_id'] = $video_id;

                            }


                        }

                        if (count($hashtag_video) > 0) {


                            $this->HashtagVideo->saveAll($hashtag_video);
                        }
                    }

                    /*************************end hashtag save ********************/


                    /**************pushnotification to tagged users******************/


                    if (count($data_users) > 0) {

                        foreach ($data_users as $key => $value) {

                            $user_id = $value['user_id'];

                            $tagged_userDetails = $this->User->getUserDetailsFromID($user_id);
                            $msg = $video_userDetails['User']['username'] . " has tagged you in a video";

                            if (strlen($tagged_userDetails['User']['device_token']) > 8) {
                                $notification['to'] = $tagged_userDetails['User']['device_token'];

                                $notification['notification']['title'] = $msg;
                                $notification['notification']['body'] = "";
                                $notification['notification']['badge'] = "1";
                                $notification['notification']['sound'] = "default";
                                $notification['notification']['icon'] = "";
                                $notification['notification']['type'] = "video_tag";
                                $notification['data']['title'] = $msg;
                                $notification['data']['body'] = '';
                                $notification['data']['icon'] = "";
                                $notification['data']['badge'] = "1";
                                $notification['data']['sound'] = "default";
                                $notification['data']['type'] = "video_tag";
                                $notification['notification']['receiver_id'] =  $tagged_userDetails['User']['id'];
                                $notification['data']['receiver_id'] = $tagged_userDetails['User']['id'];




                                $if_exist = $this->PushNotification->getDetails($tagged_userDetails['User']['id']);

                                if (count($if_exist) > 0) {

                                    $video_updates = $if_exist['PushNotification']['video_updates'];
                                    if ($video_updates > 0) {
                                        Utility::sendPushNotificationToMobileDevice(json_encode($notification));
                                    }
                                }


                                $notification_data['sender_id'] = $video_userDetails['User']['id'];
                                $notification_data['receiver_id'] = $tagged_userDetails['User']['id'];
                                $notification_data['type'] = "video_tag";
                                $notification_data['video_id'] = $video_id;

                                $notification_data['string'] = $msg;
                                $notification_data['created'] = $created;

                                $this->Notification->save($notification_data);

                            }


                        }
                    }
                    /*************************end hashtag save ********************/


                    /**************pushnotification to tagged users******************/
                    $all_followers = $this->Follower->getUserFollowersWithoutLimit($user_id);
                    if (count($all_followers) > 0) {
                        foreach ($all_followers as $key => $value) {

                            $user_id = $value['FollowerList']['id'];
                            $device_token = $value['FollowerList']['device_token'];


                            $msg = $video_userDetails['User']['username'] . " has posted a a video";

                            if (strlen($device_token) > 8) {
                                $notification['to'] = $device_token;

                                $notification['notification']['title'] = $msg;
                                $notification['notification']['body'] = "";
                                $notification['notification']['badge'] = "1";
                                $notification['notification']['sound'] = "default";
                                $notification['notification']['icon'] = "";
                                $notification['notification']['type'] = "video_new_post";
                                $notification['data']['title'] = $msg;
                                $notification['data']['body'] = '';
                                $notification['data']['icon'] = "";
                                $notification['data']['badge'] = "1";
                                $notification['data']['sound'] = "default";
                                $notification['data']['type'] = "video_new_post";
                                $notification['notification']['receiver_id'] =  $value['FollowerList']['id'];
                                $notification['data']['receiver_id'] = $value['FollowerList']['id'];



                                $if_exist = $this->PushNotification->getDetails($user_id);

                                if (count($if_exist) > 0) {

                                    $video_updates = $if_exist['PushNotification']['video_updates'];
                                    if ($video_updates > 0) {
                                        Utility::sendPushNotificationToMobileDevice(json_encode($notification));
                                    }
                                }


                                $notification_data['sender_id'] = $video_userDetails['User']['id'];
                                $notification_data['receiver_id'] = $user_id;
                                $notification_data['type'] = "video_updates";
                                $notification_data['video_id'] = $video_id;

                                $notification_data['string'] = $msg;
                                $notification_data['created'] = $created;

                                $this->Notification->save($notification_data);

                            }


                        }
                    }
                    /*************************end hashtag save ********************/


                    $output = array();

                    $album_details = $this->Video->getDetails($video_id);


                    $output['code'] = 200;
                    $output['msg'] = $album_details;
                    echo json_encode($output);

                }
            }else{
                Message::EMPTYDATA();
                die();

            }


        }

    }



    public function showFollowingVideos(){

        $this->loadModel('Video');
        $this->loadModel('VideoComment');
        $this->loadModel('VideoLike');
        $this->loadModel('VideoFavourite');
        $this->loadModel('Follower');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = 0;
            $device_id = $data['device_id'];
            $starting_point = $data['starting_point'];
            if(isset($data['user_id'])) {

                $user_id = $data['user_id'];
            }

            $following_users = $this->Follower->getUserFollowingWithoutLimit($user_id);

            $ids = array();
            if(count($following_users) > 0) {
                foreach ($following_users as $key => $following) {

                    $ids[$key] = $following['FollowingList']['id'];

                }
            }

            if(count($ids) > 0){
                $videos = $this->Video->getFollowingVideosNotWatched($user_id, $device_id, $starting_point,$ids);


                if (count($videos) < 1) {

                    $videos = $this->Video->getFollowingVideosWatched($user_id, $device_id, $starting_point,$ids);
                }
                if(count($videos) > 0) {

                    foreach ($videos as $key => $video) {

                        if ($user_id > 0) {
                            $video_data['user_id'] = $user_id;
                            $video_data['video_id'] = $video['Video']['id'];
                            $video_like_detail = $this->VideoLike->ifExist($video_data);
                            $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);

                            if (count($video_like_detail) > 0) {

                                $videos[$key]['Video']['like'] = 1;

                            } else {

                                $videos[$key]['Video']['like'] = 0;
                            }

                            if (count($video_favourite_detail) > 0) {

                                $videos[$key]['Video']['favourite'] = 1;

                            } else {

                                $videos[$key]['Video']['favourite'] = 0;
                            }
                        } else {
                            $videos[$key]['Video']['like'] = 0;
                            $videos[$key]['Video']['favourite'] = 0;


                        }
                        $comment_count = $this->VideoComment->countComments($video['Video']['id']);
                        $video_likes_count = $this->VideoLike->countLikes($video['Video']['id']);

                        $videos[$key]['Video']['comment_count'] = $comment_count;
                        $videos[$key]['Video']['like_count'] = $video_likes_count;

                    }

                    $output['code'] = 200;

                    $output['msg'] = $videos;


                    echo json_encode($output);


                    die();
                }else{

                    Message::EMPTYDATA();
                    die();
                }
            }else{

                $output['code'] = 201;

                $output['msg'] = "you are not following anyone yet";


                echo json_encode($output);


                die();

            }

        }


    }
    public function showCountries(){

        $this->loadModel('Country');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $countries = $this->Country->getAll();





            $output['code'] = 200;

            $output['msg'] = $countries;


            echo json_encode($output);


            die();


        }


    }

    public function editProfile()
    {


        $this->loadModel('User');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);













            $user_id = $data['user_id'];




            if(isset($data['first_name'])){


                $user['first_name'] = $data['first_name'];
            }
            if(isset($data['last_name'])){


                $user['last_name'] = $data['last_name'];
            }
            if(isset($data['bio'])){


                $user['bio'] = $data['bio'];
            }
            if(isset($data['website'])){


                $user['website'] = $data['website'];
            }

            if(isset($data['phone'])){


                $user['phone'] = $data['phone'];
            }

            if(isset($data['language_id'])){


                $user['lang_id'] = $data['lang_id'];
            }



            if(isset($data['facebook'])){


                $user['facebook'] = $data['facebook'];
            }

            if(isset($data['instagram'])){


                $user['instagram'] = $data['instagram'];
            }

            if(isset($data['youtube'])){


                $user['youtube'] = $data['youtube'];
            }

            if(isset($data['twitter'])){


                $user['twitter'] = $data['twitter'];
            }
            if(isset($data['username'])){


                $user['username'] = $data['username'];

                $username_exist = $this->User->editIsUsernameAlreadyExist($data['username'], $user_id);
                //$email_exist = $this->User->editIsEmailAlreadyExist($data['email'], $user_id);

                if($username_exist > 0){

                    $output['code'] = 201;
                    $output['msg'] = "username already exist";
                    echo json_encode($output);
                    die();
                }
            }

            if(isset($data['gender'])){

                $user['gender'] = $data['gender'];

            }

            // $phone = $this->User->editIsphoneNoAlreadyExist($data['phone'], $user_id);


            $this->User->id = $user_id;
            $this->User->save($user);


            $output = array();
            $userDetails = $this->User->getUserDetailsFromID($user_id);


            $output['code'] = 200;
            $output['msg'] = $userDetails;
            echo json_encode($output);


        }
    }


    public function logout()
    {


        $this->loadModel('User');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);




            $user_id = $data['user_id'];
            $user['device_token'] = "";
            $user['auth_token'] = "";


            $userDetails = $this->User->getUserDetailsFromID($user_id);
            if(count($userDetails) > 0) {

                $this->User->id = $user_id;
                $this->User->save($user);


                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
            }else{


                Message::EMPTYDATA();
                die();


            }

        }
    }
    public function showAudiencesReach(){

        $this->loadModel('Audience');
        $this->loadModel('User');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $min_age = 0;
            $max_age = 0;
            $gender = "all";
            if(isset($data['min_age'])){

                $min_age = $data['min_age'];
            }

            if(isset($data['max_age'])){

                $max_age = $data['max_age'];
            }

            if(isset($data['gender'])){

                $gender = $data['gender'];
            }



            $locations = $data['locations'];





            if($min_age < 1 && $max_age < 1){

                //it means only location reach

                $total_reach = 0;
                foreach ($locations as $key => $location) {


                    $city_id = $location['city_id'];
                    $country_id = $location['country_id'];
                    $state_id = $location['state_id'];

                    $audience_reach = $this->User->totalAudienceAgainstCityID($city_id);
                    if($location['city_id'] < 1 || $audience_reach < 1 ){

                        $audience_reach = $this->User->totalAudienceAgainstStateID($state_id);

                        if($location['state_id'] < 1 || $audience_reach < 1 ){

                            $audience_reach = $this->User->totalAudienceAgainstCountryID($country_id);
                        }

                    }



                    $total_reach = $audience_reach + $total_reach;
                }

            }else if($gender == "all"){


                $total_reach = 0;
                foreach ($locations as $key => $location) {

                    $city_id = $location['city_id'];
                    $country_id = $location['country_id'];
                    $state_id = $location['state_id'];


                    $audience_reach = $this->User->totalAudienceWithoutGenderAndCity($min_age,$max_age,$city_id);
                    if($city_id < 1 || $audience_reach < 1 ){

                        $audience_reach = $this->User->totalAudienceWithoutGenderAndState($min_age,$max_age,$state_id);

                        if($state_id < 1 || $audience_reach < 1 ){

                            $audience_reach = $this->User->totalAudienceWithoutGenderAndCountry($min_age,$max_age,$country_id);
                        }

                    }



                    $total_reach = $audience_reach[0][0]['total_audience'] + $total_reach;
                }

            }else{

                $total_reach = 0;
                foreach ($locations as $key => $location) {

                    $city_id = $location['city_id'];
                    $country_id = $location['country_id'];
                    $state_id = $location['state_id'];


                    $audience_reach = $this->User->totalAudienceAgainstGenderAndCity($min_age,$max_age,$gender,$city_id);
                    if($city_id < 1 || $audience_reach < 1 ){

                        $audience_reach = $this->User->totalAudienceAgainstGenderAndState($min_age,$max_age,$gender,$state_id);

                        if($state_id < 1 || $audience_reach < 1 ){

                            $audience_reach = $this->User->totalAudienceAgainstGenderAndCountry($min_age,$max_age,$gender,$country_id);
                        }

                    }



                    $total_reach = $audience_reach[0][0]['total_audience'] + $total_reach;
                }


            }






            /*
             *
             *   $location_with_commas = "'" . implode("','", $location_new) . "'";
            if ($gender != "any") {
                    $count = $this->User->totalAudienceAgainstGender($min_age, $max_age, $gender, $location_with_commas);



                }else{

                $count = $this->User->totalAudienceWithoutGender($min_age, $max_age, $location_with_commas);



            }*/



            $output['code'] = 200;

            $output['msg'] = $total_reach;


            echo json_encode($output);


            die();

        }

    }

    public function showLocations()
    {


        $this->loadModel("User");
        $this->loadModel("Country");
        $this->loadModel("City");
        $this->loadModel("State");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);
            $keyword = $data['keyword'];


            // $countries = $this->User->searchLocation($keyword);

            $countries = $this->Country->getCountriesAgainstKeyword($keyword);

            $location = array();
            if(count($countries) > 0) {

                foreach ($countries as $key => $country) {
                    $id = $country[0]['id'];
                    $name = $country[0]['name'];
                    $type = $country[0]['type'];


                    if($type == "country"){

                        $location[$key]['country_id'] = $id;
                        $location[$key]['city_id'] = 0;
                        $location[$key]['state_id'] = 0;
                        $location[$key]['name'] = $name;


                    }else if($type == "state"){


                        $details =  $this->State->getDetails($id);
                        $location[$key]['country_id'] = $details['Country']['id'];
                        $location[$key]['state_id'] = $id;
                        $location[$key]['city_id'] = 0;
                        $location[$key]['name'] = $name.",".$details['Country']['name'];

                    }else if($type == "city"){


                        $details =  $this->City->getDetails($id);


                        $location[$key]['country_id'] = $details['City']['country_id'];
                        $location[$key]['state_id'] = $details['State']['id'];
                        $location[$key]['city_id'] = $id;
                        $location[$key]['name'] = $name.",".$details['Country']['name'];
                    }
                }
            }


            if(count($location) > 0) {

                $output['code'] = 200;

                $output['msg'] = $location;


                echo json_encode($output);


                die();

            }else{

                Message::EMPTYDATA();
                die();
            }
        }
    }

    public function addAudience()
    {

        $this->loadModel("Audience");
        $this->loadModel("AudienceLocation");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $name = $data['name'];
            $user_id = $data['user_id'];
            $locations = $data['locations'];
            $min_age = $data['min_age'];
            $max_age = $data['max_age'];
            $gender = strtolower($data['gender']);
            $created = date('Y-m-d H:i:s', time());


            $audience['name'] = $name;
            $audience['user_id'] = $user_id;

            $audience['min_age'] = $min_age;
            $audience['max_age'] = $max_age;
            $audience['gender'] = $gender;
            $audience['created'] = $created;









            $this->Audience->save($audience);



            $id = $this->Audience->getInsertID();

            if(count($locations) > 0){
                foreach ($locations as $key=>$country){



                    $audience_country[$key]['city_id'] = $country['city_id'];
                    $audience_country[$key]['state_id'] = $country['state_id'];
                    $audience_country[$key]['country_id'] = $country['country_id'];
                    $audience_country[$key]['audience_id'] = $id;
                }

                $this->AudienceLocation->saveAll($audience_country);


            }
            $details = $this->Audience->getDetails($id);

            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);


            die();








        }
    }

    public function deleteAudience(){



        $this->loadModel('Audience');
        $this->loadModel('Promotion');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            //$trip['trip_id'] =

            $id =  $data['id'];
            $created = date('Y-m-d H:i:s', time());
            $details =  $this->Promotion->getActivePromotionAudience($id,$created);


            if(count($details) < 1) {




                $this->Audience->id = $id;
                $this->Audience->delete();




                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();




            }else{

                $output['code'] = 201;

                $output['msg'] = "You cannot delete an audience since your ad is active against this audience.";


                echo json_encode($output);


                die();
            }

        }




    }

    public function showAudiences(){

        $this->loadModel('Audience');
        $this->loadModel('User');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];

            $audiences = $this->Audience->getUserAudiences($user_id);





            $output['code'] = 200;

            $output['msg'] = $audiences;


            echo json_encode($output);


            die();


        }


    }

    public function sendGift()
    {
        $this->loadModel("User");
        $this->loadModel("PushNotification");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if (in_array("GiftSend", App::objects('Model'))) {
                $this->loadModel("GiftSend");
                $this->loadModel("Gift");
            }else{

                $output['code'] = 201;

                $output['msg'] = "Contact hello@qboxus.com to get these premium features";


                echo json_encode($output);


                die();
            }

            $sender_id = $data['sender_id'];
            $receiver_id = $data['receiver_id'];
            $gift_id = $data['gift_id'];
            $gift_count = $data['gift_count'];




            $gift_details =  $this->Gift->getDetails($gift_id);

            if(count($gift_details)< 1){


                Message::EMPTYDATA();
                die();
            }


            $gift_data['image'] = $gift_details['Gift']['image'];
            $gift_data['coin'] = $gift_details['Gift']['coin'] * $gift_count;
            $gift_data['id'] = $gift_details['Gift']['id'];
            $gift_data['title'] = $gift_details['Gift']['title'];
            $gift_data['sender_id'] = $sender_id;
            $gift_data['receiver_id'] = $receiver_id;

            $receiver_details =  $this->User->getUserDetailsFromID($receiver_id);
            $sender_details =  $this->User->getUserDetailsFromID($sender_id);


            if(count($sender_details) > 0){

                $total_coins_sender = $sender_details['User']['wallet'];
                $total_coins_receiver = $receiver_details['User']['wallet'];

                if($total_coins_sender < $gift_data['coin']){


                    $output['code'] = 201;
                    $output['msg'] = "You do not have enough coins to send gift";
                    echo json_encode($output);


                    die();
                }

            }

            $this->GiftSend->save($gift_data);


            /*********************************START NOTIFICATION******************************/

            $notification['to'] = $receiver_details['User']['device_token'];




            $notification['notification']['title'] = "You have received a gift";
            $notification['notification']['body'] = $gift_details['Gift']['title']." worth ".$gift_data['coin']." coins";
            $notification['notification']['user_id'] = $sender_details['User']['id'];
            $notification['notification']['image'] = $sender_details['User']['profile_pic'];
            $notification['notification']['name'] = $sender_details['User']['username'];
            $notification['notification']['badge'] = "1";
            $notification['notification']['sound'] = "default";
            $notification['notification']['icon'] = "";
            $notification['notification']['type'] = "gift";

            $notification['data']['title'] = "You have received a gift";
            $notification['data']['name'] = $sender_details['User']['username'];
            $notification['data']['body'] = $gift_details['Gift']['title']." worth ".$gift_data['coin']." coins";
            $notification['data']['icon'] = "";
            $notification['data']['badge'] = "1";
            $notification['data']['sound'] = "default";
            $notification['data']['type'] = "gift";
            $notification['data']['user_id'] = $sender_details['User']['id'];
            $notification['data']['image'] = $sender_details['User']['profile_pic'];



            $if_exist = $this->PushNotification->getDetails($receiver_details['User']['id']);
            if(count($if_exist) > 0) {

                $likes = $if_exist['PushNotification']['direct_messages'];
                if($likes > 0) {
                    Utility::sendPushNotificationToMobileDevice(json_encode($notification));
                }
            }

            /*********************************END NOTIFICATION******************************/


            $this->User->id = $sender_details['User']['id'];
            $this->User->saveField('wallet',$total_coins_sender - $gift_data['coin']);


            $this->User->id = $receiver_details['User']['id'];
            $this->User->saveField('wallet',$total_coins_receiver + $gift_data['coin']);


            $sender_details =  $this->User->getUserDetailsFromID($sender_id);

            $output['code'] = 200;
            $output['msg'] = $sender_details;
            echo json_encode($output);


            die();
        }

    }

    public function withdrawRequest()
    {



        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            if (in_array("WithdrawRequest", App::objects('Model'))) {
                $this->loadModel('WithdrawRequest');

            }else{

                $output['code'] = 201;

                $output['msg'] = "Contact hello@qboxus.com to get these premium features";


                echo json_encode($output);


                die();
            }
            $withdraw_data['user_id'] = $data['user_id'];
            $withdraw_data['amount'] = $data['amount'];

            $withdraw_data['created'] = date('Y-m-d H:i:s', time());



            $details = $this->WithdrawRequest->getUserPendingWithdrawRequest($data['user_id']);

            if(count($details) > 0 ){

                $output['code'] = 201;
                $output['msg'] = "You have already requested a payout.";
                echo json_encode($output);
                die();

            }




            $this->WithdrawRequest->save($withdraw_data);

            $id = $this->WithdrawRequest->getInsertID();

            $output = array();
            $details = $this->WithdrawRequest->getDetails($id);
            $this->User->id =  $data['user_id'];
            $this->User->saveField('wallet',0);



            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);
            die();





        }
    }



    public function addStory()
    {



        $this->loadModel('Story');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $created = date('Y-m-d H:i:s', time());
            $type = $data['type'];
            $attachment = $data['attachment'];
            $video_duration = Utility::getDurationOfVideoFile($attachment);

            $user_id = $data['user_id'];
            $datetime = $data['datetime'];

            $post_data['type'] = $type;
            $post_data['user_id'] = $user_id;
            $post_data['attachment'] = $attachment;
            $post_data['created'] = $datetime;
            $post_data['duration'] = $video_duration;


            $this->Story->save($post_data);
            $id = $this->Story->getInsertID();










            $details = $this->Story->getDetails($id);

            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);
            die();


        }
    }

    public function showStory(){

        $this->loadModel('Story');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);



            $details = array();
            $date = $data['date'];
            //$date_readable =  date("Y-m-d", strtotime($date));

            $story_others = $this->Story->getOtherStories($date);

            if(isset($data['user_id'])){


                $details = $this->Story->getUserStories($data['user_id'],$date);
            }



            $output['code'] = 200;

            $output['msg']['mystory'] = $details;
            $output['msg']['other'] = $story_others;


            echo json_encode($output);


            die();


        }


    }


    public function deleteStory(){

        $this->loadModel('Story');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $id = $data['id'];

            $this->Story->id = $id;
            $this->Story->delete();





            $output['code'] = 200;

            $output['msg'] = "success";


            echo json_encode($output);


            die();


        }


    }
    public function addPlaylist()
    {



        $this->loadModel('Playlist');
        $this->loadModel('PlaylistVideo');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $created = date('Y-m-d H:i:s', time());
            $name = $data['name'];
            $user_id = $data['user_id'];
            $videos = $data['videos'];
            $post_data['name'] = $name;
            $post_data['user_id'] = $user_id;
            $post_data['created'] = $created;

            $playlist_details = $this->Playlist->ifExist($name);

            if(count($playlist_details) > 0){

                $output['code'] = 201;
                $output['msg'] = "Playlist already exist with the same name";
                echo json_encode($output);
                die();

            }

            $this->Playlist->save($post_data);
            $id = $this->Playlist->getInsertID();


            if(count($videos) > 0){


                foreach ($videos as $key=>$val){


                    $post_videolist[$key]['video_id'] =  $val['video_id'];
                    $post_videolist[$key]['order'] = $val['order'];
                    $post_videolist[$key]['playlist_id'] = $id;

                }

                $this->PlaylistVideo->saveAll($post_videolist);


            }







            $details = $this->Playlist->getDetails($id);

            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);
            die();


        }
    }

    public function showPlaylists(){

        $this->loadModel('Playlist');
        $this->loadModel('VideoLike');
        $this->loadModel('VideoFavourite');
        $this->loadModel('Follower');
        $this->loadModel('VideoComment');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $id = $data['id'];

            $details = $this->Playlist->getDetails($id);



            if(count($details) > 0) {


                $i = 0;
                foreach($details['PlaylistVideo'] as $key=>$val){

                    $video_id = $val['video_id'];
                    $video_user_id = $val['Video']['user_id'];

                    $details['PlaylistVideo'][$key]['Video']['like'] = 0;
                    $details['PlaylistVideo'][$key]['Video']['favourite'] = 0;

                    if (isset($data['user_id'])) {

                        $user_id = $data['user_id'];




                        if ($user_id != $video_user_id) {

                            $follower_details = $this->Follower->ifFollowing($user_id, $video_user_id);

                            if (count($follower_details) > 0) {

                                $details['PlaylistVideo'][$key]['Video']['User']['button'] = "unfollow";

                            } else {


                                $details['PlaylistVideo'][$key]['Video']['User']['button'] = "follow";
                            }


                            $video_data['user_id'] = $user_id;
                            $video_data['video_id'] = $video_id;
                            $video_like_detail = $this->VideoLike->ifExist($video_data);
                            $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);

                            if (count($video_like_detail) > 0) {

                                $details['PlaylistVideo'][$key]['Video']['like'] = 1;

                            }

                            if (count($video_favourite_detail) > 0) {

                                $details['PlaylistVideo'][$key]['Video']['favourite'] = 1;

                            }


                        }

                        $video_like_count = $this->VideoLike->countLikes($video_id);
                        $video_comment_count = $this->VideoComment->countComments($video_id);
                        $details['PlaylistVideo'][$key]['Video']['like_count'] = $video_like_count;
                        $details['PlaylistVideo'][$key]['Video']['comment_count'] = $video_comment_count;

                    }

                }
                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();
            }else{


                Message::EMPTYDATA();
                die();
            }

        }


    }


    public function deletePlaylist(){

        $this->loadModel('Playlist');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $id = $data['id'];

            $this->Playlist->delete($id,true);





            $output['code'] = 200;

            $output['msg'] = "success";


            echo json_encode($output);


            die();


        }


    }

    public function deletePlaylistVideo(){

        $this->loadModel('Playlist');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $id = $data['id'];
            $this->PlaylistVideo->id = $id;
            $this->PlaylistVideo->delete();


            $output['code'] = 200;

            $output['msg'] = "success";


            echo json_encode($output);


            die();

        }
    }

    public function addGroup()
    {



        $this->loadModel('Group');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $created = date('Y-m-d H:i:s', time());
            $post_data['title'] = $data['title'];
            $post_data['user_id'] = $data['user_id'];
            $post_data['created'] = $created;


            if(isset($data['id'])){


                $group_details = $this->Group->getDetails($data['id']);

                if(isset($data['image'])) {
                    $image_db = $group_details['Group']['image'];
                    if (strlen($image_db) > 5) {
                        @unlink($image_db);

                    }

                    $image = $data['image'];
                    $folder_url = UPLOADS_FOLDER_URI;

                    $filePath = Utility::uploadFileintoFolderDir($image, $folder_url);
                    $post_data['image'] = $filePath;

                }


                $this->Group->id = $data['id'];
                $this->Group->save($post_data);

                $group_details = $this->Group->getDetails($data['id']);

                $output['code'] = 200;
                $output['msg'] = $group_details;
                echo json_encode($output);
                die();
            }


            if(isset($data['image'])) {


                $image = $data['image'];
                $folder_url = UPLOADS_FOLDER_URI;

                $filePath = Utility::uploadFileintoFolderDir($image, $folder_url);
                $post_data['image'] = $filePath;

            }

            $this->Group->save($post_data);
            $id = $this->Group->getInsertID();
            $group_details = $this->Group->getDetails($id);

            $output['code'] = 200;
            $output['msg'] = $group_details;
            echo json_encode($output);
            die();


        }
    }


    public function inviteUserToGroup(){

        $this->loadModel('Notification');
        $this->loadModel('Group');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $created = date('Y-m-d H:i:s', time());
            $group_id = $data['group_id'];
            $user_id = $data['user_id'];



            $group_details  = $this->Group->getDetails($group_id);
            $receiver_details  = $this->User->getUserDetailsFromID($user_id);

            if(count($group_details) > 0){
                $title = $group_details['Group']['title'];
                $msg = "You have been invite in ".$title." group";

                $group_user_id = $group_details['Group']['user_id'];
                $group_id = $group_details['Group']['id'];

                $notification_data['sender_id'] = $group_user_id;
                $notification_data['receiver_id'] = $user_id;
                $notification_data['group_id'] = $group_id;
                $notification_data['type'] = "group_invite";


                $notification_data['string'] = $msg;
                $notification_data['created'] = $created;


                $this->Notification->save($notification_data);
                $id = $this->Notification->getInsertID();
                $notification['to'] = $receiver_details['User']['device_token'];

                $notification['notification']['title'] =  "group invitation";
                $notification['notification']['body'] = $msg;
                $notification['notification']['badge'] = "1";
                $notification['notification']['sound'] = "default";
                $notification['notification']['icon'] = "";
                $notification['notification']['type'] = "live";
                $notification['notification']['user_id'] = $user_id;
                //$notification['notification']['name'] = $user_details['User']['first_name']." ".$user_details['User']['last_name'];
                // $notification['notification']['image'] = $user_details['User']['profile_pic'];

                $notification['data']['title'] = "group invitation";
                $notification['data']['body'] = $msg;
                $notification['data']['icon'] = "";
                $notification['data']['badge'] = "1";
                $notification['data']['sound'] = "default";
                $notification['data']['type'] = "live";
                $notification['data']['user_id'] = $user_id;
                // $notification['data']['receiver_id'] = $receiver_id;
                // $notification['notification']['receiver_id'] = $receiver_id;




                Utility::sendPushNotificationToMobileDevice(json_encode($notification));
                $details  = $this->Notification->getDetails($id);
                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();


            }


        }
    }


    public function acceptGroupInvite()
    {



        $this->loadModel('Notification');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $id = $data['id'];
            $status = $data['status'];





            $details = $this->Notification->getDetails($id);

            if(count($details) > 0 ) {


                $this->Notification->id = $id;
                $this->Notification->saveField('status', $status);
                $details = $this->Notification->getDetails($id);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();


            }


        }
    }

    public function addPayout()
    {



        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $withdraw_data['user_id'] = $data['user_id'];
            $withdraw_data['wallet_address'] = $data['wallet_address'];





            $details = $this->User->getUserDetailsFromID($data['user_id']);

            if(count($details) > 0 ) {


                $this->User->id = $data['user_id'];
                $this->User->saveField('wallet_address', $data['wallet_address']);
                $details = $this->User->getUserDetailsFromID($data['user_id']);


                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);
                die();


            }


        }
    }

    public function addPromotion()
    {

        $this->loadModel("Promotion");




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $video_id = $data['video_id'];
            $user_id = $data['user_id'];
            $destination = $data['destination'];
            $price = $data['price'];

            $website_url = $data['website_url'];
            $audience_id = $data['audience_id'];
            $start_datetime = $data['start_datetime'];
            $end_datetime = $data['end_datetime'];
            $active = 0;
            $created = date('Y-m-d H:i:s', time());



            $audience['video_id'] = $video_id;
            $audience['user_id'] = $user_id;
            $audience['destination'] = $destination;
            $audience['price'] = $price;
            $audience['website_url'] = $website_url;
            $audience['audience_id'] = $audience_id;
            $audience['start_datetime'] = $start_datetime;
            $audience['end_datetime'] = $end_datetime;
            $audience['active'] = $active;
            $audience['created'] = $created;









            $this->Promotion->save($audience);



            $id = $this->Promotion->getInsertID();



            $details = $this->Promotion->getDetails($id);


            $output['code'] = 200;
            $output['msg'] = $details;
            echo json_encode($output);


            die();








        }
    }

    public function learnMoreTap()
    {

        $this->loadModel("Promotion");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $promotion_id = $data['promotion_id'];
            $details = $this->Promotion->getDetails($promotion_id);

            if(count($details) > 0 ){

                $destination_tap =  $details['Promotion']['destination_tap'];
                $this->Promotion->id = $promotion_id;
                $this->Promotion->saveField('destination_tap',$destination_tap + 1);



                $details = $this->Promotion->getDetails($promotion_id);

                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


                die();

            }else{


                Message::EMPTYDATA();
                die();
            }






        }
    }
    /*public function addDeviceData()
    {


        $this->loadModel('User');
        $this->loadModel('PushNotification');
        $this->loadModel('PrivacySetting');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user['device_token'] = $data['device_token'];
            $user['ip'] = $data['ip'];
            $user['device'] = $data['device'];
            $user['version'] = $data['version'];
            $ipdat = @json_decode(file_get_contents(
                "http://www.geoplugin.net/json.gp?ip=" .  $data['ip']));


            if(count($ipdat) > 0 ) {



                $user['city'] = strtolower($ipdat->geoplugin_city);
                $user['country'] = strtolower($ipdat->geoplugin_countryName);
            }
            $user_id = $data['user_id'];


            $userDetails = $this->User->getUserDetailsFromID($user_id);
            if(count($userDetails) > 0) {

                $this->User->id = $user_id;
                $this->User->save($user);
                $if_exist = $this->PushNotification->getDetails($user_id);


                if(count($if_exist) < 1) {
                    $notification['likes'] = 1;
                    $notification['comments'] = 1;
                    $notification['new_followers'] = 1;
                    $notification['mentions'] = 1;
                    $notification['direct_messages'] = 1;
                    $notification['video_updates'] = 1;
                    $notification['id'] = $user_id;

                    $this->PushNotification->save($notification);
                }

                $if_exist = $this->PrivacySetting->getDetails($user_id);
                if(count($if_exist) < 1) {
                    $settings['videos_download'] = 1;
                    $settings['videos_repost'] = 1;
                    $settings['direct_message'] = "everyone";
                    $settings['duet'] = "everyone";
                    $settings['liked_videos'] = "me";
                    $settings['video_comment'] = "everyone";
                    $settings['id'] = $user_id;

                    $this->PrivacySetting->save($settings);
                }
                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
            }else{


                Message::EMPTYDATA();
                die();


            }

        }
    }
*/
    public function addDeviceData()
    {


        $this->loadModel('User');
        $this->loadModel('Country');
        $this->loadModel('State');
        $this->loadModel('City');
        // $this->loadModel('UserOnline');
        $this->loadModel('PushNotification');
        $this->loadModel('PrivacySetting');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user['device_token'] = $data['device_token'];
            $user['ip'] = $data['ip'];
            $user['device'] = $data['device'];
            $user['version'] = $data['version'];
            $created = date('Y-m-d H:i:s', time());

            $ipdat =   Utility::getLocationFromIP($data['ip']);



            $state_id = 0;
            $country_id = 0;
            $city_id = 0;
            if(count($ipdat) > 0 ) {

                /*echo 'Country Name: ' . $ipdat->geoplugin_countryName . "\n";
                echo 'City Name: ' . $ipdat->geoplugin_city . "\n";
                echo 'Continent Name: ' . $ipdat->geoplugin_continentName . "\n";
                echo 'Latitude: ' . $ipdat->geoplugin_latitude . "\n";
                echo 'Longitude: ' . $ipdat->geoplugin_longitude . "\n";
                echo 'Currency Symbol: ' . $ipdat->geoplugin_currencySymbol . "\n";
                echo 'Currency Code: ' . $ipdat->geoplugin_currencyCode . "\n";
                echo 'Timezone: ' . $ipdat->geoplugin_timezone;*/
                $state_name = strtolower($ipdat['geoplugin_region']);
                $state_short_name = strtolower($ipdat['geoplugin_regionCode']);
                $user['city'] = strtolower($ipdat['geoplugin_city']);
                $user['country'] = strtolower($ipdat['geoplugin_countryName']);
                $short_country_name = strtolower($ipdat['geoplugin_countryCode']);



                $country_details = $this->Country->getCountryAgainstName($user['country']);
                $country_details_short_name = $this->Country->getCountryAgainstShortName($short_country_name);





                if(count($country_details) > 0){

                    $country_id =  $country_details['Country']['id'];



                }else    if(count($country_details_short_name) > 0){

                    $country_id =  $country_details_short_name['Country']['id'];

                }

                $state_details = $this->State->getStateAgainstName($state_name,$country_id);
                $state_short_details = $this->State->getStateAgainstShortName($state_short_name,$country_id);

                if(count($state_details) > 0){


                    $state_id =  $state_details['State']['id'];


                }else if(count($state_short_details) > 0 ){

                    $state_id = $state_short_details['State']['id'];
                }
                $city_details = $this->City->getCityAgainstName($user['city'],$state_id,$country_id);
                if(count($city_details) > 0){


                    $city_id =  $city_details['City']['id'];


                }
            }
            $user_id = $data['user_id'];

            $user['country_id'] = $country_id;
            $user['state_id'] = $state_id;
            $user['city_id'] = $city_id;
            $userDetails = $this->User->getUserDetailsFromID($user_id);
            if(count($userDetails) > 0) {

                $this->User->id = $user_id;
                $this->User->save($user);

                $if_exist = $this->PushNotification->getDetails($user_id);


                if(count($if_exist) < 1) {
                    $notification['likes'] = 1;
                    $notification['comments'] = 1;
                    $notification['new_followers'] = 1;
                    $notification['mentions'] = 1;
                    $notification['direct_messages'] = 1;
                    $notification['video_updates'] = 1;
                    $notification['id'] = $user_id;

                    $this->PushNotification->save($notification);
                }

                $if_exist = $this->PrivacySetting->getDetails($user_id);
                if(count($if_exist) < 1) {
                    $settings['videos_download'] = 1;
                    $settings['videos_repost'] = 1;
                    $settings['direct_message'] = "everyone";
                    $settings['duet'] = "everyone";
                    $settings['liked_videos'] = "me";
                    $settings['video_comment'] = "everyone";
                    $settings['id'] = $user_id;

                    $this->PrivacySetting->save($settings);
                }
                //$user_online['user_id'] = $user_id;
                //$user_online['created'] = $created;
                //$this->UserOnline->save($user_online);

                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
            }else{


                Message::EMPTYDATA();
                die();


            }

        }
    }


    public function addPhoneDeviceData($device_token,$ip,$device,$version,$user_id)
    {


        $this->loadModel('User');
        $this->loadModel('Country');
        $this->loadModel('State');
        $this->loadModel('City');
        // $this->loadModel('UserOnline');
        $this->loadModel('PushNotification');
        $this->loadModel('PrivacySetting');





        $json = file_get_contents('php://input');
        $data = json_decode($json, TRUE);

        $user['device_token'] = $device_token;
        $user['ip'] = $ip;
        $user['device'] = $device;
        $user['version'] = $version;
        $created = date('Y-m-d H:i:s', time());

        $ipdat =   Utility::getLocationFromIP($ip);



        $state_id = 0;
        $country_id = 0;
        $city_id = 0;
        if(count($ipdat) > 0 ) {

            /*echo 'Country Name: ' . $ipdat->geoplugin_countryName . "\n";
            echo 'City Name: ' . $ipdat->geoplugin_city . "\n";
            echo 'Continent Name: ' . $ipdat->geoplugin_continentName . "\n";
            echo 'Latitude: ' . $ipdat->geoplugin_latitude . "\n";
            echo 'Longitude: ' . $ipdat->geoplugin_longitude . "\n";
            echo 'Currency Symbol: ' . $ipdat->geoplugin_currencySymbol . "\n";
            echo 'Currency Code: ' . $ipdat->geoplugin_currencyCode . "\n";
            echo 'Timezone: ' . $ipdat->geoplugin_timezone;*/
            $state_name = strtolower($ipdat['geoplugin_region']);
            $state_short_name = strtolower($ipdat['geoplugin_regionCode']);
            $user['city'] = strtolower($ipdat['geoplugin_city']);
            $user['country'] = strtolower($ipdat['geoplugin_countryName']);
            $short_country_name = strtolower($ipdat['geoplugin_countryCode']);



            $country_details = $this->Country->getCountryAgainstName($user['country']);
            $country_details_short_name = $this->Country->getCountryAgainstShortName($short_country_name);





            if(count($country_details) > 0){

                $country_id =  $country_details['Country']['id'];



            }else    if(count($country_details_short_name) > 0){

                $country_id =  $country_details_short_name['Country']['id'];

            }

            $state_details = $this->State->getStateAgainstName($state_name,$country_id);
            $state_short_details = $this->State->getStateAgainstShortName($state_short_name,$country_id);

            if(count($state_details) > 0){


                $state_id =  $state_details['State']['id'];


            }else if(count($state_short_details) > 0 ){

                $state_id = $state_short_details['State']['id'];
            }
            $city_details = $this->City->getCityAgainstName($user['city'],$state_id,$country_id);
            if(count($city_details) > 0){


                $city_id =  $city_details['City']['id'];


            }
        }
        $user_id = $user_id;

        $user['country_id'] = $country_id;
        $user['state_id'] = $state_id;
        $user['city_id'] = $city_id;

        $userDetails = $this->User->getUserDetailsFromID($user_id);
        if(count($userDetails) > 0) {

            $this->User->id = $user_id;
            $this->User->save($user);

            $if_exist = $this->PushNotification->getDetails($user_id);


            if(count($if_exist) < 1) {
                $notification['likes'] = 1;
                $notification['comments'] = 1;
                $notification['new_followers'] = 1;
                $notification['mentions'] = 1;
                $notification['direct_messages'] = 1;
                $notification['video_updates'] = 1;
                $notification['id'] = $user_id;

                $this->PushNotification->save($notification);
            }

            $if_exist = $this->PrivacySetting->getDetails($user_id);
            if(count($if_exist) < 1) {
                $settings['videos_download'] = 1;
                $settings['videos_repost'] = 1;
                $settings['direct_message'] = "everyone";
                $settings['duet'] = "everyone";
                $settings['liked_videos'] = "me";
                $settings['video_comment'] = "everyone";
                $settings['id'] = $user_id;

                $this->PrivacySetting->save($settings);
            }
            //$user_online['user_id'] = $user_id;
            //$user_online['created'] = $created;
            //$this->UserOnline->save($user_online);

            $output = array();
            $userDetails = $this->User->getUserDetailsFromID($user_id);


            return $userDetails;
        }
        return true;

    }
    public function addUserImage()
    {


        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];
            $extension = $data['extension'];

            $userDetails = $this->User->getUserDetailsFromID($user_id);


            if (isset($data['profile_pic'])) {


                $image_db = $userDetails['User']['profile_pic'];
                if (strlen($image_db) > 5) {
                    @unlink($image_db);

                }

                $image = $data['profile_pic'];
                $folder_url = UPLOADS_FOLDER_URI;

                $filePath = Utility::uploadFileintoFolderDir($image, $folder_url,$extension);
                $user['profile_pic'] = $filePath;


                $image_db = $userDetails['User']['profile_pic'];
                if (strlen($image_db) > 5) {
                    @unlink($image_db);

                }


                $image = $data['profile_pic_small'];
                $folder_url = UPLOADS_FOLDER_URI;

                $filePath = Utility::uploadFileintoFolder($user_id, $image, $folder_url);
                $user['profile_pic_small'] = $filePath;


                $this->User->id = $user_id;
                if (!$this->User->save($user)) {
                    echo Message::DATASAVEERROR();
                    die();
                }


                $output = array();
                $userDetails = $this->User->getUserDetailsFromID($user_id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);


            } else {

                $output['code'] = 201;
                $output['msg'] = "please send the correct image";
                echo json_encode($output);
            }


        }
    }

    public function userVerificationRequest()
    {


        $this->loadModel('VerificationRequest');
        $this->loadModel('User');


        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];
            $created = date('Y-m-d H:i:s', time());
            $details = $this->VerificationRequest->getVerificationDetailsAgainstUserID($user_id);



            if (isset($data['attachment'])) {


                $attachment = $data['attachment'];
                $folder_url = UPLOADS_FOLDER_URI;

                $filePath = Utility::uploadFileintoFolder($user_id, $attachment, $folder_url);

                $verification_data['user_id'] = $user_id;
                $verification_data['attachment'] = $filePath;
                $verification_data['update_time']  = $created;

                if(count($details) > 0) {
                    $attachment = $details['VerificationRequest']['attachment'];
                    if (strlen($attachment) > 5) {
                        @unlink($attachment);

                    }



                    $this->VerificationRequest->id = $details['VerificationRequest']['id'];
                    $this->VerificationRequest->save($verification_data);
                    $id = $details['VerificationRequest']['id'];

                }else{

                    $verification_data['created']  = $created;
                    $this->VerificationRequest->save($verification_data);
                    $id = $this->VerificationRequest->getInsertID();
                }



                $output = array();
                $details = $this->VerificationRequest->getDetails($id);
                $user_details = $this->User->getUserDetailsFromID($user_id);
                if(count($user_details) > 0) {
                    $verification_details = $this->VerificationRequest->getVerificationDetailsAgainstUserID($user_details['User']['id']);

                    if (count($verification_details) > 0) {

                        $user_details['User']['verification_applied'] = 1;
                    }else{

                        $user_details['User']['verification_applied'] = 0;

                    }
                }

                $output['code'] = 200;
                $output['msg'] = $details;
                echo json_encode($output);


            }else{

                $output['code'] = 201;
                $output['msg'] = "please send the correct image";
                echo json_encode($output);
            }



        }
    }
    public function search()
    {



        $this->loadModel("Video");
        $this->loadModel("VideoLike");
        $this->loadModel("VideoFavourite");
        $this->loadModel("SoundFavourite");
        $this->loadModel("User");
        $this->loadModel("Sound");
        $this->loadModel("Hashtag");
        $this->loadModel("HashtagVideo");
        $this->loadModel("HashtagFavourite");

        $this->loadModel("Follower");


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $keyword = $data['keyword'];
            $type = $data['type'];
            $starting_point = $data['starting_point'];

            $users = array();
            $videos = array();
            $sounds = array();
            $user_id = 0;

            if(isset($data['user_id'])){

                $user_id = $data['user_id'];
            }

            if($type == "user"){


                $users = $this->User->getSearchResults($keyword,$starting_point,$user_id);
                if(count($users) > 0){
                    foreach ($users as $key=>$val){

                        $count_followers =  $this->Follower->countFollowers($val['User']['id']);
                        $video_count = $this->Video->getUserVideosCount($val['User']['id']);
                        $users[$key]['User']['followers_count'] = $count_followers;
                        $users[$key]['User']['video_count'] = $video_count;


                    }


                }


                $searchData = $users;

            }else   if($type == "video"){


                $videos = $this->Video->getSearchResults($keyword,$starting_point,$user_id);
                if(count($videos) > 0){
                    foreach ($videos as $key=>$val){

                        if($user_id > 0) {
                            $video_data['user_id'] = $user_id;
                            $video_data['video_id'] = $val['Video']['id'];
                            $video_like_detail = $this->VideoLike->ifExist($video_data);
                            $video_favourite_detail = $this->VideoFavourite->ifExist($video_data);

                            if (count($video_like_detail) > 0) {

                                $videos[$key]['Video']['like'] = 1;

                            } else {

                                $videos[$key]['Video']['like'] = 0;
                            }

                            if (count($video_favourite_detail) > 0) {

                                $videos[$key]['Video']['favourite'] = 1;

                            } else {

                                $videos[$key]['Video']['favourite'] = 0;
                            }
                        }else{


                            $videos[$key]['Video']['like'] = 0;
                            $videos[$key]['Video']['favourite'] = 0;



                        }

                        $video_like_count = $this->VideoLike->countLikes($val['Video']['id']);
                        $videos[$key]['Video']['like_count'] = $video_like_count;


                    }


                }


                $searchData = $videos;

            }else  if($type == "sound"){


                $sounds = $this->Sound->getSearchResults($keyword,$starting_point);

                if(count($sounds) > 0) {
                    foreach ($sounds as $key => $val) {

                        if ($user_id > 0) {
                            $sound_data['user_id'] = $user_id;
                            $sound_data['sound_id'] = $val['Sound']['id'];

                            $sound_favourite_detail = $this->SoundFavourite->ifExist($sound_data);


                            if (count($sound_favourite_detail) > 0) {

                                $sounds[$key]['Sound']['favourite'] = 1;

                            } else {

                                $sounds[$key]['Sound']['favourite'] = 0;
                            }
                        } else {


                            $sounds[$key]['Sound']['favourite'] = 0;


                        }
                    }
                }
                $searchData = $sounds;


            }else  if($type == "hashtag"){


                $hashtags = $this->Hashtag->getSearchResults($keyword,$starting_point);

                if(count($hashtags) > 0) {
                    foreach ($hashtags as $key => $hashtag) {

                        $hashtag_data['hashtag_id'] = $hashtag['Hashtag']['id'];
                        $hashtag_data['user_id'] = $user_id;
                        $hashtag_views = $this->HashtagVideo->countHashtagViews($hashtag['Hashtag']['id']);
                        $hashtag_videos_count = $this->HashtagVideo->countHashtagVideos($hashtag['Hashtag']['id']);

                        $hashtags[$key]['Hashtag']['videos_count'] = $hashtag_videos_count;
                        $hashtags[$key]['Hashtag']['views'] = $hashtag_views[0]['total_sum'];
                        $hashtag_favourite_detail = $this->HashtagFavourite->ifExist($hashtag_data);

                        if (count($hashtag_favourite_detail) > 0) {

                            $hashtags[$key]['Hashtag']['favourite'] = 1;

                        } else {

                            $hashtags[$key]['Hashtag']['favourite'] = 0;
                        }

                    }



                }
                $searchData = $hashtags;

            }else if($type == "following"){


                $following = $this->Follower->searchFollowing($keyword,$starting_point,$user_id);

                if (count($following) > 0) {
                    foreach ($following as $key => $follow) {

                        $person_user_id = $follow['FollowingList']['id'];



                        $follower_details = $this->Follower->ifFollowing($user_id, $person_user_id);
                        $follower_back_details = $this->Follower->ifFollowing($person_user_id, $user_id);

                        $following_count = $this->Follower->countFollowing($person_user_id);

                        $video_count = $this->Video->getUserVideosCount($person_user_id);
                        $following[$key]['FollowingList']['following_count'] = $following_count;
                        $following[$key]['FollowingList']['video_count'] = $video_count;

                        if (count($follower_details) > 0 && count($follower_back_details) > 0) {

                            $following[$key]['FollowingList']['button'] = "Friends";
                        } else if (count($follower_details) > 0) {

                            $following[$key]['FollowingList']['button'] = "Following";

                        } else if (count($follower_back_details) > 0 && $follower_details < 0) {

                            $following[$key]['FollowingList']['button'] = "Follow Back";

                        } else {

                            $following[$key]['FollowingList']['button'] = "Follow";

                        }

                    }
                }
                $searchData = $following;

            }else if($type == "follower"){


                $followers = $this->Follower->searchFollower($keyword,$starting_point,$user_id);

                if (count($followers) > 0) {
                    foreach ($followers as $key => $follow) {

                        $person_user_id = $follow['FollowerList']['id'];


                        $follower_details = $this->Follower->ifFollowing($user_id, $person_user_id);
                        $follower_back_details = $this->Follower->ifFollowing($person_user_id, $user_id);

                        $followers_count = $this->Follower->countFollowers($person_user_id);

                        $video_count = $this->Video->getUserVideosCount($person_user_id);
                        $followers[$key]['FollowerList']['follower_count'] = $followers_count;
                        $followers[$key]['FollowerList']['video_count'] = $video_count;
                        if (count($follower_details) > 0 && count($follower_back_details) > 0) {

                            $followers[$key]['FollowerList']['button'] = "Friends";
                        } else if (count($follower_details) > 0) {

                            $followers[$key]['FollowerList']['button'] = "Following";

                        } else if (count($follower_back_details) > 0 && $follower_details < 0) {

                            $followers[$key]['FollowerList']['button'] = "Follow Back";

                        } else {

                            $followers[$key]['FollowerList']['button'] = "Follow";

                        }

                    }
                }
                $searchData = $followers;

            }




            if(count($searchData) > 0) {

                $output['code'] = 200;

                $output['msg'] = $searchData;


                echo json_encode($output);

                die();

            }else{

                Message::EMPTYDATA();
                die();

            }










        }
    }




    public function searchFollowingOrFollowUsers()
    {




        $this->loadModel("User");

        $this->loadModel("Follower");
        $this->loadModel("Video");



        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $keyword = $data['keyword'];

            $starting_point = $data['starting_point'];


            $user_id = 0;

            if(isset($data['user_id'])){

                $user_id = $data['user_id'];
            }




            $new_array = array();
            $followers = $this->Follower->searchFollower($keyword,$starting_point,$user_id);
            $following = $this->Follower->searchFollowing($keyword,$starting_point,$user_id);


            $i=0;
            if (count($followers) > 0) {
                foreach ($followers as $key => $follow) {

                    $person_user_id = $follow['FollowerList']['id'];


                    $follower_details = $this->Follower->ifFollowing($user_id, $person_user_id);
                    $follower_back_details = $this->Follower->ifFollowing($person_user_id, $user_id);

                    $followers_count = $this->Follower->countFollowers($person_user_id);

                    $video_count = $this->Video->getUserVideosCount($person_user_id);
                    $new_array[$i]['User'] = $follow['FollowerList'];
                    $new_array[$i]['User']['follower_count'] = $followers_count;
                    $new_array[$i]['User']['video_count'] = $video_count;
                    if (count($follower_details) > 0 && count($follower_back_details) > 0) {

                        $new_array[$i]['User']['button'] = "Friends";
                    } else if (count($follower_details) > 0) {

                        $new_array[$i]['User']['button'] = "Following";

                    } else if (count($follower_back_details) > 0 && $follower_details < 0) {

                        $new_array[$i]['User']['button'] = "Follow Back";

                    } else {

                        $new_array[$i]['User']['button'] = "Follow";

                    }

                    $i++;
                }
            }

            if (count($following) > 0) {
                foreach ($following as $key => $follow) {

                    $person_user_id = $follow['FollowingList']['id'];



                    $follower_details = $this->Follower->ifFollowing($user_id, $person_user_id);
                    $follower_back_details = $this->Follower->ifFollowing($person_user_id, $user_id);

                    $following_count = $this->Follower->countFollowing($person_user_id);

                    $video_count = $this->Video->getUserVideosCount($person_user_id);
                    $new_array[$i]['User'] = $follow['FollowingList'];
                    $new_array[$i]['User']['following_count'] = $following_count;
                    $new_array[$i]['User']['video_count'] = $video_count;

                    if (count($follower_details) > 0 && count($follower_back_details) > 0) {

                        $new_array[$i]['User']['button'] = "Friends";
                    } else if (count($follower_details) > 0) {

                        $new_array[$i]['User']['button'] = "Following";

                    } else if (count($follower_back_details) > 0 && $follower_details < 0) {

                        $new_array[$i]['User']['button'] = "Follow Back";

                    } else {

                        $new_array[$i]['User']['button'] = "Follow";

                    }

                    $i++;
                }
            }







            if(count($new_array) > 0) {

                $output['code'] = 200;

                $output['msg'] = $new_array;


                echo json_encode($output);

                die();

            }else{

                Message::EMPTYDATA();
                die();

            }










        }
    }

    public function showLicense(){


        $if_method_exist = method_exists('Extended', 'deleteObjectS3');

        if($if_method_exist){


            $output['code'] = 200;

            $output['msg'] = "Extended";


            echo json_encode($output);

            die();
        }else{

            $output['code'] = 201;

            $output['msg'] = "Regular";


            echo json_encode($output);

            die();
        }
    }



    function forgotPassword()
    {


        $this->loadModel('User');

        if ($this->request->isPost()) {


            $result = array();
            $json   = file_get_contents('php://input');

            $data = json_decode($json, TRUE);


            $email     = $data['email'];



            $code     = Utility::randomNumber(4);
            $user_info = $this->User->getUserDetailsAgainstEmail($email);


            if (count($user_info) > 0) {



                $user_id = $user_info['User']['id'];
                $email   = $user_info['User']['email'];
                $first_name   = $user_info['User']['first_name'];
                $last_name   = $user_info['User']['last_name'];
                $full_name   = $first_name. ' '.$last_name;

                $email_data['to'] = $email;
                $email_data['name'] = $full_name;
                $email_data['subject'] = "reset your password";
                $email_data['message'] = "You recently requested to reset your password for your ".APP_NAME." account  with the e-mail address (".$email."). 
Please enter this verification code to reset your password.<br><br>Confirmation code: <b></b>".$code."<b>";
                $response = Utility::sendMail($email_data);



                //  $response['ErrorCode']  = 0;
                if ($response['code'] == 200) {

                    $this->User->id = $user_id;

                    $savedField     = $this->User->saveField('token', $code);
                    $result['code'] = 200;
                    $result['msg']  = "An email has been sent to " . $email . ". You should receive it shortly.";
                } else {

                    $result['code'] = 201;
                    $result['msg']  = $response['msg'];


                }

            } else {

                $result['code'] = 201;
                $result['msg']  = "Email doesn't exist";
            }



            echo json_encode($result);
            die();
        }


    }


    public function verifyforgotPasswordCode()
    {
        $this->loadModel('User');


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');

            $data = json_decode($json, TRUE);
            $code = $data['code'];
            $email = $data['email'];

            $code_verify = $this->User->verifyToken($code,$email);
            $user_info = $this->User->getUserDetailsFromEmail($email);
            if (!empty($code_verify)) {
                $this->User->id = $user_info['User']['id'];
                $this->User->saveField('token',0);

                $user_info = $this->User->getUserDetailsFromEmail($email);
                $result['code'] = 200;
                $result['msg']  = $user_info;
                echo json_encode($result);
                die();
            } else {
                $result['code'] = 201;
                $result['msg']  = "invalid code";
                echo json_encode($result);
                die();
            }
        }
    }


    public function changePassword()
    {
        $this->loadModel('User');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);


            $user_id        = $data['user_id'];
            $this->User->id = $user_id;
            $email          = $this->User->field('email');

            $old_password   = $data['old_password'];
            $new_password   = $data['new_password'];


            if ($this->User->verifyPassword($email, $old_password)) {

                $this->request->data['password'] = $new_password;
                $this->User->id                  = $user_id;


                if ($this->User->save($this->request->data)) {

                    echo Message::DATASUCCESSFULLYSAVED();

                    die();
                } else {


                    echo Message::DATASAVEERROR();
                    die();


                }

            } else {

                echo Message::INCORRECTPASSWORD();
                die();

            }


        }

    }
    public function changeEmailAddress()
    {
        $this->loadModel('User');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);


            $user_id        = $data['user_id'];
            $email        = $data['email'];


            $email_exist = $this->User->editIsEmailAlreadyExist($email, $user_id);

            $user_details = $this->User->getUserDetailsFromID($user_id);
            if(count($user_details) > 0) {

                $db_email = $user_details['User']['email'];

                if ($db_email == $email) {


                    $result['code'] = 200;
                    $result['msg'] = $user_details;
                    echo json_encode($result);
                    die();
                }

                if ($email_exist > 0) {

                    $result['code'] = 201;
                    $result['msg'] = "This email has already been registered";
                    echo json_encode($result);
                    die();
                }


                $code = Utility::randomNumber(4);


                $user_id = $user_details['User']['id'];
                $first_name = $user_details['User']['first_name'];
                $last_name = $user_details['User']['last_name'];
                $full_name = $first_name . ' ' . $last_name;

                $email_data['to'] = $email;
                $email_data['name'] = $full_name;
                $email_data['subject'] = "change your email address";
                $email_data['message'] = "You recently requested to update your email for your " . APP_NAME . " account. 
Please enter this verification code to reset your email.<br><br>Confirmation code: <b></b>" . $code . "<b>";
                $response = Utility::sendMail($email_data);


                //  $response['ErrorCode']  = 0;
                if ($response['code'] == 200) {

                    $this->User->id = $user_id;

                    $savedField = $this->User->saveField('token', $code);
                    $result['code'] = 200;
                    $result['msg'] = "An email has been sent to " . $email . ". You should receive it shortly.";
                } else {

                    $result['code'] = 201;
                    $result['msg'] = $response['msg'];


                }

                echo json_encode($result);
                die();
            }else{


                Message::EMPTYDATA();
                die();
            }

        }

    }




    public function verifyRegisterEmailCode()
    {
        $this->loadModel('EmailVerification');
        $this->loadModel('User');


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');

            $data = json_decode($json, TRUE);

            $email_count = $this->User->isEmailAlreadyExist($data['email']);
            if($email_count > 0){

                $user_details  = $this->User->getUserDetailsAgainstEmail($data['email']);
                $active = $user_details['User']['active'];

                if($active > 1){


                    $output['code'] = 201;
                    $output['msg'] = "You have been blocked by the admin. Contact support";
                    echo json_encode($output);
                    die();

                }

                $output['code'] = 201;
                $output['msg'] = "The account already exist with this email";
                echo json_encode($output);
                die();
            }
            if(!isset($data['code'])){


                $email = $data['email'];



                $code = Utility::randomNumber(4);
                $email_data['to'] = $email;
                $email_data['name'] = "";
                $email_data['subject'] = "verify your email address";
                $email_data['message'] = "Please enter this verification code to register your email.<br><br>Confirmation code: <b></b>" . $code . "<b>";

                if(APP_STATUS == "live"){

                    $response = Utility::sendMail($email_data);

                }else{

                    $code = 1234;
                }

                $email_verification['email'] = $email;
                $email_verification['code'] = $code;

                $this->EmailVerification->save($email_verification);
                $id = $this->EmailVerification->getInsertID();
                $details =  $this->EmailVerification->getDetails($id);

                $result['code'] = 200;
                $result['msg'] = $details;
                echo json_encode($result);
                die();
            }
            $code = $data['code'];
            $email = $data['email'];
            $details = $this->EmailVerification->verifyCode($email,$code);
            if(count($details) > 0) {

                $result['code'] = 200;
                $result['msg'] = $details;
                echo json_encode($result);
                die();

            }else{

                $result['code'] = 201;
                $result['msg'] = "invalid code";
                echo json_encode($result);
                die();
            }

        }
    }

    public function verifyChangeEmailCode()
    {
        $this->loadModel('User');


        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');

            $data = json_decode($json, TRUE);
            $code = $data['code'];
            $email = $data['new_email'];
            $user_id = $data['user_id'];
            $user_details = $this->User->getUserDetailsFromID($user_id);
            if(count($user_details) > 0) {

                $db_email = $user_details['User']['email'];
                $code_verify = $this->User->verifyToken($code, $db_email);

                if (!empty($code_verify) && $code > 0) {
                    $email_change['email'] = $email;
                    $email_change['token'] = 0;
                    $email_change['active'] = 1;
                    $this->User->id = $user_id;
                    $this->User->save($email_change);

                    $user_details = $this->User->getUserDetailsFromEmail($email);
                    $result['code'] = 200;
                    $result['msg'] = $user_details;
                    echo json_encode($result);
                    die();
                } else {
                    $result['code'] = 201;
                    $result['msg'] = "invalid code";
                    echo json_encode($result);
                    die();
                }
            }else{

                $result['code'] = 201;
                $result['msg'] = "invalid code";
                echo json_encode($result);
                die();
            }

        }
    }

    public function changePhoneNo()
    {
        $this->loadModel('User');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);


            $user_id        = $data['user_id'];
            $phone        = $data['phone'];


            $phone_exist = $this->User->editIsphoneNoAlreadyExist($phone, $user_id);

            $user_details = $this->User->getUserDetailsFromID($user_id);
            if(count($user_details) > 0) {

                $db_phone = $user_details['User']['phone'];

                if ($db_phone == $phone) {


                    $result['code'] = 200;
                    $result['msg'] = $user_details;
                    echo json_encode($result);
                    die();
                }

                if ($phone_exist > 0) {

                    $result['code'] = 201;
                    $result['msg'] = "This phone has already been registered";
                    echo json_encode($result);
                    die();
                }



                $response =  $this->verifyPhoneNo($phone,$user_id,0);


                echo json_encode($response);
                die();
            }

        }

    }

    public function verifyPhoneNo($phone_no = null,$user_id = null,$verify = null)
    {

        $this->loadModel('PhoneNoVerification');
        $this->loadModel('User');



        $json = file_get_contents('php://input');

        $data = json_decode($json, TRUE);


        if (!empty($phone_no)) {
            $phone_no = $phone_no;
            $verify = $verify;

        }else{

            $phone_no =  $data['phone'];
            $verify =  $data['verify'];
            // $code =  $data['code'];

            if(isset($data['user_id'])) {
                $user_id = $data['user_id'];

                $phone_exist = $this->User->editisphoneNoAlreadyExist($phone_no,$user_id);




                if ($phone_exist > 0) {

                    $result['code'] = 201;
                    $result['msg'] = "This phone has already been registered";
                    echo json_encode($result);
                    die();
                }
            }
        }


        $code     = Utility::randomNumber(4);

        if(APP_STATUS =="demo"){
            $code     = 1234;
        }


        $created                  = date('Y-m-d H:i:s', time() - 60 * 60 * 4);
        $phone_verify['phone_no'] = $phone_no;
        $phone_verify['code']     = $code;
        $phone_verify['created']  = $created;


        if ($verify == 0) {

            if(APP_STATUS =="demo"){
                $response['sid']= "";
            }else{

                $response = Utility::sendSmsVerificationCurl($phone_no, VERIFICATION_PHONENO_MESSAGE . ' ' . $code);

            }





            if (array_key_exists('code', $response)){


                $output['code'] = 201;
                $output['msg']  = $response['message'];



            }else{



                if (array_key_exists('sid', $response)){



                    $this->PhoneNoVerification->save($phone_verify);


                    $output['code'] = 200;

                    $output['msg']  = "code has been generated and sent to user's phone number";



                }

            }





        } else {
            $code_user = $data['code'];
            if ($this->PhoneNoVerification->verifyCode($phone_no, $code_user) > 0) {

                if (!empty($user_id)) {


                    $this->User->id = $user_id;
                    $this->User->saveField('phone',$phone_no);
                }
                $output['code'] = 200;
                $output['msg']  = "successfully code matched";
                /*$this->PhoneNoVerification->deleteAll(array(
                    'phone_no' => $phone_no
                ), false);*/



            } else {

                $output['code'] = 201;
                $output['msg']  = "invalid code";



            }

        }

        if (!empty($phone)) {


            return $output;
        }else{


            //it means post request from app
            echo json_encode($output);
            die();

        }

    }
    public function blockUser()
    {


        $this->loadModel('BlockUser');

        if ($this->request->isPost()) {


            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            //$user['password'] = $data['password'];


            $user_id = $data['user_id'];
            $block_user_id = $data['block_user_id'];

            $block_user['created'] = date('Y-m-d H:i:s', time());
            $block_user['user_id'] = $user_id;
            $block_user['block_user_id'] = $block_user_id;

            $userDetails = $this->BlockUser->ifAlreadyBlocked($block_user);
            if(count($userDetails) < 1) {

                $this->BlockUser->save($block_user);

                $id = $this->BlockUser->getInsertID();
                $output = array();
                $userDetails = $this->BlockUser->getDetails($id);


                $output['code'] = 200;
                $output['msg'] = $userDetails;
                echo json_encode($output);
                die();

            }else{

                $id = $userDetails['BlockUser']['id'];
                $this->BlockUser->id = $id;
                $this->BlockUser->delete();


                $output['code'] = 201;
                $output['msg'] = "deleted";
                echo json_encode($output);
                die();

            }
        }
    }

    public function showBlockedUsers(){

        $this->loadModel('BlockUser');




        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];

            $details = $this->BlockUser->getBlockUsers($user_id);


            if(count($details) > 0) {


                $output['code'] = 200;

                $output['msg'] = $details;


                echo json_encode($output);


                die();
            }else{


                Message::EMPTYDATA();
                die();
            }

        }


    }

    public function changePasswordForgot()
    {
        $this->loadModel('User');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            //$json = $this->request->data('json');
            $data = json_decode($json, TRUE);


            $email        = $data['email'];

            $new_password   = $data['password'];




            $this->request->data['password'] = $new_password;

            $email_details = $this->User->getUserDetailsAgainstEmail($email);


            $user_id = $email_details['User']['id'];
            $this->User->id = $user_id;
            if ($this->User->save($this->request->data)) {

                $user_info = $this->User->getUserDetailsFromID($user_id);
                $result['code'] = 200;
                $result['msg']  = $user_info;
                echo json_encode($result);
                die();
            } else {


                echo Message::DATASAVEERROR();
                die();


            }

        } else {

            echo Message::INCORRECTPASSWORD();
            die();




        }

    }


    public function sendLiveStreamPushNotfication()
    {
        $this->loadModel("User");
        $this->loadModel("PushNotification");
        $this->loadModel("Follower");
        $this->loadModel("Notification");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $user_id = $data['user_id'];






            $user_details =  $this->User->getUserDetailsFromID($user_id);
            $followers =  $this->Follower->getUserFollowersWithoutLimit($user_id);
            if(count($followers) > 0){
                foreach($followers as $follower){


                    $device_token = $follower['FollowerList']['device_token'];
                    $receiver_id = $follower['FollowerList']['id'];


                    $msg = $user_details['User']['username']." is live now";
                    if (strlen($device_token) > 8) {
                        $notification['to'] = $device_token;

                        $notification['notification']['title'] =  $user_details['User']['username'];
                        $notification['notification']['body'] = $msg;
                        $notification['notification']['badge'] = "1";
                        $notification['notification']['sound'] = "default";
                        $notification['notification']['icon'] = "";
                        $notification['notification']['type'] = "live";
                        $notification['notification']['user_id'] = $user_id;
                        $notification['notification']['name'] = $user_details['User']['first_name']." ".$user_details['User']['last_name'];
                        $notification['notification']['image'] = $user_details['User']['profile_pic'];

                        $notification['data']['title'] =$user_details['User']['username'];
                        $notification['data']['body'] = $msg;
                        $notification['data']['icon'] = "";
                        $notification['data']['badge'] = "1";
                        $notification['data']['sound'] = "default";
                        $notification['data']['type'] = "live";
                        $notification['data']['user_id'] = $user_id;
                        $notification['data']['name'] = $user_details['User']['first_name']." ".$user_details['User']['last_name'];
                        $notification['data']['image'] = $user_details['User']['profile_pic'];
                        $notification['data']['receiver_id'] = $receiver_id;
                        $notification['notification']['receiver_id'] = $receiver_id;




                        Utility::sendPushNotificationToMobileDevice(json_encode($notification));


                        $notification_data['sender_id'] = $user_id;
                        $notification_data['receiver_id'] = $receiver_id;
                        $notification_data['type'] = "live";
                        //$notification_data['video_id'] = $video_id;

                        $notification_data['string'] = $msg;
                        $notification_data['created'] = date('Y-m-d H:i:s', time());

                        $this->Notification->save($notification_data);
                    }
                }
            }




            $output['code'] = 200;
            $output['msg'] = "success";
            echo json_encode($output);


            die();
        }

    }

    public function deleteUserAccount(){

        $this->loadModel('User');
        $this->loadModel('Follower');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);

            $user_id = $data['user_id'];

            $details = $this->User->getUserDetailsFromID($user_id);

            if(count($details) > 0 ) {
                $this->User->delete($user_id,true);
                $this->Follower->deleteFollowerAgainstUserID($user_id);


                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }

    public function deleteFollower(){

        $this->loadModel('User');
        $this->loadModel('Follower');

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $follower_id = $data['follower_id'];
            $user_id = $data['user_id'];

            $details = $this->Follower->ifFollowing($follower_id,$user_id);

            if(count($details) > 0 ) {
                $this->Follower->id = $details['Follower']['id'];
                $this->Follower->delete();


                $output['code'] = 200;

                $output['msg'] = "deleted";


                echo json_encode($output);


                die();

            }else{

                $output['code'] = 201;

                $output['msg'] = "Invalid id: Do not exist";


                echo json_encode($output);


                die();


            }

        }




    }

    public function sendMessageNotification()
    {
        $this->loadModel("User");
        $this->loadModel("PushNotification");

        if ($this->request->isPost()) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, TRUE);


            $sender_id = $data['sender_id'];
            $title = $data['title'];
            $message = $data['message'];

            $sender_details =  $this->User->getUserDetailsFromID($sender_id);





            /*********************************START NOTIFICATION******************************/

            if(isset($data['receivers'])){


                $receivers = $data['receivers'];

                foreach($receivers as $receiver){

                    $receiver_id = $receiver['receiver_id'];
                    $receiver_details =  $this->User->getUserDetailsFromID($receiver_id);

                    $notification['to'] = $receiver_details['User']['device_token'];




                    $notification['notification']['title'] = $title;
                    $notification['notification']['body'] = $message;
                    $notification['notification']['user_id'] = $sender_details['User']['id'];
                    $notification['notification']['image'] = $sender_details['User']['profile_pic'];
                    $notification['notification']['name'] = $sender_details['User']['username'];
                    $notification['notification']['badge'] = "1";
                    $notification['notification']['sound'] = "default";
                    $notification['notification']['icon'] = "";
                    $notification['notification']['type'] = "message";

                    $notification['data']['title'] = $title;
                    $notification['data']['name'] = $sender_details['User']['username'];
                    $notification['data']['body'] = $message;
                    $notification['data']['icon'] = "";
                    $notification['data']['badge'] = "1";
                    $notification['data']['sound'] = "default";
                    $notification['data']['type'] = "message";
                    $notification['data']['user_id'] = $sender_details['User']['id'];
                    $notification['data']['image'] = $sender_details['User']['profile_pic'];
                    $notification['data']['receiver_id'] = $receiver_details['User']['id'];
                    $notification['notification']['receiver_id'] = $receiver_details['User']['id'];




                    $if_exist = $this->PushNotification->getDetails($receiver_details['User']['id']);
                    if(count($if_exist) > 0) {

                        $likes = $if_exist['PushNotification']['direct_messages'];
                        if($likes > 0) {
                            Utility::sendPushNotificationToMobileDevice(json_encode($notification));
                        }
                    }
                }
            }

            if(isset($data['receiver_id'])) {
                $receiver_id = $data['receiver_id'];


                $receiver_details =  $this->User->getUserDetailsFromID($receiver_id);
                $notification['to'] = $receiver_details['User']['device_token'];


                $notification['notification']['title'] = $title;
                $notification['notification']['body'] = $message;
                $notification['notification']['user_id'] = $sender_details['User']['id'];
                $notification['notification']['image'] = $sender_details['User']['profile_pic'];
                $notification['notification']['name'] = $sender_details['User']['username'];
                $notification['notification']['badge'] = "1";
                $notification['notification']['sound'] = "default";
                $notification['notification']['icon'] = "";
                $notification['notification']['type'] = "message";

                $notification['data']['title'] = $title;
                $notification['data']['name'] = $sender_details['User']['username'];
                $notification['data']['body'] = $message;
                $notification['data']['icon'] = "";
                $notification['data']['badge'] = "1";
                $notification['data']['sound'] = "default";
                $notification['data']['type'] = "message";
                $notification['data']['user_id'] = $sender_details['User']['id'];
                $notification['data']['image'] = $sender_details['User']['profile_pic'];
                $notification['data']['receiver_id'] = $receiver_details['User']['id'];
                $notification['notification']['receiver_id'] = $receiver_details['User']['id'];


                $if_exist = $this->PushNotification->getDetails($receiver_details['User']['id']);
                if (count($if_exist) > 0) {

                    $likes = $if_exist['PushNotification']['direct_messages'];
                    if ($likes > 0) {
                        Utility::sendPushNotificationToMobileDevice(json_encode($notification));
                    }
                }

                /*********************************END NOTIFICATION******************************/


            }


            $output['code'] = 200;
            $output['msg'] = "success";
            echo json_encode($output);


            die();
        }

    }













}








?>