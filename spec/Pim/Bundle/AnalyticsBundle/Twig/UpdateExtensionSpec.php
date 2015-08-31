<?php

namespace spec\Pim\Bundle\AnalyticsBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PhpSpec\ObjectBehavior;
use Pim\Bundle\AnalyticsBundle\UrlGenerator\UrlGeneratorInterface;
use Prophecy\Argument;

class UpdateExtensionSpec extends ObjectBehavior
{
    function let(ConfigManager $configManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->beConstructedWith($configManager, $urlGenerator);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Bundle\AnalyticsBundle\Twig\UpdateExtension');
    }

    function it_indicates_if_last_patch_should_be_fetched($configManager)
    {
        $configManager->get('pim_analytics.version_update')->willReturn(true);

        $this->isLastPatchEnabled()->shouldReturn(true);
    }

    function it_provides_last_patch_url_should_be_fetched($urlGenerator)
    {
        $urlGenerator->generateUrl()->willReturn('http://test/CE-1.4');

        $this->getLastPatchUrl()->shouldReturn('http://test/CE-1.4');
    }

    function it_has_a_name()
    {
        $this->getName()->shouldBe('pim_notification_update_extension');
    }
}
