<?php

declare(strict_types=1);

namespace Tests\Prometee\SyliusPayumStripeCheckoutSessionPlugin\Behat\Mocker;

use Prometee\PayumStripeCheckoutSession\Action\Api\Resource\AbstractCreateAction;
use Prometee\PayumStripeCheckoutSession\Action\Api\Resource\AbstractRetrieveAction;
use Prometee\PayumStripeCheckoutSession\Request\Api\Resource\CreateSession;
use Prometee\PayumStripeCheckoutSession\Request\Api\Resource\RetrievePaymentIntent;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Sylius\Behat\Service\Mocker\MockerInterface;

final class StripeSessionCheckoutMocker
{
    /** @var MockerInterface */
    private $mocker;

    public function __construct(MockerInterface $mocker)
    {
        $this->mocker = $mocker;
    }

    public function mockCreatePayment(callable $action): void
    {
        $model = [
            'id' => 'sess_1',
        ];

        $mock = $this->mocker->mockService('tests.prometee.sylius_payum_stripe_checkout_session_plugin.behat.mocker.action.create_session', AbstractCreateAction::class);

        $mock
            ->shouldReceive('setApi')
            ->once()
        ;
        $mock
            ->shouldReceive('setGateway')
            ->once()
        ;

        $mock
            ->shouldReceive('supports')
            ->andReturnUsing(function ($request) use ($model) {
                return $request instanceof CreateSession;
            })
        ;

        $mock
            ->shouldReceive('execute')
            ->once()
            ->andReturnUsing(function (CreateSession $request) use ($model) {
                $request->setApiResource(Session::constructFrom($model));
            })
        ;

        $action();

        $this->mocker->unmockAll();
    }

    public function mockCancelledPayment(
        callable $notifyAction,
        callable $captureAction
    ): void
    {
        $this->mockPaymentIntentSync($notifyAction, PaymentIntent::STATUS_CANCELED);
        $this->mockPaymentIntentSync($captureAction, PaymentIntent::STATUS_CANCELED);
    }

    public function mockSuccessfulPayment(
        callable $notifyAction,
        callable $captureAction
    ): void
    {
        $this->mockPaymentIntentSync($notifyAction, PaymentIntent::STATUS_SUCCEEDED);
        $this->mockPaymentIntentSync($captureAction, PaymentIntent::STATUS_SUCCEEDED);
    }

    /**
     * @param callable $captureAction
     *
     * @see https://stripe.com/docs/payments/intents#payment-intent
     */
    public function mockPaymentIntentRequiredStatus(callable $captureAction)
    {
        $this->mockPaymentIntentSync($captureAction, PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD);
    }

    public function mockPaymentIntentSync(callable $action, string $status): void
    {
        $model = [
            'id' => 'pi_1',
            'object' => PaymentIntent::OBJECT_NAME,
            'status' => $status,
        ];

        $mock = $this->mocker->mockService('tests.prometee.sylius_payum_stripe_checkout_session_plugin.behat.mocker.action.retrieve_payment_intent', AbstractRetrieveAction::class);

        $mock
            ->shouldReceive('setApi')
            ->once()
        ;
        $mock
            ->shouldReceive('setGateway')
            ->once()
        ;

        $mock
            ->shouldReceive('supports')
            ->andReturnUsing(function ($request) use ($model) {
                return $request instanceof RetrievePaymentIntent;
            })
        ;

        $mock
            ->shouldReceive('execute')
            ->once()
            ->andReturnUsing(function (RetrievePaymentIntent $request) use ($model) {
                $request->setApiResource(PaymentIntent::constructFrom($model));
            })
        ;

        $action();

        $this->mocker->unmockAll();
    }
}
