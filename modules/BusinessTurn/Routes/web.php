<?php

$hostname = app(Hyn\Tenancy\Contracts\CurrentHostname::class);

if($hostname) {
    Route::domain($hostname->fqdn)->group(function () {
        Route::middleware(['auth'])->group(function() {

            Route::prefix('bussiness_turns')->group(function () {

                Route::get('tables', 'BusinessTurnController@tables');
                Route::get('records', 'BusinessTurnController@records');
                Route::post('validate_hotel', 'BusinessTurnController@validate_hotel');
                Route::post('', 'BusinessTurnController@store');
                Route::get('', 'BusinessTurnController@index')->name('tenant.bussiness_turns.index');


            });
 
 

        });
    });
}