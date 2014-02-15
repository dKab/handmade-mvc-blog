<?php
class DefaultAction extends Action
{
    public function execute()
    {
        $dbh = AppHelper::instance()->getConnection();
        $manager = new PostManager($dbh);
        $posts = $manager->getAllPosts();
        $twig = AppHelper::twig();
        // var_dump($twig);
        //var_dump($posts);
        
        echo $twig->render("posts.html.twig", array( 
            'title'=>'Все записи',
            'posts'=>$posts,
            'debug' => true
            ));
  
        
        /*
        AppHelper::instance()->twig()->render( 'posts.twig', array(
                'title' => "Все записи",
                 'posts' => $posts,
        ));
         * 
         */
        /*
        $this->render('posts', array(
            'title'=>'Приветствуем!',
            'posts'=> $posts,
        ));
         * 
         */
    }
}

