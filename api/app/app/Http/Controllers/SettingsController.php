<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\UserApp;
use App\Models\VoiceCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManagerStatic as Image;

class SettingsController extends Controller
{
    /**
     * get my apps with enable & disable status
     */
    public function getMyApps()
    {
        try {
            $settings = Setting::where('type', 'apps')->first();
            $userApps = UserApp::where('user_id', auth()->id())->where('type', 'apps')->first();
            $myApps = $userApps ? $userApps->enabled_apps : [];

            $this->_response['data'] = array_merge($settings->enabled_apps, $myApps);
            $this->setResponse(false, null);
            return response()->json($this->_response);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function removeZoomApp()
    {
        try {
            // $settings = Setting::where('type', 'apps')->first();
            $userApps = UserApp::where("user_id", auth()->id())->where('type', 'apps');
            if ($userApps->exists()) {
                $userApps = $userApps->first();
                if (in_array('zoom', $userApps->enabled_apps)) {
                    $userApps->pull('enabled_apps', 'zoom');
                }
            }

            //deauthorize zoom access token
            // deauthorizeZoomAccessToken();

            // Setting::where('type', 'zoom_token')->delete();
            UserApp::where("user_id", auth()->id())->where('type', 'zoom_token')->delete();

            $this->setResponse(false, 'Zoom Uninstalled Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function integrateCustomerModule($status)
    {
        $fields = ['status' => $status];
        $validator = Validator::make($fields, [
            'status' => 'required|in:enable,disable',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $settings = Setting::where('type', 'customer')->first();
            $settings->is_enabled = $status == 'enable' ? true : false;
            $settings->save();

            $this->setResponse(false, 'Customer ' . ucfirst($status) . 'd Successfully.');
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function isCustomerEnabled()
    {
        $settings = Setting::where('type', 'customer')->first();

        return response()->json([
            "error" => false,
            "data" => ["is_enabled" => $settings->is_enabled],
            "message" => null
        ]);
    }

    public function updateCompanyProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|mimes:jpg,png',
            // 'logo' => 'dimensions:min_width=100,min_height=200'
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {

            $file = $request->file('logo');
            $ext = $file->getClientOriginalExtension();

            $path = $request->file('logo')->storeAs(
                'company_logo',
                getUniqueStamp() . '.' . $ext,
                'public'
            );

            if (Storage::disk('public')->exists(app('tenant')->business_logo)) {
                Storage::delete(app('tenant')->business_logo);
            }

            $tenant = app('tenant');
            $tenant->business_logo = $path;
            $tenant->save();

            $this->setResponse(false, "Company logo updated.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function changeLanguage($language)
    {
        $params = ["language" => $language];
        $validator = Validator::make($params, [
            'language' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $recordExists = Setting::where("type", "enabled_vc_languages")->count();
            if ($recordExists == 0) {
                $recordExists = Setting::create([
                    "type" => "enabled_vc_languages",
                    "languages" => ['en']
                ]);
            }

            //check already is already language enabled 
            $isEnabled = Setting::where("type", "enabled_vc_languages")->where('languages', $language)->exists();
            $vcExists = VoiceCommand::where('lang', $language)->exists(); //for old customers check
            if (!$isEnabled) {
                //import vc_commands
                if (!$vcExists) {
                    $vcCommandCsv = 'vc_languages' . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . "{$language}.csv";
                    if (Storage::disk('local')->exists($vcCommandCsv)) {
                        $csvFile = Storage::disk('local')->path($vcCommandCsv);
                        $file = fopen($csvFile, 'r');

                        fgetcsv($file); // to skip first row
                        while (($data = fgetcsv($file, 15000, ",")) !== FALSE) {
                            VoiceCommand::create([
                                'command' => $data[0],
                                'lang' => $data[1],
                                'sub_module' => $data[2],
                            ]);
                        }
                    }
                }

                //add language as enabled to settings
                $settings = Setting::where("type", "enabled_vc_languages")->first();
                $settings->languages = array_unique(array_merge([$language], $settings->languages));
                $settings->save();
            }

            $this->setResponse(false, "Voice Commands Added Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function customerFormLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'link' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $settings = Setting::where('type', 'customer')->first();
            $settings->form_link = $request->link;
            $settings->save();

            $this->setResponse(false, "Link has been updated successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getCustomerFormLink()
    {
        try {
            $settings = Setting::where('type', 'customer')->first();

            $this->_response['data'] = [
                "link" => $settings->form_link ?? null
            ];

            $this->setResponse(false, "");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
