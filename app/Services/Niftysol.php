<?php 

namespace App\Services;

use Illuminate\Support\Facades\App;

class Niftysol
{
	
	 /** @var niftysol api handle url */
	//private static $url = 'http://192.168.1.29/priyal/laravel/niftysol_onboarding/api/apifunction'; //LOCAL 
	//private static $url = 'https://accounts.niftysol.com/api/apifunction'; 
	
	
	/**
     * @param array $fields
     */	
	public function call(array $fields)
	{		
	
		$hostname = $_SERVER['HTTP_HOST'];
		if(App::environment('production'))
		{
			$url = 'https://accounts.niftysol.com/api/apifunction';
		}else
		{
			$url = 'https://test.accounts.niftysol.com/api/apifunction';  
		}
	
		if(!is_array($fields))
		{
			die;
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields); //fields must include api type. "eg: updateUserEmail,deleteUserToken"
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		$response = curl_exec($ch);
		
		curl_close($ch);
		return $response;
	}
}