<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class FcmGenerateDynamicLink
{
    public function create($link)
    {
        $requestData = json_encode([
            'dynamicLinkInfo' => [
                "domainUriPrefix" => env('FIREBASE_DYNAMIC_LINKS_DOMAIN'),
                "link" => $link, //https://yaraai.com/?resetCode=9d3e4af3ec652d4be0eb4506a4817ef16c727185c59656c5e23851e9519b9ef3
                "androidInfo" => [
                    "androidPackageName" => env('FCM_ANDROID_PACKAGE')
                ],
                "iosInfo" => [
                    "iosBundleId" => env('FCM_IOS_BUNDLE_ID'),
                    "iosIpadBundleId" => env('FCM_IOS_BUNDLE_ID'),
                    "iosAppStoreId" => env('FCM_IOS_APP_STORE_ID'),
                ]
            ]
        ]);

        $requestData = stripslashes($requestData);

        $client = new Client([
            'base_uri' => 'https://firebasedynamiclinks.googleapis.com/v1/shortLinks?key=' . env('FIREBASE_API_KEY')
        ]);

        try {
            $request = $client->request('POST', '', [
                'body' => $requestData
            ]);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), $e->getCode());
        }

        return json_decode($request->getBody(), true);
    }
}
