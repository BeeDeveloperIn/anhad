<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderMail;

class SendOrderMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    public $setting;
    protected $is_admin = 0;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order, $setting,$is_admin = 0)
    {
        $this->order = $order;
        $this->setting = $setting;
        $this->is_admin = $is_admin;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = $this->order->billing_email;
        if($this->is_admin == 1){
            $email = $this->setting->email;
        }
        Mail::to($email)->send(new OrderMail($this->order,$this->setting,$this->is_admin));
    }
}
