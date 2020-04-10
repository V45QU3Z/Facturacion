<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('ecommerce')->group(function() {
   // Route::get('/', 'EcommerceController@index');

    Route::get('/', 'EcommerceController@index')->name('tenant.ecommerce.index');
    Route::get('item/{id}', 'EcommerceController@item')->name('tenant.ecommerce.item');
    Route::get('items', 'EcommerceController@items')->name('tenant.ecommerce.item.index');
    Route::get('item_partial/{id}', 'EcommerceController@partialItem')->name('item_partial');
    Route::get('detail_cart', 'EcommerceController@detailCart')->name('tenant_detail_cart');
    Route::get('pay_cart', 'EcommerceController@pay')->name('tenant_pay_cart');
    Route::get('login', 'EcommerceController@showLogin')->name('tenant_ecommerce_login');
    Route::get('logout', 'EcommerceController@logout')->name('tenant_ecommerce_logout');
    Route::get('items_bar', 'EcommerceController@itemsBar');
    Route::post('login', 'EcommerceController@login')->name('tenant_ecommerce_login');
    Route::post('storeUser', 'EcommerceController@storeUser')->name('tenant_ecommerce_store_user');
    Route::post('rating_item', 'EcommerceController@ratingItem')->name('tenant_ecommerce_rating_item');
    Route::get('rating_item/{id}', 'EcommerceController@getRating');

    Route::post('culqi', 'CulqiController@payment')->name('tenant_ecommerce_culqui');
    Route::post('transaction_finally', 'EcommerceController@transactionFinally')->name('tenant_ecommerce_transaction_finally');
    Route::post('payment_cash', 'EcommerceController@paymentCash')->name('tenant_ecommerce_payment_cash');

});
