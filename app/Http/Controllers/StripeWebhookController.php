<?php

namespace App\Http\Controllers;

use Stripe\StripeClient;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\CreateTenantJob;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use App\Mail\SendStripeOnboardingSuccessfulMail;

class StripeWebhookController extends Controller
{
    /**
     * Handle a Stripe webhook call.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $method = 'handle' . Str::studly(str_replace('.', '_', $payload['type']));

        // WebhookReceived::dispatch($payload);

        if (method_exists($this, $method)) {
            // $this->setMaxNetworkRetries();

            $response = $this->{$method}($payload);

            // WebhookHandled::dispatch($payload);

            return $response;
        }

        return $this->missingMethod($payload);
    }

    /**
     * Handle customer subscription created.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        $productId = $payload['data']['object']['plan']['product'];

        /** check the subscription is created in product yaraa */
        if ($productId == config('services.stripe.product_id')) {

            $customerId = $payload['data']['object']['customer'];
            $userLimit = $payload['data']['object']['plan']['metadata']['user_limit'] ?? 10;
            $subscriptionId = $payload['data']['object']['id'];

            $stripe = new StripeClient(config('services.stripe.secret'));
            $customer = $stripe->customers->retrieve($customerId, []);
            $firstName = trim(explode(" ", $customer->name)[0]);
            $randomPassword = Str::random(10);

            $data["business_name"] = $firstName . "'s Company";
            $data["account_user_id"] = null;
            $data["domain"] = $firstName . getUniqueStamp();
            $data["user_limit"] = intval($userLimit);
            $data["email"] = $customer->email;
            $data["password"] = $randomPassword;
            $data["name"] = $customer->name;
            $data["country"] = $customer->address->country;
            $data["subscription_id"] = $subscriptionId;
            $data["provider"] = "stripe";
            $data["cancelled_after_days"] = 90;

            dispatch(new CreateTenantJob($data));

            $mailData = [
                "name" => $customer->name,
                "email" => $customer->email,
                "password" => $randomPassword
            ];

            /** queue an email to send to customer **/
            Mail::to($customer->email)->send(new SendStripeOnboardingSuccessfulMail($mailData));
        }

        return response()->json('Webhook Handled', 200);
    }


    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function missingMethod($parameters = [])
    {
        return new Response;
    }
}
