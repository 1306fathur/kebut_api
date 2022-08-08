<?php

namespace App\Providers;

use Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $setting = DB::table('setting')->get()->toArray();
		$out = array();
		if(!empty($setting)){
			foreach($setting as $val){
				$out[$val->setting_key] = $val->setting_val;
			}
		}
		$config = array(
            'driver'     => 'smtp',
            'host'       => 'mail.andtechnology.me',
			'port'       => 465,
            'from'       => array('address' => $out['send_mail'], 'name' => 'Kebut Express'),
            'encryption' => 'ssl',
            'username'   => $out['send_mail'],
            'password'   => $out['mail_pass'],
            'sendmail'   => '/usr/sbin/sendmail -bs',
            'pretend'    => false,
        );
		Config::set('mail', $config);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
