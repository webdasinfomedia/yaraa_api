<?php

namespace App\Http\Controllers;

use App\Http\Resources\PriorityResource;
use App\Models\Priority;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PriorityController extends Controller
{

    public function index()
    {
        try{
            $priorities = Priority::all();
            if($priorities){
                return (PriorityResource::collection($priorities))->additional(["error" => false, "message" => null]);
            }
            $this->setResponse(true, 'No Priorities Found.');
            return response()->json($this->_response,404);
        } catch(\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|regex:/^[\pL\s\-]+$/u|unique:priorities,name",
            "color" => ['required','regex:/^#(\d|a|b|c|d|e|f){6}$/i'],
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try{
            $priority = Priority::create($request->all());
            $priority->slug = Str::slug($request->name);
            $priority->save();
            $this->setResponse(false, 'Priority created successfully.');
            return response()->json($this->_response, 201);
        } catch(\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }        
    }

    public function getPriority($prioritySlug)
    {
        $fields = [ "slug" => $prioritySlug];
        $validator = Validator::make($fields, [
            "slug" => 'required|exists:priorities,slug',
        ]);
        
        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try{
            return (new PriorityResource(Priority::where('slug',$prioritySlug)->first()))->additional(["error" => false, "message" => null]);
        } catch(\Exception $e) {    
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
