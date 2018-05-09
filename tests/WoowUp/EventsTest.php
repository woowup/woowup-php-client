<?php
namespace WoowUpTest\WoowUp;

use WoowUp\Client as WoowUp;

/**
 *
 */
class EventsTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateEvent()
    {
        $woowup = new WoowUp(
            $_ENV['WOOWUP_API_KEY'],
            $_ENV['WOOWUP_API_HOST'],
            $_ENV['WOOWUP_API_VERSION']
        );

        $r = $woowup->events->create([
            'name' => 'test-event-' . md5(microtime()),
        ]);

        $this->assertEquals($r, true);
    }

    public function testAssignEventToUser()
    {
        $woowup = new WoowUp(
            $_ENV['WOOWUP_API_KEY'],
            $_ENV['WOOWUP_API_HOST'],
            $_ENV['WOOWUP_API_VERSION']
        );

        // creo el evento
        $event = 'test-event-' . md5(microtime());

        $r = $woowup->events->create([
            'name' => $event,
        ]);

        $this->assertEquals($r, true);

        // creo el cliente
        $email = md5(microtime()) . '@email.com';

        $r = $woowup->users->create([
            'service_uid' => $email,
            'email'       => $email,
            'first_name'  => 'John',
            'last_name'   => 'Doe',
        ]);

        $this->assertEquals($r, true);

        $woowup->userEvents->create([
            "event"       => $event,
            "service_uid" => $email,
            "datetime"    => date('c'),
            "metadata"    => [
                "campo 1" => "valor 1",
                "campo 2" => "valor 2",
            ],
        ]);
    }
}
