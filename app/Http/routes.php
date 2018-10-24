<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// Hooks
Route::group(['namespace' => 'Hooks', 'prefix' => 'hooks'], function()
{
    Route::post('mailgun/{domain}', ['uses' => 'MailgunController@stored', 'as' => 'mailgun.{domain}.stored']);
});

// API
Route::group(['namespace' => 'Api\v1', 'prefix' => 'api/v1', 'middleware' => 'api'], function()
{
    Route::post('site', ['uses' => 'SiteController@store', 'as' => 'api.v1.site.store']);

    // Authenticated calls
    Route::group(['middleware' => ['auth:api']], function()
    {
        Route::post('site/{site}/user', ['uses' => 'SiteController@store_user', 'as' => 'api.v1.site.{site}.user.store']);
        Route::delete('site/{site}/user', ['uses' => 'SiteController@delete_user', 'as' => 'api.v1.site.{site}.user.delete']);
        Route::resource('site', 'SiteController', ['only' => ['index', 'show', 'update']]);

        Route::group(['middleware' => ['site.required']], function()
        {
            Route::get('user/{user}/contact', ['uses' => 'UserController@contacts', 'as' => 'api.v1.user.{user}.contanct.index']);
            Route::get('user/{user}/email_account/{email_account}/test', ['uses' => 'EmailAccountController@test_user_email_account', 'as' => 'api.v1.user.{user}.email_account.{email_account}.test']);
            Route::post('user/{user}/reasign', ['uses' => 'UserController@reasign', 'as' => 'api.v1.user.{user}.reasign']);
            Route::resource('user/{user}/email_account', 'EmailAccountController', ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
            Route::resource('user', 'UserController', ['only' => ['index', 'show', 'store', 'update']]);
            Route::resource('item', 'ItemController', ['only' => ['index', 'show', 'store', 'update']]);
            Route::post('contact/{contact}/item', ['uses' => 'ContactController@store_item', 'as' => 'api.v1.contact.{contact}.item.store']);
            Route::delete('contact/{contact}/item', ['uses' => 'ContactController@delete_item', 'as' => 'api.v1.contact.{contact}.item.delete']);
            Route::resource('contact', 'ContactController', ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
            Route::get('ticket/status', ['uses' => 'TicketController@status', 'as' => 'api.v1.ticket.status']);
            Route::get('ticket/source', ['uses' => 'TicketController@source', 'as' => 'api.v1.ticket.source']);
            Route::resource('ticket', 'TicketController', ['only' => ['index', 'show', 'store', 'update', 'destroy']]);
            Route::resource('ticket/{ticket}/message', 'MessageController', ['only' => ['index', 'show', 'store']]);

            Route::get('stats/contact', ['uses' => 'StatsController@contact', 'as' => 'api.v1.stats.contact']);
            Route::get('stats/item', ['uses' => 'StatsController@item', 'as' => 'api.v1.stats.item']);
            Route::get('stats/user', ['uses' => 'StatsController@user', 'as' => 'api.v1.stats.user']);

            Route::get('email_account/{protocol}', ['uses' => 'EmailAccountController@test', 'as' => 'api.v1.email_account.{protocol}']);
        });
    });
});

Route::get('queue-monitor', function () {
    return Response::view('queue-monitor::status-page');
});

Route::get('queue-monitor.json', function () {
    $response = Response::view('queue-monitor::status-json', [
        'options' => \JSON_PRETTY_PRINT,
    ]);
    $response->header('Content-Type', 'application/json');
    return $response;
});
