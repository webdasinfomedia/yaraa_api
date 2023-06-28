<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Events\PusherEvent;
use App\Mail\TestMail;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Stripe\StripeClient;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$router->get('/test', function () {
    return "Welcome to API server.There is nothing to see.";
});


$router->get('/', function () {
    return "Welcome to API server.There is nothing to see.";
});

// $router->get('/provider/{provider}', function ($provider){
//     return Socialite::driver($provider)->stateless()->redirect();
// });

// $router->get('/getcode/{provider}', function ($provider){ 
//     $providerUser = Socialite::driver($provider)->stateless()->user();
//     dd($providerUser);  
// });

$router->group(['prefix' => 'assets', 'middleware' => 'cors'], function ($router) {
    $router->get('{module}/{type}/{attachment}', function (Request $request, $module, $type, $attachment) {
        $sp = DIRECTORY_SEPARATOR;
        $filePath = base_path() . "{$sp}assets{$sp}{$module}{$sp}{$type}{$sp}{$attachment}";

        /* $response = $next($request);
        $response->headers->set('Access-Control-Allow-Origin' , '*');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, Application');
        */
        return response()->download($filePath);

        // return Storage::download($filePath);

        $type = 'image/png';
        $headers = ['Content-Type' => $type];
        return  new BinaryFileResponse($filePath, 200, $headers);

        return response()->download($filePath, null, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ], null);
    });
});

$router->post('stripe/webhook', 'StripeWebhookController@handleWebhook');

