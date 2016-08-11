<?php

namespace Loren138\CASServer\Console;

use Loren138\CASServer\Models\CASAuthentication;
use Loren138\CASServer\Models\CASTicket;
use Illuminate\Console\Command;

class Cleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cas-server:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired authentication sessions and tickets';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ticket = new CASTicket();
        $tickets = $ticket->cleanup();
        $this->info('Deleted '.$tickets.' expired ticket(s).');

        $auth = new CASAuthentication();
        $auths = $auth->cleanup();
        $this->info('Deleted '.$auths.' expired authentication session(s).');
    }
}
