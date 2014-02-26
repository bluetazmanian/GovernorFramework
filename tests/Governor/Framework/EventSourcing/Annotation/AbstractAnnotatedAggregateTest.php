<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventSourcing\Annotation;

use Rhumsaa\Uuid\Uuid;
use Governor\Framework\Stubs\StubDomainEvent;
use Governor\Framework\Annotations\AggregateIdentifier;
use Governor\Framework\Annotations\EventSourcedMember;
use Governor\Framework\Annotations\EventHandler;

/**
 * Description of AbstractAnnotatedAggregateTest
 *
 * @author david
 */
class AbstractAnnotatedAggregateTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;

    public function testApplyEvent()
    {
        $this->testSubject = new SimpleAggregateRoot();

        $this->assertNotNull($this->testSubject->getIdentifier());
        $this->assertEquals(1, $this->testSubject->getUncommittedEventCount());
        // this proves that a newly added entity is also notified of an event
        $this->assertEquals(1, $this->testSubject->getEntity()->invocationCount);

        $this->testSubject->doSomething();

        $this->assertEquals(2, $this->testSubject->invocationCount);
        $this->assertEquals(2, $this->testSubject->getEntity()->invocationCount);
    }

    public function testIdentifierInitialization_LateInitialization()
    {
        $aggregate = new LateIdentifiedAggregate();
        $this->assertEquals("lateIdentifier", $aggregate->getIdentifier());
        $this->assertEquals("lateIdentifier",
            $aggregate->getUncommittedEvents()->peek()->getAggregateIdentifier());
    }

}

class LateIdentifiedAggregate extends AbstractAnnotatedAggregateRoot
{

    /**
     * @AggregateIdentifier
     */
    public $aggregateIdentifier;

    public function __construct()
    {
        $this->apply(new StubDomainEvent());
    }

    /**
     *  @EventHandler
     */
    public function myEventHandlerMethod(StubDomainEvent $event)
    {
        $this->aggregateIdentifier = "lateIdentifier";
    }

}

class SimpleAggregateRoot extends AbstractAnnotatedAggregateRoot
{

    public $invocationCount;

    /**
     * @EventSourcedMember
     */
    public $entity;

    /**
     * @AggregateIdentifier
     */
    public $identifier;

    public function __construct()
    {
        $this->identifier = Uuid::uuid1();
        $this->apply(new StubDomainEvent());
    }

    /**
     * @EventHandler
     */
    public function myEventHandlerMethod(StubDomainEvent $event)
    {
        $this->invocationCount++;
        if (null === $this->entity) {
            $this->entity = new SimpleEntity();
        }
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function doSomething()
    {
        $this->apply(new StubDomainEvent());
    }

}

class SimpleEntity extends AbstractAnnotatedEntity
{

    public $invocationCount;

    /**
     * @EventHandler
     */
    public function myEventHandlerMethod(StubDomainEvent $event)
    {
        $this->invocationCount++;
    }

}
