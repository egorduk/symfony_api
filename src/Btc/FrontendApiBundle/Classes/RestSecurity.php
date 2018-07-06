<?php

namespace Btc\FrontendApiBundle\Classes;

class RestSecurity
{
    const GOOGLE_AUTH_TURN_ON = 'on';
    const GOOGLE_AUTH_TURN_OFF = 'off';

    const ATTEMPT_GENERATE_PIN = 5;
    const CNT_REQUEST_GENERATE_PIN_KEY = 'request_generate_pin_%s';
}
