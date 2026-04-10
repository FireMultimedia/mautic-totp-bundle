<?php declare(strict_types=1);

namespace MauticPlugin\MauticTOTPBundle\Controller\v5;

use Mautic\CoreBundle\Controller\CommonController;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

use Mautic\PluginBundle\Helper\IntegrationHelper;

use MauticPlugin\MauticTOTPBundle\Helper\AuthenticatorHelper;
use MauticPlugin\MauticTOTPBundle\Integration\v5\TOTPIntegration;

use Doctrine\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Mautic\CoreBundle\Helper\AppVersion;

// assume that Mautic developers use sane versioning
$mauticVersion = str_replace(".", "", explode("-", (new AppVersion())->getVersion())[0]);

$mauticVersion = str_split((string)$mauticVersion);

if($mauticVersion[0] < 6) {
    class AuthController extends CommonController {

        /**
         * <h2>AuthController constructor.</h2>
         *
         * @param ManagerRegistry          $doctrine
         * @param MauticFactory            $factory
         * @param ModelFactory             $modelFactory
         * @param UserHelper               $userHelper
         * @param CoreParametersHelper     $coreParametersHelper
         * @param EventDispatcherInterface $dispatcher
         * @param Translator               $translator
         * @param FlashBag                 $flashBag
         * @param RequestStack|null        $requestStack
         * @param CorePermissions|null     $security
         * @param IntegrationHelper        $integrationHelper
         */
        public function __construct(
            protected ManagerRegistry          $doctrine,
            protected MauticFactory            $factory,
            protected ModelFactory             $modelFactory,
            UserHelper                         $userHelper,
            protected CoreParametersHelper     $coreParametersHelper,
            protected EventDispatcherInterface $dispatcher,
            protected Translator               $translator,
            private FlashBag                   $flashBag,
            private ?RequestStack              $requestStack,
            protected ?CorePermissions         $security,
            protected IntegrationHelper        $integrationHelper
        ) {
            parent::__construct(
                $doctrine,
                $factory,
                $modelFactory,
                $userHelper,
                $coreParametersHelper,
                $dispatcher,
                $translator,
                $flashBag,
                $requestStack,
                $security
            );
        }

        /**
         * <h2>authAction</h2>
         *
         * @param Request $request
         *
         * @return RedirectResponse|Response
         */
        public function authAction(Request $request) {
            if(!$this->isCsrfTokenValid("totp", $request->request->get("_csrf_token"))) {
                return $this->delegateView([
                    "contentTemplate" => "@MauticTOTP/AuthView/form.html.twig",
                    "viewParameters"  => []
                ]);
            }

            $integration = $this->integrationHelper->getIntegrationObject(TOTPIntegration::INTEGRATION_NAME);

            $secret = $integration->getSecret();
            $code   = $request->request->get("_code");

            $authHelper = new AuthenticatorHelper();

            if(!$authHelper->checkCode($secret, $code)) {
                return $this->delegateView([
                    "contentTemplate" => "@MauticTOTP/AuthView/form.html.twig",

                    "viewParameters" => [
                        "error" => $this->translator->trans("mautic.plugin.totp.totp_invalid")
                    ]
                ]);
            }

            $request->getSession()->set("otp_granted", true);

            return new RedirectResponse("dashboard");
        }

    }
} else {
    class AuthController extends CommonController {
        public function authAction(Request $request) {
            // stub
        }
    }
}
