<?php



class Promotion extends AppModel
{
    public $useTable = 'promotion';

    public $belongsTo = array(

        'User' => array(
            'className' => 'User',
            'foreignKey' => 'user_id',


        ),

        'Video' => array(
            'className' => 'Video',
            'foreignKey' => 'video_id',


        ),


        'Audience' => array(
            'className' => 'Audience',
            'foreignKey' => 'audience_id',


        ),



    );

    public function getDetails($id)
    {
        return $this->find('first', array(
            'conditions' => array(

                'Promotion.id' => $id





            )
        ));
    }



    public function getUserPromotions($user_id)
    {
        return $this->find('all', array(
            'conditions' => array(

                'Promotion.user_id' => $user_id





            )
        ));
    }

    public function getUserActivePromotions($user_id,$datetime)
    {
        return $this->find('all', array(
            'conditions' => array(

                'Promotion.user_id' => $user_id,
                'Promotion.end_datetime >=' => $datetime,
                'Promotion.active'=>1





            )
        ));
    }

    public function getActivePromotionAudience($id,$datetime)
    {
        return $this->find('first', array(
            'conditions' => array(

                'Promotion.audience_id' => $id,
                'Promotion.end_datetime >=' => $datetime





            )
        ));
    }

    public function getUserCompletedPromotions($user_id,$datetime)
    {
        return $this->find('all', array(
            'conditions' => array(

                'Promotion.user_id' => $user_id,
                'Promotion.end_datetime <=' => $datetime,
                'Promotion.active'=>1





            )
        ));
    }
    public function getNonActivePromotions($user_id)
    {
        return $this->find('all', array(
            'conditions' => array(

                'Promotion.user_id' => $user_id,
                'Promotion.active' => array(0, 2)





            )
        ));
    }

    public function getVideoPromotions($video_id)
    {
        return $this->find('all', array(
            'conditions' => array(

                'Promotion.video_id' => $video_id





            )
        ));
    }

    public function getVideoPromotionDetail($video_id)
    {
        return $this->find('first', array(
            'conditions' => array(

                'Promotion.video_id' => $video_id,
                'Promotion.reach < Promotion.price',






            )
        ));
    }

    public function getActivePromotionAgainstPromotionID($promotion_id,$datetime)
    {
        return $this->find('first', array(
            'conditions' => array(

                'Promotion.id' => $promotion_id,
                'Promotion.start_datetime >=' => $datetime,
                'Promotion.end_datetime <=' => $datetime,
                'Promotion.active' => 1




            )
        ));
    }

    public function getActivePromotionAgainstVideoID($video_id,$datetime)
    {
        return $this->find('first', array(
            'conditions' => array(

                'Promotion.video_id' => $video_id,
                'Promotion.start_datetime >=' => $datetime,
                'Promotion.end_datetime <=' => $datetime,
                'Promotion.active' => 1





            )
        ));
    }



    public function getPromotedVideoWhichHasNotBeenWatchedByTheUser($user_id,$city_id,$state_id,$country_id,$gender,$dob,$datetime)
    {
        $this->Behaviors->attach('Containable');




        return $this->find('first', array(
            // 'fields' => array('id'),
            'conditions' => array(
                'not exists '.
                '(SELECT id FROM video_watch as VideoWatch WHERE Promotion.video_id = VideoWatch.video_id AND VideoWatch.user_id ='.$user_id.')',
                'exists '.
                '(SELECT id FROM audience_location as AudienceLocation WHERE 
                AudienceLocation.city_id = '.$city_id.' OR AudienceLocation.state_id = '.$state_id.' OR AudienceLocation.country_id = '.$country_id.')',
                'Promotion.user_id !='=> $user_id,

                'Promotion.reach < Promotion.price',


                //'Video.id >='=> $starting_id,
                //'Audience.location'=> $location,
                'Audience.gender IN'=> array("all",$gender),
                'Audience.min_age <='=> $dob,

                'Audience.max_age >='=> $dob,
                //'Promotion.start_datetime >=' => $datetime,
                // 'Promotion.end_datetime >=' => $datetime,


            ),
            'contain' => array('Audience','Video.User.PrivacySetting','Video.User.PushNotification','Video.Sound','Video.VideoComment.User'),



        ));

    }




    public function getPromotionsBasedOnStatus($date,$active)
    {
        return $this->find('all', array(
            'conditions' => array(

                'Promotion.active' => $active,
                'Promotion.end_datetime >=' => $date





            ),
            'order' => 'Promotion.id ASC'
        ));
    }

    public function getAll()
    {
        return $this->find('all', array(

            'order' => 'Promotion.id DESC'

        ));
    }








}