<?php


class VideoCommentReply extends AppModel
{

    public $useTable = 'video_comment_reply';

    public $belongsTo = array(
        'VideoComment' => array(
            'className' => 'VideoComment',
            'foreignKey' => 'comment_id',



        ),

        'User' => array(
            'className' => 'User',
            'foreignKey' => 'user_id',



        ),

    );

    public function getDetails($id)
    {
        return $this->find('first', array(
            'conditions' => array(



                'VideoCommentReply.id'=> $id,




            )
        ));
    }



   
    public function getAll()
    {
        return $this->find('all');
    }






}
?>