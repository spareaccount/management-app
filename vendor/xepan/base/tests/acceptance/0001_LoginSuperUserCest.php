<?php

class LoginSuperUserCest
{
    public function _before(SuperUser $I)
    {
    }

    public function _after(SuperUser $I)
    {
    }


    public function wrongLogin(SuperUser $i){
        $i->amOnPage('/admin');
        $i->tryLogin('root','root');
        $i->waitForText('Incorrect');
        $i->see('Incorrect');
    }

}
