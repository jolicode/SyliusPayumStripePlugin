<?php

declare(strict_types=1);

namespace Tests\Prometee\SyliusPayumStripeCheckoutSessionPlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\PaymentIntent;
use Sylius\Behat\Page\Shop\Checkout\CompletePageInterface;
use Sylius\Behat\Page\Shop\Order\ShowPageInterface;
use Tests\Prometee\SyliusPayumStripeCheckoutSessionPlugin\Behat\Mocker\StripeSessionCheckoutMocker;
use Tests\Prometee\SyliusPayumStripeCheckoutSessionPlugin\Behat\Page\External\StripeCheckoutPage;

class StripeShopContext extends MinkContext implements Context
{
    /** @var StripeSessionCheckoutMocker */
    private $stripeSessionCheckoutMocker;

    /** @var CompletePageInterface */
    private $summaryPage;

    /** @var ShowPageInterface */
    private $orderDetails;

    /** @var StripeCheckoutPage */
    private $paymentPage;

    public function __construct(
        StripeSessionCheckoutMocker $stripeSessionCheckoutMocker,
        CompletePageInterface $summaryPage,
        ShowPageInterface $orderDetails,
        StripeCheckoutPage $paymentPage
    ) {
        $this->stripeSessionCheckoutMocker = $stripeSessionCheckoutMocker;
        $this->summaryPage = $summaryPage;
        $this->orderDetails = $orderDetails;
        $this->paymentPage = $paymentPage;
    }

    /**
     * @When I confirm my order with Stripe payment
     * @Given I have confirmed my order with Stripe payment
     */
    public function iConfirmMyOrderWithStripePayment()
    {
        $this->stripeSessionCheckoutMocker->mockCreatePayment(function () {
            $this->summaryPage->confirmOrder();
        });
    }

    /**
     * @When I get redirected to Stripe and complete my payment
     */
    public function iGetRedirectedToStripe(): void
    {
        $this->stripeSessionCheckoutMocker->mockSuccessfulPayment(
            function () {
                $jsonEvent = [
                    'id' => 'evt_00000000000000',
                    'type' => Event::CHECKOUT_SESSION_COMPLETED,
                    'object' => 'event',
                    'data' => [
                        'object' => [
                            'id' => 'cs_00000000000000',
                            'object' => Session::OBJECT_NAME,
                            'payment_intent' => 'pi_00000000000000',
                            'metadata' => [
                                'token_hash' => '%s',
                            ],
                        ],
                    ],
                ];
                $payload = json_encode($jsonEvent);

                $this->paymentPage->notify($payload);
            },
            function () {
                $this->paymentPage->capture();
            }
        );
    }

    /**
     * @When I cancel my Stripe payment
     * @Given I have cancelled Stripe payment
     */
    public function iCancelMyStripePayment()
    {
        $this->stripeSessionCheckoutMocker->mockCancelledPayment(function () {
            $jsonEvent = [
                'id' => 'evt_11111111111111',
                'type' => Event::PAYMENT_INTENT_CANCELED,
                'object' => 'event',
                'data' => [
                    'object' => [
                        'id' => 'pi_00000000000000',
                        'object' => PaymentIntent::OBJECT_NAME,
                        'metadata' => [
                            'token_hash' => '%s',
                        ],
                    ],
                ],
            ];
            $payload = json_encode($jsonEvent);

            $this->paymentPage->notify($payload);
        },
        function () {
            $this->paymentPage->capture();
        });
    }

    /**
     * @When I try to pay again Stripe payment
     */
    public function iTryToPayAgainStripePayment(): void
    {
        $this->stripeSessionCheckoutMocker->mockCreatePayment(function () {
            $this->orderDetails->pay();
        });
    }

    /**
     * @Given /^I never fill any credit card field on my Stripe payment$/
     */
    public function iNeverFillAnyCreditCardFieldOnMyStripePayment()
    {
        $this->stripeSessionCheckoutMocker->mockPaymentIntentRequiredStatus(function() {
            $this->paymentPage->capture();
        });
    }
}
