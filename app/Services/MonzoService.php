<?php

namespace App\Services;

class MonzoService
{
    private string $uri;
    private string $clientId;
    private string $redirectUri;
    private string $state;

    public function __construct()
    {
        $this->uri = config('services.monzo.auth_uri');
        $this->clientId = env('MONZO_CLIENT_ID');
        $this->redirectUri = env('MONZO_REDIRECT_URI');
        $this->state = csrf_token(); 
    }

    public function redirectToMonzo()
    {
        $url = "https://auth.monzo.com/?client_id=$this->clientId&redirect_uri=$this->redirectUri&response_type=code&scope=read balance&state=$this->state";
        return redirect()->away($url);
    }

    public function handleCallback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $redirectUri = env('MONZO_REDIRECT_URI');
        $clientId = env('MONZO_CLIENT_ID');

        $response = Http::asForm()->post($this->uri, [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'code' => $code,
            'state' => $state,
        ]);

        return $response->json();
    }
}