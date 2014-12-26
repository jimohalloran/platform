<?php

namespace Oro\Bundle\SecurityBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;

class AclPermissionController extends Controller
{
    /**
     * @Route(
     *  "/acl-access-levels/{oid}",
     *  name="oro_security_access_levels",
     *  requirements={"oid"="\w+:[\w\(\)]+"},
     *  defaults={"_format"="json"}
     * )
     * @Template
     *
     * @param string $oid
     *
     * @return array
     */
    public function aclAccessLevelsAction($oid)
    {
        if (strpos($oid, 'entity:') === 0) {
            $oid = $this->get('oro_entity.routing_helper')->decodeClassName($oid);
        }

        $levels = $this
            ->get('oro_security.acl.manager')
            ->getAccessLevels($oid);

        return ['levels' => $levels];
    }

    /**
     * @Route(
     *      "/switch-organization/{id}",
     *      name="oro_security_switch_organization", defaults={"id"=0}
     * )
     * @ParamConverter("organization", class="OroOrganizationBundle:Organization")
     *
     * @param Organization $organization
     *
     * @return RedirectResponse , AccessDeniedException
     */
    public function switchOrganizationAction(Organization $organization)
    {
        $token = $this->container->get('security.context')->getToken();

        if (!$token instanceof OrganizationContextTokenInterface ||
            !$token->getUser() instanceof User ||
            !$organization->isEnabled() ||
            !$token->getUser()->getOrganizations()->contains($organization)
        ) {
            throw new AccessDeniedException(
                $this->get('translator')->trans(
                    'oro.security.organization.access_denied',
                    ['%organization_name%' => $organization->getName()]
                )
            );
        }

        $token->setOrganizationContext($organization);
        return $this->redirect($this->generateUrl('oro_default'));
    }
}