$router->group(['prefix' => 'api'], function ($router) {

    $router->post('/password/email', 'PasswordController@sendResetLinkEmail');
    $router->post('/password/reset', 'PasswordController@resetPassword');

    $router->post('auth/login/verify', 'AuthController@verifySocialLogin');
    $router->post('auth/linkedin/login', 'AuthController@getLinkedinAccessToken');
    $router->post('auth/apple/login', 'AuthController@getAppleAccessToken');
    $router->get('auth/apple/getcode', 'AuthController@getAppleCode');
    $router->get('auth/apple/getsecret', 'AuthController@generateAppleSecret');

    $router->post('user/create', 'AuthController@register');
    $router->post('user/login', 'AuthController@login');

    $router->get('verify/{token}', 'UserController@verifyEmail');
    $router->post('sso_login', 'UserController@ssoLogin');

    $router->post('onboarding', 'OnboardingController@index');
    $router->post('app-sumo-onboarding', 'AppSumoSignupController@signup');
    $router->post('pitchground-onboarding', 'PitchGroundSignupController@signup');
    $router->post('dealfuel-onboarding', 'DealFuelSignupController@signup');
    $router->post('plan-updated', 'PlanController@planUpdated');

    $router->group(['middleware' => 'auth'], function ($router) {
        $router->get('/test', function () {
            //
        });

        // $router->group(['prefix' => 'storage'], function ($router) {
        //     $router->get('/task/{any}/{attachment}', function(){
        //         dd('called');
        //     });
        // });

        $router->group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => 'checkAdminRole'], function ($router) {
            $router->get('member/list', 'AdminTeamController@getMemberList');
            $router->get('get-plan-upgrade-link', 'AdminPlanController@getPlanUpgradeLink');
        });
        $router->group(['prefix' => 'plan',], function ($router) {
            $router->get('details', 'PlanController@getPlanDetails');
        });


        $router->get('check_user_limit_consumed', function () {
            $isLimitReached = isUserLimitReached();
            return response()->json([
                'data' => ['limit_reached' => $isLimitReached],
                'error' => false,
                'message' => $isLimitReached ? 'User Limit Reached,Please upgrade plan or delete user to add more.' : null,
            ], 200);
        });

        $router->get('/pusher/{message}', function ($message) {
            broadcast(new PusherEvent($message));
        });

        $router->post('broadcasting/auth', 'BroadcastController@authenticate');


        $router->post('logout', 'AuthController@logout');
        $router->post('refresh', 'AuthController@refresh');
        $router->post('me', 'AuthController@me');

        $router->post('search', 'SearchController@search'); //global search

        $router->post('notification_settings', 'NotificationSettingController@setSettings');
        $router->get('notification_settings', 'NotificationSettingController@getSettings');

        $router->group(["prefix" => "marketplace"], function ($router) {
            $router->post('add/zoom', 'AuthController@getZoomAccessToken');
            $router->delete('remove/zoom', 'SettingsController@removeZoomApp');
        });

        $router->group(["prefix" => "settings"], function ($router) {
            $router->get('my/apps', 'SettingsController@getMyApps');
            $router->put('customer/{status}', 'SettingsController@integrateCustomerModule');
            $router->get('customer/status', 'SettingsController@isCustomerEnabled');
            $router->post('company/profile/update', 'SettingsController@updateCompanyProfile');
            $router->put('vc/language/{language}/enable', 'SettingsController@changeLanguage');
            $router->post('customer/form_link', 'SettingsController@customerFormLink');
            $router->get('customer/form_link', 'SettingsController@getCustomerFormLink');
        });

        $router->group(["prefix" => "user"], function ($router) {
            $router->post('profile/update', 'UserController@update');
            $router->post('preferences/save', 'UserController@savePreferences');
            $router->get('profile/get', 'UserController@getProfile');
            $router->get('dashboard', 'UserController@index');
            $router->get('notifications', 'NotificationController@index');
            $router->get('notifications/read', 'NotificationController@readNotification');
            $router->get('organizations', 'OrganizationController@getOrganizationList');
            $router->post('organizations/switch', 'OrganizationController@switchOrganization');
            $router->post('location', 'LogLocationController@store');
            $router->post('live-location', 'LogLocationController@getLocation');
            $router->delete('account', 'UserController@deleteUser');
        });

        /** Role module Routes ***/
        $router->group(['prefix' => 'role'], function ($router) {
            $router->post('create', 'RoleController@store');
            $router->post('update', 'RoleController@update');
            $router->post('delete', 'RoleController@delete');
        });

        $router->group(['prefix' => 'project'], function ($router) {
            $router->post('create', 'ProjectController@store');
            $router->post('update', 'ProjectController@update');
            $router->post('list', 'ProjectController@index');
            $router->get('{projectId}', 'ProjectController@getProject');
            $router->get('favourite/{projectId}', 'ProjectController@markAsFavourite');
            $router->get('favourite/{projectId}', 'ProjectController@markAsFavourite');
            $router->get('unfavourite/{projectId}', 'ProjectController@markAsUnfavourite');
            $router->post('invite/member', 'ProjectController@inviteMember');
            $router->post('complete', 'ProjectController@complete');
            $router->post('reopen', 'ProjectController@reOpen');
            $router->post('destroy', 'ProjectController@delete');
            $router->post('destroy/all', 'ProjectController@deleteMultipleProject');
            $router->get('{projectId}/all_tasks', 'ProjectController@getAllTaskByProject');
            $router->get('{projectId}/timeline', 'ProjectController@getTimeline');
            $router->post('csv/import', 'ProjectController@csvImport');
        });

        $router->group(['prefix' => 'milestone'], function ($router) {
            $router->get('for-project/{projectId}', 'MilestoneController@getMilestones');
            $router->post('edit', 'MilestoneController@editMilestones');
            $router->post('create', 'MilestoneController@addMilestones');
            $router->post('complete', 'MilestoneController@markAsComplete');
            $router->post('delete', 'MilestoneController@delete');
            $router->post('view', 'MilestoneController@get');
        });

        /*
        * Task Module Routes
        */
        $router->group(['prefix' => 'task'], function ($router) {
            $router->post('create', 'TaskController@store');
            $router->post('list', 'TaskController@index');
            $router->post('update', 'TaskController@update');
            $router->get('{task_id}', 'TaskController@getTask');
            $router->post('start', 'TaskController@startTask');
            $router->post('complete', 'TaskController@markAsComplete');
            $router->post('complete/all', 'TaskController@markAsCompleteAll');
            $router->post('invite/member', 'TaskController@inviteMember');
            $router->post('reopen', 'TaskController@reOpen');
            $router->post('reopen/all', 'TaskController@reOpenAllTask');
            $router->post('pause', 'TaskController@pause');
            $router->post('resume', 'TaskController@resume');
            $router->post('destroy', 'TaskController@delete');
            $router->post('destroy/all', 'TaskController@deleteMultipleTask');
            $router->post('move/project', 'TaskController@moveToProject');
            $router->post('copy', 'TaskController@copyTask');
            $router->post('leave', 'TaskController@leaveTask');
        });

        $router->group(['prefix' => 'task/comments'], function ($router) {
            $router->post('list', 'TaskCommentController@index');
            $router->post('create', 'TaskCommentController@store');
            $router->post('log-location-history', 'TaskCommentController@addLocation');
            $router->post('mark/important', 'TaskCommentController@markAsImportant');
            $router->post('mark/important/remove', 'TaskCommentController@markAsUnimportant');
        });

        $router->group(['prefix' => 'team'], function ($router) {
            $router->post('create', 'TeamController@store');
            $router->post('invite/member', 'TeamController@inviteMember');
            $router->post('user/disable', ['middleware' => 'checkAdminRole', 'uses' => 'TeamController@disableUser']);
            $router->post('user/enable', ['middleware' => 'checkAdminRole', 'uses' => 'TeamController@enableUser']);
            $router->delete('user/delete/{email}', ['middleware' => 'checkAdminRole', 'uses' => 'TeamController@deleteUser']);
        });

        $router->group(['prefix' => "member"], function ($router) {
            $router->get('list', 'UserController@memberList');
            $router->get('invite/{email}', 'UserController@inviteMember');
            $router->post('show/tasks', 'UserController@showMemberTask');
        });

        $router->group(['prefix' => "priority"], function ($router) {
            $router->post('create', 'PriorityController@create');
            $router->get('list', 'PriorityController@index');
            $router->get('get/{prioritySlug}', 'PriorityController@getPriority');
        });

        $router->group(['prefix' => "todo"], function ($router) {
            $router->post('create', 'TodoController@createTodo');
            $router->post('edit', 'TodoController@editTodo');
            $router->get('get/{todoId}', 'TodoController@getTodo');
            $router->post('complete', 'TodoController@complete');
            $router->post('delete', 'TodoController@delete');
            $router->post('reopen', 'TodoController@reopen');
        });

        $router->group(["prefix" => "tag"], function ($router) {
            $router->get('list', 'TagController@list');
        });

        $router->group(["prefix" => "subtask"], function ($router) {
            $router->post('create', 'SubTaskController@create');
            $router->post('update', 'SubTaskController@update');
            $router->post('delete', 'SubTaskController@delete');
            $router->post('complete', 'SubTaskController@markAsComplete');
            $router->post('reopen', 'SubTaskController@reOpen');

            // retired API start            
            $router->post('add-assignee', 'SubTaskController@AddAssignee');
            $router->get('remove-assignee/{subtask_id}', 'SubTaskController@removeAssignee');
            // retired API end
        });

        $router->group(["prefix" => "archive"], function ($router) {
            $router->post('project', 'ArchiveController@archiveProject');
            $router->post('task', 'ArchiveController@archiveTask');
            $router->post('task/all', 'ArchiveController@archiveAllTask');
            $router->get('list', 'ArchiveController@itemsList');
        });

        $router->group(["prefix" => "unarchive"], function ($router) {
            $router->post('project', 'ArchiveController@unArchiveProject');
            $router->post('project/all', 'ArchiveController@unArchiveAllProject');
            $router->post('task', 'ArchiveController@unArchiveTask');
            $router->post('task/all', 'ArchiveController@unArchiveAllTask');
        });

        $router->group(["prefix" => "chat"], function ($router) {
            $router->get('message/list', 'MessageController@memberMessageList');
            $router->post('message/send', 'MessageController@send');
            $router->post('group/create', 'ConversationController@createGroup');
            $router->post('message/history', 'MessageController@getMessageHistory');
            $router->post('message/mark/read', 'MessageController@markAsRead');
            $router->post('group/add/members', 'MessageController@addMember');
            $router->post('group/edit', 'ConversationController@editGroup');
            $router->post('group/leave', 'ConversationController@leaveGroup');
            $router->post('clear/messages', 'MessageController@clearMessages');
            $router->post('group_details', 'ConversationController@groupDetails');
            $router->post('conversation/clean', 'ConversationController@cleanConversation');
            $router->delete('group/{groupId}', 'ConversationController@deleteConversation');
        });

        $router->group(['prefix' => 'customer'], function ($router) {
            $router->get('list', 'CustomerController@index');
            $router->post('create', 'CustomerController@store');
            $router->get('current/projects',  ['middleware' => 'ensureRole:customer', 'uses' => 'CustomerController@getCurrentProjects']);
            $router->get('past/projects', ['middleware' => 'ensureRole:customer', 'uses' => 'CustomerController@getPastProjects']);
        });

        $router->group(['prefix' => 'zoom', 'middleware' => 'checkZoomToken'], function ($router) {
            $router->post('create_meeting', 'ZoomController@createMeeting');
            $router->post('delete_meeting', 'ZoomController@deleteMeeting');
        });

        $router->group(['prefix' => 'meet'], function ($router) {
            $router->post('add-meeting', 'TaskCommentController@addGoogleMeetDetails');
        });

        $router->group(['prefix' => 'vc'], function ($router) {
            $router->get('modules/{lang}', 'VoiceCommandModuleController@getModulesList');
            $router->get('sub-modules/{module}/{lang}', 'VoiceCommandModuleController@getSubModulesList');
            $router->post('create-module', 'VoiceCommandModuleController@createModule');
            $router->post('update-module', 'VoiceCommandModuleController@updateModule');
            $router->post('create-sub-module', 'VoiceCommandModuleController@createSubModule');
            $router->post('update-sub-module', 'VoiceCommandModuleController@updateSubModule');
            $router->delete('module/{id}', 'VoiceCommandModuleController@deleteModule');
            $router->delete('sub-module/{id}', 'VoiceCommandModuleController@deleteSubModule');
            #####
            $router->post('add-voice-command', 'VoiceCommandController@store');
            $router->get('voice-commands', 'VoiceCommandController@getCommandList');
            $router->post('update-command', 'VoiceCommandController@update');
            $router->delete('command/{id}', 'VoiceCommandController@deleteCommand');
            $router->get('updated/history', 'VoiceCommandController@getUpdateHistory');
        });

        $router->group(['prefix' => 'reports', 'middleware' => ['ensureRole:admin']], function ($router) {
            $router->post('task/completion', 'ReportsController@getTaskCompletionReport');
            $router->post('timesheet', 'ReportsController@getTimesheetReport');
        });

        $router->group(['prefix' => 'reports', 'middleware' => ['ensureRole:admin|employee']], function ($router) {
            $router->post('attendance', 'ReportsController@getAttendanceReport');
        });

        $router->group(['prefix' => 'punch', 'middleware' => ['ensureRole:admin|employee']], function ($router) {
            $router->post('in', 'PunchDetailController@punchIn');
            $router->post('out', 'PunchDetailController@punchOut');
            $router->get('details', 'PunchDetailController@getTodaysDetails');
        });
    });
});
