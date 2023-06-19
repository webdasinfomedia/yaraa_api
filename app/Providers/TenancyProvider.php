<?php
namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class TenancyProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    { 
        // dd($this->app['request']->getRequestUri());
        if(!in_array($this->app['request']->getRequestUri(), globalRoutes()))
        {
            try{
                $this->configureRequests();
                $this->configureQueue();
            } catch(\Throwable $e) {
                return response()->json([
                    "message" => "Invalid user account, Please contact support for more details.",
                    "error" => true,
                    "data" => null,
                ]);
            }
        }

        // $this->configureMasterDbQueue();
    }

    /**
     *
     */
    public function configureRequests()
    {
        // dd(auth()->payload()->get('host'));
        // dd($this->app['request']->header()['authorization']);
        if (! $this->app->runningInConsole()) {
            // dd(auth()->payload()->get('host'));
            $host = auth()->payload()->get('host');
            Tenant::whereDomain($host)->firstOrFail()->configure()->use();
        }
    }

    /**
     *
     */
    public function configureQueue()
    {
        $this->app['queue']->createPayloadUsing(function () {
            return $this->app['tenant'] ? ['tenant_id' => $this->app['tenant']->id] : [];
        });

        $this->app['events']->listen(JobProcessing::class, function ($event) {
            if (isset($event->job->payload()['tenant_id'])) {
                Tenant::find($event->job->payload()['tenant_id'])->configure()->use();
            }
        });
    }
    /**
     *
     */
    // public function configureMasterDbQueue()
    // {
    //     $this->app['queue']->createPayloadUsing(function () {
    //         if(isset($this->app['master_job'])){
    //             return ['master_job' => 'yes'];
    //         }else{
    //             return [];
    //         }
    //     });

    //     $this->app['events']->listen(JobProcessing::class, function ($event) {
    //         if (isset($event->job->payload()['master_job'])) {
    //             config([
    //                 'database.connections.tenant.database' => 'yaraa_master',
    //             ]);
    //             DB::purge('tenant');
    //             DB::reconnect('tenant');
    //             Schema::connection('tenant')->getConnection()->reconnect();
    //         }
    //     });
    // }
}