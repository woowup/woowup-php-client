<?php
namespace WoowUpTest\WoowUp;

use WoowUp\Client as WoowUp;

/**
 *
 */
class UsersTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateUser()
    {
        $woowup = new WoowUp($_ENV['WOOWUP_API_KEY'], $_ENV['WOOWUP_API_HOST'], $_ENV['WOOWUP_API_VERSION']);

        $email = md5(microtime()) . '@email.com';

        $r = $woowup->users->create([
            'service_uid' => $email,
            'email'       => $email,
            'first_name'  => 'John',
            'last_name'   => 'Doe',
        ]);

        $this->assertEquals($r, true);
    }

    public function testExistUser()
    {
        $woowup = new WoowUp($_ENV['WOOWUP_API_KEY'], $_ENV['WOOWUP_API_HOST'], $_ENV['WOOWUP_API_VERSION']);

        $email = md5(microtime()) . '@email.com';

        $r = $woowup->users->create([
            'service_uid' => $email,
            'email'       => $email,
            'first_name'  => 'John',
            'last_name'   => 'Doe',
        ]);

        $this->assertEquals($r, true);

        $r = $woowup->users->exist($email);
        $this->assertEquals($r, true);
    }

    public function testNotExistUser()
    {
        $woowup = new WoowUp($_ENV['WOOWUP_API_KEY'], $_ENV['WOOWUP_API_HOST'], $_ENV['WOOWUP_API_VERSION']);

        $email = md5(microtime()) . '@email.com';

        $r = $woowup->users->exist($email);
        $this->assertEquals($r, false);
    }
}
