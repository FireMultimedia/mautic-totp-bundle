<?php declare(strict_types=1);

namespace MauticPlugin\MauticTOTPBundle\EventListener;

use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticTOTPBundle\Integration\TOTPIntegration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class UserSubscriber.
 *
 * @author Henrique Rodrigues <henrique@hostnet.com.br>
 *
 * @see https://www.hostnet.com.br
 */
class UserSubscriber implements EventSubscriberInterface {

    /**
     * <h2>UserSubscriber constructor.</h2>
     *
     * @param RouterInterface   $router
     * @param CorePermissions   $security
     * @param IntegrationHelper $integration
     */
    public function __construct(
        private readonly RouterInterface   $router,
        private readonly CorePermissions   $security,
        private readonly IntegrationHelper $integration
    ) {
        // silent constructor
    }

    /** {@inheritDoc} */
    public static function getSubscribedEvents() {
        return [
            KernelEvents::REQUEST => ["onKernelRequest", 0],
        ];
    }

    /**
     * <h2>onKernelRequest</h2>
     *   verifies if the user is authenticated and gives the right response.
     *
     * @param RequestEvent $event
     *
     * @return false|void
     */
    public function onKernelRequest(RequestEvent $event) {
        if(!$event->isMainRequest())
            return false;

        $integration = $this->integration->getIntegrationObject(TOTPIntegration::INTEGRATION_NAME);

        if(!$integration)
            return false;

        $published = $integration->getIntegrationSettings()->getIsPublished();

        if(!$published || !$integration->isConfigured() || !$integration->isActive())
            return false;

        $request = $event->getRequest();

        if(!$this->security->isAnonymous() && !preg_match("/api|login|otp|totp/i", $request->getRequestUri()) && !$request->getSession()->get("otp_granted")) {
            $request->getSession()->set("otp_granted", false);

            $event->setResponse(new RedirectResponse($this->router->generate("otp_auth")));
        }
    }

}
