<?php


class Video extends AppModel
{

    public $useTable = 'video';

    public $belongsTo = array(
        'User' => array(
            'className' => 'User',
            'foreignKey' => 'user_id',



        ),

        'Sound' => array(
            'className' => 'Sound',
            'foreignKey' => 'sound_id',



        ),


    );

    public $hasMany = array(
        'VideoComment' => array(
            'className' => 'VideoComment',
            'foreignKey' => 'video_id',
            'dependent' =>true



        ),

        'VideoFavourite' => array(
            'className' => 'VideoFavourite',
            'foreignKey' => 'video_id',
            'dependent' =>true



        ),

        'VideoLike' => array(
            'className' => 'VideoLike',
            'foreignKey' => 'video_id',
            'dependent' =>true



        ),

        'VideoWatch' => array(
            'className' => 'VideoWatch',
            'foreignKey' => 'video_id',
            'dependent' =>true



        ),


    );



    public function getDetails($id)
    {

        $this->Behaviors->attach('Containable');
        return $this->find('first', array(
            'conditions' => array(



                'Video.id'=> $id,




            ),

            'contain' => array('User.PrivacySetting','User.PushNotification','Sound','VideoComment.User'),
        ));
    }
    public function getUserVideosIDs($user_id)
    {


        return $this->find('all', array(
            'conditions' => array(



                'Video.user_id'=> $user_id,




            ),
            'fields'=>array('Video.id'),

            'recursive'=>-1
        ));
    }

    public function getDetailsAgainstOldVideoID($old_video_id)
    {


        return $this->find('first', array(
            'conditions' => array(



                'Video.old_video_id'=> $old_video_id,




            ),

            'recursive'=>-1
        ));
    }

    public function getSearchResults($keyword,$starting_point,$user_id){


        $this->Behaviors->attach('Containable');
        return $this->find('all', array(

            'conditions' => array(

                'Video.description Like' => "$keyword%",
                'Video.user_id !=' => $user_id,


            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),

            'limit'=>10,
            'offset' => $starting_point*10,





            'recursive' => 0


        ));

    }

