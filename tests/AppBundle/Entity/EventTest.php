<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\Event;

class EventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Event
     */
    protected $event;

    public function setUp()
    {
        $this->event = new Event();
    }

    public function testSetGetValueRegular()
    {
        // regular string
        $this->event->setValue('value');
        $this->assertEquals('value', $this->event->getValue());

        // regular non shot string
        $this->event->setValue('A2|sunk');
        $this->assertEquals('A2|sunk', $this->event->getValue());

        // regular int
        $this->event->setValue(123);
        $this->assertEquals(123, $this->event->getValue());

        // regular bool
        $this->event->setValue(false);
        $this->assertEquals(false, $this->event->getValue());
    }

    public function testSetGetValueTrim()
    {
        // string to trim
        $this->event->setValue("\t value \n");
        $this->assertEquals('value', $this->event->getValue());
    }

    public function testSetGetValueRegularShot()
    {
        $this->event->setType(Event::TYPE_SHOT);

        // string
        $this->event->setValue('A1');
        $this->assertEquals('A1', $this->event->getValue());

        // string with result
        $this->event->setValue('A2|sunk');
        $this->assertEquals('A2', $this->event->getValue());

        // array
        $this->event->setValue(['A2', 'sunk']);
        $this->assertEquals('A2', $this->event->getValue());
    }

    public function testSetGetValueTrimShot()
    {
        $this->event->setType(Event::TYPE_SHOT);

        // string to trim
        $this->event->setValue('A1');
        $this->assertEquals('A1', $this->event->getValue());

        // string with result to trim
        $this->event->setValue('A2|sunk');
        $this->assertEquals('A2', $this->event->getValue());

        // array to trim
        $this->event->setValue(["\t A3 \n", '  hit  ']);
        $this->assertEquals('A3', $this->event->getValue());
    }
}
