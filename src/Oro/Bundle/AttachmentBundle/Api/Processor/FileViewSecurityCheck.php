<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Checks if it is allowed to view File entity.
 */
class FileViewSecurityCheck implements ProcessorInterface
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$this->authorizationChecker->isGranted('VIEW', new ObjectIdentity($context->get('id'), File::class))) {
            throw new AccessDeniedException();
        }
    }
}
