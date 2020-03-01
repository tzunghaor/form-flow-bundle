<?php

namespace Tzunghaor\FormFlowBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tzunghaor\FormFlowBundle\Exception\AlreadyFinishedException;
use Tzunghaor\FormFlowBundle\Exception\StepNotFoundException;
use Tzunghaor\FormFlowBundle\FormFlow\FormFlowNavigator;

class DefaultController extends Controller
{
    /**
     * @Route(
     *     "/{flowName}/{step}/{instanceId}", name="tzunghaor_formflow_index",
     *     defaults={"instanceId":"", "step":""}
     * )
     *
     * @param Request $request
     * @param string $flowName name of the form flow as it appears in your config.yml
     * @param string $instanceId
     * @param string $step
     *
     * @return Response
     *
     * @throws AlreadyFinishedException
     * @throws StepNotFoundException
     */
    public function indexAction(Request $request, string $flowName, string $instanceId, string $step) {
        // get the flow based on the flow name parameter
        $flow = $this->get('tzunghaor_form_flow.form_flow_locator')->getFormFlow($flowName);

        // initialize the flow with the request
        $flow->handleRequest($request);

        // $flow->canProceedAndSave() is analogous to $form->isSubmitted() && $form->isValid()
        if ($flow->canProceedAndSave()) {
            // update flow state
            if ($flow->proceedToRequestedStepAndSave() === FormFlowNavigator::NAV_FINISHED) {
                if (($finishedRoute = $flow->getFinishedRoute()) !== '') {
                    // if flow finished, and there is no finished route defined, then redirect there
                    $redirectUrl = $this->generateUrl($finishedRoute, $flow->getRouteParameters());
                } else {
                    // if flow finished, but there is no finished route defined, then start new flow instance
                    $redirectUrl = $this->generateUrl('tzunghaor_formflow_index', ['flowName' => $flowName]);
                }
            } else {
                // if not finished, redirect to continue the form (POST - redirect - GET pattern)
                $redirectUrl = $this->generateUrl('tzunghaor_formflow_index', $flow->getRouteParameters());
            }

            return $this->redirect($redirectUrl);
        }

        // We reach here if the request has no form submitted, or if the submit cant be processed

        // Update URL if instanceId or step request parameter is invalid (e.g. requesting an inaccessible step)
        if ($instanceId !== $flow->getInstanceId() || $step !== $flow->getCurrentStepName()) {
            return $this->redirectToRoute('tzunghaor_formflow_index', $flow->getRouteParameters());
        }

        // Render the form flow
        $formView = $flow->createView();

        return $this->render($formView->getView(), $formView->getViewVariables());
    }

}
