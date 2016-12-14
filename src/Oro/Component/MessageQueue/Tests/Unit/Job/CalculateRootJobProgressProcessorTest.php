<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\CalculateRootJobProgressProcessor;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\CalculateRootJobProgressService;
use Psr\Log\LoggerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Job\Topics;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class CalculateRootJobProgressProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCanCreateInstanceViaConstructor()
    {
        $calculateRootJobProgressProcessor = new CalculateRootJobProgressProcessor(
            $this->createJobStorageMock(),
            $this->createCalculateRootJobProgressServiceMock(),
            $this->createMessageProducerMock(),
            $this->createLoggerMock()
        );

        $this->assertInstanceOf(MessageProcessorInterface::class, $calculateRootJobProgressProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $calculateRootJobProgressProcessor);
    }

    public function testCheckingSubscribedTopicNames()
    {
        $this->assertEquals(
            [Topics::CALCULATE_ROOT_JOB_PROGRESS],
            CalculateRootJobProgressProcessor::getSubscribedTopics()
        );
    }

    public function testShouldReturnRejectMessageAndLogErrorIfJobIdNotFoundInMessageBody()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message. body: ""')
        ;

        $message = $this->createMessageMock();
        $message
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn('')
        ;

        $processor = new CalculateRootJobProgressProcessor(
            $this->createJobStorageMock(),
            $this->createCalculateRootJobProgressServiceMock(),
            $this->createMessageProducerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldReturnRejectMessageAndLogErrorIfJobFound()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Job was not found. id: "11111"')
        ;

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['jobId' => 11111]))
        ;

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('findJobById')
            ->with('11111')
        ;

        $processor = new CalculateRootJobProgressProcessor(
            $jobStorage,
            $this->createCalculateRootJobProgressServiceMock(),
            $this->createMessageProducerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }


    public function testShouldReturnACKMessage()
    {
        $job = new Job();
        $job->setId(11111);

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['jobId' => 11111]))
        ;

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('findJobById')
            ->willReturn($job)
            ->with('11111')
        ;

        $calculateProgressService = $this->createCalculateRootJobProgressServiceMock();
        $calculateProgressService
            ->expects($this->once())
            ->method('calculateRootJobProgress')
            ->with($this->identicalTo($job))
        ;

        $processor = new CalculateRootJobProgressProcessor(
            $jobStorage,
            $calculateProgressService,
            $this->createMessageProducerMock(),
            $logger
        );
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createMessageMock()
    {
        return $this->getMock(MessageInterface::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->getMock(JobStorage::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CalculateRootJobProgressService
     */
    private function createCalculateRootJobProgressServiceMock()
    {
        return $this->getMock(CalculateRootJobProgressService::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }
}
