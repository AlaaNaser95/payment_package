<?php

namespace beinmedia\payment\Commands;

use Illuminate\Console\Command;

class CreateWebhookCommand extends Command
{
    protected $signature = 'create:webhook';

    protected $description = 'Create Paypal Webhook';

    public function __construct() {
        parent::__construct();
    }

    public function handle() {
        $s=new \beinmedia\payment\Services\PaypalRecurring();
        $s->createWebhook();
    }
}
