<?php

namespace App\Http\Controllers;

use App\Http\Resources\VoiceCommandListResource;
use App\Jobs\VoiceCommandUpdatePreferenceSaveJob;
use App\Models\VoiceCommand;
use App\Models\VoiceCommandUpdateHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoiceCommandController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'command' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\@']+$/u|max:200",
            'command' => "required|max:200",
            'sub_module' => "required|exists:voice_command_sub_modules,sub_module",
            'lang' => "required|max:10",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            VoiceCommand::create([
                'command' => $request->command,
                'sub_module' => $request->sub_module,
                'lang' => $request->lang,
            ]);

            //Update user preferences to update commands on end user APP
            // dispatch(new VoiceCommandUpdatePreferenceSaveJob($request->sub_module, $request->lang));

            //Keep update history to update command on end user devices
            VoiceCommandUpdateHistory::create([
                "sub_module" => $request->sub_module, "lang" => $request->lang
            ]);

            //Keep update history to update command on end user devices
            VoiceCommandUpdateHistory::create([
                "sub_module" => $request->sub_module, "lang" => $request->lang
            ]);

            $this->setResponse(false, "Voice Command Added Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getCommandList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sub_module' => 'required|exists:voice_command_sub_modules,sub_module',
            'lang' => 'required',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $commands = VoiceCommand::whereSubModule($request->sub_module)->whereLang($request->lang)->get();
            return (VoiceCommandListResource::collection($commands))->additional(['error' => false, 'message' => null]);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => "required|exists:voice_commands,_id",
            // 'command' => "required|regex:/^[\pL\pN\s\-\_\,\?\&\.\(\)\@']+$/u|max:200",
            'command' => "required|max:200",
            // 'sub_module' => "required|regex:/^[\pL\pN\s\-\_]+$/u|exists:voice_command_sub_modules,sub_module",
            'sub_module' => "required|exists:voice_command_sub_modules,sub_module",
            'lang' => "required|max:10",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $vCommand = VoiceCommand::find($request->id);
            // $vCommand->sub_module = $request->sub_module;
            $vCommand->command = $request->command;
            $vCommand->lang = $request->lang;
            $vCommand->save();

            //Update user preferences to update commands on end user APP
            // dispatch(new VoiceCommandUpdatePreferenceSaveJob($request->sub_module, $request->lang));

            //Keep update history to update command on end user devices
            VoiceCommandUpdateHistory::create([
                "sub_module" => $request->sub_module, "lang" => $request->lang
            ]);

            $this->setResponse(false, "Voice Command Updated Successfully.");
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function deleteCommand($id = null)
    {
        $fields['id'] = $id;

        $validator = Validator::make($fields, [
            'id' => 'required|exists:voice_commands,_id',
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $command = VoiceCommand::find($id);
            $command->delete();

            $this->setResponse(false, 'Voice Command Removed Successfully.');
            return response()->json($this->_response, 400);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function getUpdateHistory()
    {
        try {
            $updatedHistory = VoiceCommandUpdateHistory::select('sub_module', 'lang', 'created_at')->get();
            $updatedHistory->makeHidden('_id');

            $this->_response['data'] = $updatedHistory;
            return response()->json($this->_response, 200);
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
