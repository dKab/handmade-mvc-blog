<?php
class DefaultAction extends Action
{
    public function execute()
    {
        $dbh = AppHelper::instance()->getConnection();
        require_once 'protected/transactions/PostManager.php';
        $manager = new PostManager($dbh);
        $posts = $manager->getAllPosts();
        $this->render('posts', array(
            'title'=>'Приветствуем!',
            'posts'=> $posts,
        ));
    }
}

