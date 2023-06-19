<?php

namespace App\Http\Controllers;

use App\Http\Resources\VoiceModuleListResource;
use App\Http\Resources\VoiceSubModuleListResource;
use App\Models\VoiceCommandModule;
use App\Models\VoiceCommandSubModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoiceCommandModuleController extends Controller
{
    public function createModule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\@']+$/u|max:200",
            'module' => "required|regex:/^[\pL\pN\s\-\_]+$/u|max:200",
            'lang' => "required|regex:/^[\pL\pN\s]+$/u|max:10",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            VoiceCommandModule::create([
                'title' => $request->title,
                'module' => $request->module,
                'lang' => $request->lang,
            ]);

            $this->setResponse(false, "Voice Module Created Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function updateModule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => "required|exists:voice_command_modules,_id",
            'title' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\@']+$/u|max:200",
            // 'module' => "required|regex:/^[\pL\pN\s\-\_]+$/u|max:200",
            'lang' => "required|regex:/^[\pL\pN\s]+$/u|max:10",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $vcModule = VoiceCommandModule::find($request->id);

            $vcModule->title = $request->title;
            // $vcModule->module = $request->module;
            $vcModule->lang = $request->lang;
            $vcModule->save();

            $this->setResponse(false, "Voice Module Updated Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function createSubModule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\@']+$/u|max:200",
            'module' => "required|regex:/^[\pL\pN\-\_]+$/u|exists:voice_command_modules,module",
            'sub_module' => "required|regex:/^[\pL\pN\s\-\_]+$/u|max:200",
            'lang' => "required|regex:/^[\pL\pN\s]+$/u|max:10",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            VoiceCommandSubModule::create([
                'title' => $request->title,
                'module' => $request->module,
                'sub_module' => $request->sub_module,
                'lang' => $request->lang,
            ]);

            $this->setResponse(false, "Voice Sub Module Created Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function updateSubModule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => "required|exists:voice_command_sub_modules,_id",
            'title' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\@']+$/u|max:200",
            'module' => "required|regex:/^[\pL\pN\s\-\_]+$/u|max:200",
            // 'sub_module' => "required|regex: /^[\pL\pN\s\-\_]+$/u|max:200",
            'lang' => "required|regex:/^[\pL\pN\s]+$/u|max:10",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $vcModule = VoiceCommandSubModule::find($request->id);

            $vcModule->title = $request->title;
            $vcModule->module = $request->module;
            // $vcModule->sub_module = $request->sub_module;
            $vcModule->lang = $request->lang;
            $vcModule->save();

            $this->setResponse(false, "Voice Sub Module Updated Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getModulesList($lang)
    {
        try {
            $modules = VoiceCommandModule::where('lang', $lang)->get();
            return (VoiceModuleListResource::collection($modules))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getSubModulesList($module, $lang)
    {
        $validator = Validator::make(['module' => $module], [
            'module' => 'required|exists:voice_command_modules,module',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }
        try {
            $subModules = VoiceCommandSubModule::whereModule($module)->where('lang', $lang)->get();
            return (VoiceSubModuleListResource::collection($subModules))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function deleteModule($id = null)
    {
        $fields['id'] = $id;

        $validator = Validator::make($fields, [
            'id' => 'required|exists:voice_command_modules,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $module = VoiceCommandModule::find($id);
            $module->subModules->each(function ($subModule) {
                $subModule->commands->each->delete();
                $subModule->delete();
            });

            $module->delete();

            $this->setResponse(false, 'Voice Module Removed Successfully.');
            return response()->json($this->_response, 400);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function deleteSubModule($id = null)
    {
        $fields['id'] = $id;

        $validator = Validator::make($fields, [
            'id' => 'required|exists:voice_command_sub_modules,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $subModule = VoiceCommandSubModule::find($id);
            $subModule->commands->each->delete();
            $subModule->delete();

            $this->setResponse(false, 'Voice Sub Module Removed Successfully.');
            return response()->json($this->_response, 400);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
