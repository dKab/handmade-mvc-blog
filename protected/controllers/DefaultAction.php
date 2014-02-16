<?php
class DefaultAction extends Action
{
    public function execute()
    {
        $model = new PostManager();
        $posts = $model->getPublished();
        echo AppHelper::twig()->render("posts.html.twig", array( 
            'title'=>'Все записи',
            'posts'=>$posts,
            ));
    }
}

