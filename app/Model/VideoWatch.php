<?php


class VideoWatch extends AppModel
{

    public $useTable = 'video_watch';

    public $belongsTo = array(
        'Device' => array(
            'className' => 'Device',
            'foreignKey' => 'device_id',



        ),'Video' => array(
            'className' => 'Video',
            'foreignKey' => 'video_id',



        ),

    );

    public function getDetails($id)
    {
        return $this->find('first', array(
            'conditions' => array(



                'VideoWatch.id'=> $id,




            )
        ));
    }


    public function ifExist($data)
    {
        return $this->find('first', array(
            'conditions' => array(



                'VideoWatch.video_id'=> $data['video_id'],
                'VideoWatch.device_id'=> $data['device_id'],




            )
        ));
    }

    public function getAll()
    {
        return $this->find('all');
    }






}
?>