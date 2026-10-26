<?php

namespace App\Providers;

use App\Models\Customer;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(fn (Customer $customer, string $url): MailMessage => (new MailMessage)
            ->subject('Apstiprini savu ShocoStore e-pastu')
            ->greeting('Sveiki, '.$customer->Vards.'!')
            ->line('Apstiprini savu e-pasta adresi, lai varētu veikt pasūtījumus ShocoStore.')
            ->action('Apstiprināt e-pastu', $url)
            ->line('Šī saite ir derīga 60 minūtes.')
            ->line('Ja neizveidoji kontu vai nemainīji e-pasta adresi, ignorē šo ziņu.'));
    }
}
