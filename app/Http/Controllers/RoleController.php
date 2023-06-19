<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    private $_rules = [
        "name" => "required",
        "slug" => "required|regex:/^[a-z_-]*$/|unique:roles,slug",
        "is_admin" => "required|boolean"
    ];

    private function setRules(array $rules)
    {
        $this->_rules = array_merge($this->_rules,$rules);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->_rules);
        
        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try{
            $role = new Role;
            $role->name = $request->name;
            $role->slug = $request->slug;
            $role->is_admin = $request->is_admin ? true : false;
            $role->description = $request->description;
            $role->permission = $request->permission;
            if($role->save()){                
                $this->setResponse(false, 'role created successfully.');
                return response()->json($this->_response, 201);
            }
            dd($request);
        } catch (\Exception $e){
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function update(Request $request)
    {
        $this->setRules(['id' => 'required','description' => 'required', 'permission' => 'required', "slug" => "required|regex:/^[a-z_-]*$/",]);
        $validator = Validator::make($request->all(), $this->_rules);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        try {
            $role = Role::find($request->id);
            $role->name = $request->name;
            $role->slug = $request->slug;
            $role->description = $request->description;
            $role->permission = $request->permission;
            if($role->save()){
                $this->setResponse(false, 'role updated successfully.');
                return response()->json($this->_response);
            }
        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(),[
            "role_id" => "required",
            "migrate_id" => "required|different:role_id",
        ]);

        if ($validator->fails()) {
            $this->setResponse(true, $validator->errors()->all());
            return response()->json($this->_response, 400);
        }

        if(Role::find($request->role_id) == null || Role::find($request->migrate_id) == null){        
            $this->setResponse(true, "role or migrate id not exists.");
            return response()->json($this->_response, 500);
        }

        try {
           
            User::where('role_id', $request->role_id)->each(function($user) use($request){
                $user->role_id = $request->migrate_id;
                $user->save();
            });

            Role::find($request->role_id)->delete();
            $this->setResponse(false, 'role deleted successfully.');
            return response()->json($this->_response);

        } catch (\Exception $e) {
            $this->setResponse(true, $e->getMessage());
            return response()->json($this->_response, 500);
        }
    }
}
