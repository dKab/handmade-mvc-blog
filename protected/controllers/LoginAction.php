<?php
class LoginAction extends Action
{
    public function execute()
    {
        session_start();
        $data = array();
        if ( isset($_SESSION['feedback']) ) {
            $data['feedback'] = $_SESSION['feedback'];
            unset($_SESSION['feedback']);
        }
        echo AppHelper::twig()->render('login.html.twig', $data);
    }
}
