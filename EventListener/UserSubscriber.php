<?php

namespace MauticPlugin\MauticTOTPBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

use Mautic\PluginBundle\Helper\IntegrationHelper;

use MauticPlugin\MauticTOTPBundle\Integration\v5\TOTPIntegration as TOTPIntegrationv6;
use MauticPlugin\MauticTOTPBundle\Integration\v6\TOTPIntegration as TOTPIntegrationv5;

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

        // assume that Mautic developers use sane versioning
        $mauticVersion = str_replace(".", "", explode("-", (new AppVersion())->getVersion())[0]);

        switch(true) {
            case $mauticVersion >= 600:
                $integration = $this->integration->getIntegrationObject(TOTPIntegrationv6::INTEGRATION_NAME);
                break;
            case $mauticVersion >= 500:
                $integration = $this->integration->getIntegrationObject(TOTPIntegrationv5::INTEGRATION_NAME);
                break;
        }

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
