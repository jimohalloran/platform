<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormType;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Embedded Form Controller
 */
class EmbeddedFormController extends AbstractController
{
    /**
     * @Route(name="oro_embedded_form_list")
     * @Template()
     * @AclAncestor("oro_embedded_form_view")
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("create", name="oro_embedded_form_create")
     * @Template("OroEmbeddedFormBundle:EmbeddedForm:update.html.twig")
     * @Acl(
     *      id="oro_embedded_form_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroEmbeddedFormBundle:EmbeddedForm"
     * )
     */
    public function createAction()
    {
        return $this->update();
    }

    /**
     * @Route("delete/{id}", name="oro_embedded_form_delete", requirements={"id"="[-\d\w]+"}, methods={"DELETE"})
     * @Acl(
     *      id="oro_embedded_form_delete",
     *      type="entity",
     *      permission="DELETE",
     *      class="OroEmbeddedFormBundle:EmbeddedForm"
     * )
     * @CsrfProtection()
     */
    public function deleteAction(EmbeddedForm $entity)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $em->remove($entity);
        $em->flush();

        return new JsonResponse('', Response::HTTP_OK);
    }

    /**
     * @Route("default-data/{formType}", name="oro_embedded_form_default_data", methods={"GET"})
     * @AclAncestor("oro_embedded_form_create")
     */
    public function defaultDataAction(string $formType)
    {
        $formType = str_replace('_', '\\', $formType);
        $formManager = $this->getFormManager();
        $css = $formManager->getDefaultCssByType($formType);
        $successMessage = $formManager->getDefaultSuccessMessageByType($formType);

        return new JsonResponse(
            [
                'css'            => $css,
                'successMessage' => $successMessage
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @Route("update/{id}", name="oro_embedded_form_update", requirements={"id"="[-\d\w]+"})
     * @Template()
     * @Acl(
     *      id="oro_embedded_form_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroEmbeddedFormBundle:EmbeddedForm"
     * )
     */
    public function updateAction(EmbeddedForm $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route("view/{id}", name="oro_embedded_form_view", requirements={"id"="[-\d\w]+"})
     * @Template()
     * @Acl(
     *      id="oro_embedded_form_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroEmbeddedFormBundle:EmbeddedForm"
     * )
     */
    public function viewAction(EmbeddedForm $entity)
    {
        return [
            'entity' => $entity,
            'label' => $this->getFormManager()->get($entity->getFormType())
        ];
    }

    /**
     * @Route("info/{id}", name="oro_embedded_form_info", requirements={"id"="[-\d\w]+"})
     * @AclAncestor("oro_embedded_form_view")
     * @Template()
     */
    public function infoAction(EmbeddedForm $entity)
    {
        return array(
            'entity'  => $entity
        );
    }

    /**
     * @param EmbeddedForm $entity
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function update(EmbeddedForm $entity = null)
    {
        if (!$entity) {
            $entity = new EmbeddedForm();
        }

        $form = $this->createForm(EmbeddedFormType::class, $entity);
        $form->handleRequest($this->get('request_stack')->getCurrentRequest());
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.embeddedform.controller.saved_message')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        $formManager = $this->getFormManager();
        return array(
            'entity' => $entity,
            'defaultCss' => $formManager->getDefaultCssByType($entity->getFormType()),
            'defaultSuccessMessage' => $formManager->getDefaultSuccessMessageByType($entity->getFormType()),
            'form' => $form->createView()
        );
    }

    /**
     * @return EmbeddedFormManager
     */
    protected function getFormManager()
    {
        return $this->get('oro_embedded_form.manager');
    }
}
