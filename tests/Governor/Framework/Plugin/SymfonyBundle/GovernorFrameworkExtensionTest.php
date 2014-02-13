<?php

namespace Governor\Framework\Plugin\SymfonyBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\GovernorFrameworkExtension;
use Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler\CommandHandlerPass;
use Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler\EventHandlerPass;
use Governor\Framework\Annotations\EventHandler;
use Governor\Framework\Annotations\CommandHandler;

class GovernorFrameworkExtensionTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;

    public function setUp()
    {
        $this->testSubject = $this->createTestContainer();
    }

    public function testRepositories()
    {
        $repo1 = $this->testSubject->get('dummy1.repository');
        $repo2 = $this->testSubject->get('dummy2.repository');

        $this->assertInstanceOf('Governor\Framework\Repository\RepositoryInterface',
                $repo1);
        $this->assertInstanceOf('Governor\Framework\Repository\RepositoryInterface',
                $repo2);
        $this->assertNotSame($repo1, $repo2);
        $this->assertEquals('Governor\Framework\Stubs\Dummy1Aggregate',
                $repo1->getClass());
        $this->assertEquals('Governor\Framework\Stubs\Dummy2Aggregate',
                $repo2->getClass());
    }

    public function testEventHandlers()
    {
        $eventBus = $this->testSubject->get('governor.event_bus');
        $eventListenerLocator = $this->testSubject->get('governor.event_listener_locator');

        $this->assertInstanceOf('Governor\Framework\EventHandling\EventBusInterface',
                $eventBus);
        $this->assertInstanceOf('Governor\Framework\EventHandling\EventListenerLocatorInterface',
                $eventListenerLocator);

        $reflProperty = new \ReflectionProperty($eventListenerLocator,
                'listeners');
        $reflProperty->setAccessible(true);

        $listeners = $reflProperty->getValue($eventListenerLocator);

        $this->assertCount(1, $listeners);
        $this->assertContainsOnlyInstancesOf('Governor\Framework\EventHandling\EventListenerInterface', $listeners);
    }

    public function createTestContainer()
    {
        $config = array('governor' => array('repositories' => array('dummy1' => array(
                        'aggregate_root' => 'Governor\Framework\Stubs\Dummy1Aggregate',
                        'type' => 'doctrine'), 'dummy2' => array('aggregate_root' => 'Governor\Framework\Stubs\Dummy2Aggregate',
                        'type' => 'doctrine'))
                , 'aggregate_command_handlers' => array('dummy1' => array('aggregate_root' => 'Governor\Framework\Stubs\Dummy1Aggregate',
                        'repository' => 'dummy1.repository'),
                    'dummy2' => array('aggregate_root' => 'Governor\Framework\Stubs\Dummy2Aggregate',
                        'repository' => 'dummy2.repository'))));

        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.debug' => false,
            'kernel.bundles' => array(),
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../../../' // src dir
        )));

        $loader = new GovernorFrameworkExtension();
        $container->registerExtension($loader);
        $container->set('doctrine.orm.default_entity_manager',
                $this->getMock('Doctrine\ORM\EntityManager',
                        array(
                    'find', 'flush', 'persist', 'remove'), array(), '', false));

        $this->addTaggedCommandHandlers($container);
        $this->addTaggedEventListeners($container);

        $loader->load($config, $container);

        $container->addCompilerPass(new CommandHandlerPass(),
                PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new EventHandlerPass(),
                PassConfig::TYPE_BEFORE_REMOVING);
        $container->compile();

        return $container;
    }

    private function addTaggedCommandHandlers(ContainerBuilder $container)
    {
        $definition = new Definition(new ContainerCommandHandler1());
        $definition->addTag('governor.command_handler')
                ->setPublic(true);

        $container->setDefinition('test.command_handler', $definition);
    }

    private function addTaggedEventListeners(ContainerBuilder $container)
    {
        $definition = new Definition(new ContainerEventListener1());
        $definition->addTag('governor.event_handler')
                ->setPublic(true);

        $container->setDefinition('test.event_handler', $definition);
    }

}

class ContainerCommand1
{
    
}

class ContainerEvent1
{
    
}

class ContainerCommandHandler1
{

    /**
     * @CommandHandler
     */
    public function onCommand1(ContainerCommand1 $command)
    {
        
    }

}

class ContainerEventListener1
{

    /**
     * @EventHandler
     */
    public function onEvent1(ContainerEvent1 $event)
    {
        
    }

}
