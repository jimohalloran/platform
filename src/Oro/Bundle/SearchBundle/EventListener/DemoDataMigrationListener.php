<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;

class DemoDataMigrationListener
{
    /**
     * @var EngineInterface
     */
    protected $searchEngine;

    /**
     * @var IndexListener
     */
    protected $searchListener;

    /**
     * @param EngineInterface $searchEngine
     * @param IndexListener $searchListener
     */
    public function __construct(EngineInterface $searchEngine, IndexListener $searchListener)
    {
        $this->searchEngine = $searchEngine;
        $this->searchListener = $searchListener;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if ($this->isProcessingRequired($event)) {
            $this->searchListener->setEnabled(false);
        }
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if ($this->isProcessingRequired($event)) {
            $this->searchEngine->reindex();
            $this->searchListener->setEnabled(true);
        }
    }

    /**
     * @param ConsoleEvent $event
     * @return bool
     */
    protected function isProcessingRequired(ConsoleEvent $event)
    {
        return $event->getCommand() instanceof LoadDataFixturesCommand
            && $event->getInput()->getOption('fixtures-type') == LoadDataFixturesCommand::DEMO_FIXTURES_TYPE;
    }
}