    public function checkDuplicate($data){


        $this->Behaviors->attach('Containable');
        return $this->find('all', array(

            'conditions' => array(

                'Video.description' => $data['description'],
                'Video.user_id=' => $data['user_id'],
                'Video.video' => $data['video'],
                'Video.thum' => $data['thum'],
                'Video.gif' => $data['gif'],



            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),

            'limit'=>10,






            'recursive' => 0


        ));

    }
    public function getUserPublicVideos($user_id,$starting_point)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(
            'conditions' => array(



                'Video.user_id'=> $user_id,
                'Video.privacy_type'=> "public",




            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'limit' => APP_RECORDS_PER_PAGE,
            'offset' => $starting_point*APP_RECORDS_PER_PAGE,
            'order' => 'Video.id DESC'


        ));
    }

    public function getUserPublicVideosAdmin($user_id)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(
            'conditions' => array(



                'Video.user_id'=> $user_id,
                'Video.privacy_type'=> array("public","Public")




            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'order' => 'Video.id DESC'


        ));
    }

    public function getUserPrivateVideosAdmin($user_id)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(
            'conditions' => array(



                'Video.user_id'=> $user_id,
                'Video.privacy_type'=> "private",




            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),


            'order' => 'Video.id DESC'


        ));
    }

    public function getUserVideos($user_id)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(
            'conditions' => array(



                'Video.user_id'=> $user_id,




            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'order' => 'Video.id DESC'


        ));
    }



    public function getAllVideos()
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(


            'order' => 'Video.id DESC',
        'contain' => array('User','Sound'),

        ));
    }

    public function getUserPrivateVideos($user_id,$starting_point)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(
            'conditions' => array(



                'Video.user_id'=> $user_id,
                'Video.privacy_type'=> "private",




            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'limit' => APP_RECORDS_PER_PAGE,
            'offset' => $starting_point*APP_RECORDS_PER_PAGE,
            'order' => 'Video.id DESC'


        ));
    }

    public function getUserVideosCount($user_id)
    {
        $this->Behaviors->attach('Containable');
        return $this->find('count', array(
            'conditions' => array(



                'Video.user_id'=> $user_id,




            ),

          




        ));
    }


    public function getFrequentlyUsedSounds()
    {
        $this->Behaviors->attach('Containable');
        return $this->find('all', array(

            'conditions' => array(

                'Video.privacy_type'=> 'public'
            ),
            'fields' => array( 'DISTINCT Video.sound_id','COUNT(*) count'),

            'group' => array('Video.sound_id'),
            'order' => 'COUNT(*) DESC',




        ));
    }

    public function getVideosAgainstSoundID($user_id,$device_id,$starting_id,$sound_id)
    {
        $this->Behaviors->attach('Containable');



        return $this->find('all', array(
            // 'fields' => array('id'),
            'conditions' => array(

                'Video.sound_id'=> $sound_id,
                'Video.privacy_type'=> 'public'
            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'limit' => APP_RECORDS_PER_PAGE,
            'offset' => $starting_id*APP_RECORDS_PER_PAGE,
            'order' => 'Video.view DESC'
        ));

    }

    public function getPromotedVideo()
    {
        $this->Behaviors->attach('Containable');



        return $this->find('first', array(
            // 'fields' => array('id'),
            'conditions' => array(

                'Video.promote'=> 1,

            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),

            'order' => 'rand()'
        ));

    }


    public function getRelatedVideosNotWatched($user_id,$device_id,$starting_id)
    {
        $this->Behaviors->attach('Containable');



        return $this->find('all', array(
           // 'fields' => array('id'),
            'conditions' => array('not exists '.
                '(SELECT id FROM video_watch as VideoWatch WHERE Video.id = VideoWatch.video_id AND VideoWatch.device_id ='.$device_id.')',
                //'Video.user_id !='=> $user_id,
                'Video.block'=> 0,
                'Video.privacy_type'=> 'public'
            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'limit' => 10,
            'offset' => $starting_id*10,
            'order' => 'rand()'
            //'order' => 'Video.view DESC'
        ));

    }

    public function getRelatedVideosDemo($user_id,$device_id,$starting_id)
    {
        $this->Behaviors->attach('Containable');



        return $this->find('all', array(
            // 'fields' => array('id'),
            'conditions' => array(
                //'Video.user_id !='=> $user_id,
                'Video.block'=> 0,
                //'Video.user_id'=> 1,
                'Video.privacy_type'=> 'public'
            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'limit' => 10,
            'offset' => $starting_id*10,
            'order' => 'rand()'
            //'order' => 'Video.view DESC'
        ));

    }

    public function getRelatedVideosNotWatchedDemo($user_id,$device_id,$starting_id)
    {
        $this->Behaviors->attach('Containable');



        return $this->find('all', array(
            // 'fields' => array('id'),
            'conditions' => array(
                'Video.user_id !='=> $user_id,
                'Video.block'=> 0,
                'Video.privacy_type'=> 'public'
            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'limit' => 5,


            //'order' => 'Video.view DESC'
        ));

    }




    public function getRelatedVideosWatched($user_id,$device_id,$starting_id)
    {
        $this->Behaviors->attach('Containable');



        return $this->find('all', array(
            // 'fields' => array('id'),
            'conditions' => array(
               // 'Video.user_id !='=> $user_id,
                'Video.block'=> 0,
                'Video.privacy_type'=> 'public'
            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'limit' => 10,
            'offset' => $starting_id*10,

            'order' => 'rand()'
            //'order' => 'Video.view DESC'
        ));

    }

    public function getFollowingVideosNotWatched($user_id,$device_id,$starting_id,$ids)
    {
        $this->Behaviors->attach('Containable');



        return $this->find('all', array(
            // 'fields' => array('id'),
            'conditions' => array(
               // '(SELECT id FROM follower as Follower WHERE Video.user_id = Follower.receiver_id AND Follower.sender_id ='.$user_id.' LIMIT 1)',
                //'(SELECT id FROM video_watch as VideoWatch WHERE Video.id = VideoWatch.video_id AND VideoWatch.device_id ='.$device_id.' LIMIT 1)',
                'Video.user_id !='=> $user_id,

                'Video.privacy_type'=> 'public',
                'Video.block'=> 0,
                'Video.user_id IN'=> $ids
            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'limit' => 10,
            'offset' => $starting_id*10,

            'order' => 'rand()'
            //'order' => 'Video.view DESC'
        ));

    }


    public function getFollowingVideosWatched($user_id,$device_id,$starting_id)
    {
        $this->Behaviors->attach('Containable');



        return $this->find('all', array(
            // 'fields' => array('id'),
            'conditions' => array('exists '.
                '(SELECT id FROM follower as Follower WHERE Video.user_id = Follower.receiver_id AND Follower.sender_id ='.$user_id.')',

                'Video.user_id !='=> $user_id,
                'Video.block'=> 0,

                'Video.privacy_type'=> 'public'
            ),
            'contain' => array('User.PrivacySetting','User.PushNotification','Sound'),
            'limit' => 10,
            'offset' => $starting_id*10,

            'order' => 'rand()'
            //'order' => 'Video.view DESC'
        ));

    }

    public function getAllVideosAgainstSoundID($sound_id)
    {




        return $this->find('all', array(

            'conditions' => array(


                'Video.sound_id'=> $sound_id,

            ),
            'recursive' => -1,


        ));

    }

    public function updateSoundIDs($ids){


        $this->updateAll(
            array('sound_id' => 0),
            array('Video.sound_id IN' => $ids)
        );
    }







}
?>