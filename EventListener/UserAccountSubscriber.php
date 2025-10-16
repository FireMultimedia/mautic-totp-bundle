<?php

namespace MauticPlugin\MauticTOTPBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;

use Symfony\Component\Routing\RouterInterface;

/**
 * <h1>Class UserAccountSubscriber</h1>
 *
 * @package MauticPlugin\MauticTOTPBundle\EventListener
 */
class UserAccountSubscriber implements EventSubscriberInterface {

    /**
     * <h2>UserSubscriber constructor.</h2>
     *
     * @param RouterInterface $router
     */
    public function __construct(
        private readonly RouterInterface $router
    ) {
        // silent constructor
    }

    /** {@inheritDoc} */
    public static function getSubscribedEvents() {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ["onViewInjectCustomContent", 0],
        ];
    }

    /**
     * <h2>onViewInjectCustomContent</h2>
     *   Add TOTP setup link to account view
     *
     * @param CustomContentEvent $event
     *
     * @return false|void
     */
    public function onViewInjectCustomContent(CustomContentEvent $event) {
        if($event->getViewName() !== "@MauticUser/Profile/index.html.twig")
            return;

        $configUrl = $this->router->generate("mautic_plugin_config", [
            "name" => "TOTP"
        ]);

        $setupButton = <<<HTML
<script>
    document.addEventListener("DOMContentLoaded", () => {
        if(document.getElementById("totp_setup_button") !== null)
            return;

        const totpSetupButton = document.createElement("li");

        totpSetupButton.id = "totp_setup_button";

        totpSetupButton.className = "list-group-item";
        totpSetupButton.innerHTML = `
            <a class="list-group-item-text steps"
               onclick="Mautic.loadAjaxModal('#MauticSharedModal', '{$configUrl}', 'GET', 'Two-factor Authentication')">
                <i class="ri-shield-keyhole-line"></i> Two-factor Authentication
            </a>
        `;

        document.getElementsByClassName("list-group")[0].appendChild(totpSetupButton);
    });
</script>
HTML;

        $event->addContent($setupButton);
    }

}
