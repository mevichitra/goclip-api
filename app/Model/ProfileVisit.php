<?php



class ProfileVisit extends AppModel
{
    public $useTable = 'profile_visit';

    public $belongsTo = array(

        'Sender' => array(
            'className' => 'User',
            'foreignKey' => 'sender_id',


        ),

        'Receiver' => array(
            'className' => 'User',
            'foreignKey' => 'receiver_id',


        ),



    );

    public function getDetails($id)
    {
        return $this->find('first', array(
            'conditions' => array(

                'ProfileVisit.id' => $id





            )
        ));
    }

    public function getProfileVisitCount($user_id,$promotion_id)
    {
        return $this->find('count', array(
            'conditions' => array(

                'ProfileVisit.receiver_id' => $user_id,
                'ProfileVisit.promotion_id' => $promotion_id,






            )
        ));
    }

    public function getVisitorsCount($user_id)
    {
        return $this->find('count', array(
            'conditions' => array(

                'ProfileVisit.receiver_id' => $user_id,






            ),
            array('fields' => 'COUNT(DISTINCT sender_id) as accounts_count'),
        ));
    }

    public function getVisitorsCountryCount($user_id)
    {
        return $this->find('all', array(
            'conditions' => array(

                'ProfileVisit.receiver_id' => $user_id,






            ),
            'fields' => array(
                'ProfileVisit.sender_country','COUNT(ProfileVisit.id) as total_count',
            ),
            'group' => array('ProfileVisit.sender_country'),
            'order' => 'total_count DESC'
        ));
    }

    public function getVisitorsCityCount($user_id)
    {
        return $this->find('all', array(
            'conditions' => array(

                'ProfileVisit.receiver_id' => $user_id,






            ),
            'fields' => array(
                'ProfileVisit.sender_city','COUNT(ProfileVisit.id) as total_count',
            ),
            'group' => array('ProfileVisit.sender_city'),
            'order' => 'total_count DESC'
        ));
    }


    public function getAgeRangedData($user_id)
    {
        return $this->query("SELECT 
                            CASE
                         
                            WHEN dob BETWEEN 13 and 17 THEN '13 - 17'
                            WHEN dob BETWEEN 18 and 24 THEN '18 - 24'
                            WHEN dob BETWEEN 25 and 34 THEN '25 - 34'
                            WHEN dob BETWEEN 35 and 44 THEN '35 - 44'
                            WHEN dob BETWEEN 45 and 54 THEN '45 - 54'
                            WHEN dob BETWEEN 55 and 64 THEN '55 - 64'
                            WHEN dob > 65 THEN 'Over 65'
                            WHEN dob IS NULL THEN 'Not Filled In (NULL)'
                            END as age_range,
                            COUNT(*) AS total_count
                            
                            FROM (SELECT TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS dob FROM profile_visit  WHERE profile_visit.receiver_id = $user_id) as profile_visit
                           
                            GROUP BY age_range
                            
                            ORDER BY age_range");
    }

    public function getAgeRangedDataAgainstGender($user_id,$gender)
    {
        return $this->query("SELECT 
                            CASE
                         
                            WHEN dob BETWEEN 13 and 17 THEN '13 - 17'
                            WHEN dob BETWEEN 18 and 24 THEN '18 - 24'
                            WHEN dob BETWEEN 25 and 34 THEN '25 - 34'
                            WHEN dob BETWEEN 35 and 44 THEN '35 - 44'
                            WHEN dob BETWEEN 45 and 54 THEN '45 - 54'
                            WHEN dob BETWEEN 55 and 64 THEN '55 - 64'
                            WHEN dob > 65 THEN 'Over 65'
                            WHEN dob IS NULL THEN 'Not Filled In (NULL)'
                            END as age_range,
                            COUNT(*) AS total_count
                            
                            FROM (SELECT TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS dob FROM profile_visit  WHERE profile_visit.receiver_id = $user_id AND profile_visit.gender = '$gender') as profile_visit
                           
                            GROUP BY age_range
                            
                            ORDER BY age_range");
    }







    public function getVisitorsLastWeekGroupByDay($user_id,$start_date)
    {
        return $this->find('all', array(
            'conditions' => array(

                'ProfileVisit.receiver_id' => $user_id,
                'ProfileVisit.created >=' => $start_date,
                //'ProfileVisit.created <=' => $end_date,






            ),
            'fields' => array(
                'DAYNAME(ProfileVisit.created) as day','DATE(ProfileVisit.created) as date','COUNT(ProfileVisit.id) as total_count',
            ),
            'group' => array('ProfileVisit.created'),
            'order' => 'ProfileVisit.created ASC'
        ));
    }

    public function getVisitorsLastWeekGroupByDayCount($user_id,$start_date)
    {
        return $this->find('count', array(
            'conditions' => array(

                'ProfileVisit.receiver_id' => $user_id,
                'ProfileVisit.created >=' => $start_date,
                //'ProfileVisit.created <=' => $end_date,






            ),



        ));
    }

    public function getVisitorsLastWeekToLastWeekCountGroupByDayCount($user_id,$start_date,$end_date)
    {
        return $this->find('count', array(
            'conditions' => array(

                'ProfileVisit.receiver_id' => $user_id,
                'ProfileVisit.created >=' => $start_date,
                'ProfileVisit.created <=' => $end_date,
                //'ProfileVisit.created <=' => $end_date,






            ),



        ));
    }





}