<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Models\Customer;
use App\Models\CustomizationOrder;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Auth
//.


//customer:
//homePage:
//التريندينغ بناء على الاكثر لايكات
//.
Route::get('/getTrending', [CustomerController::class, 'getTrending']);
//عرض الغرف الاكثر مبيعا
// Route::get('/trendingItems',[CustomerController::class,'trendingItems']);
//عرض العناصر الاكثر مبيعا
// Route::get('/trendingRooms',[CustomerController::class,'trendingRooms']);
//.
Route::get('/showCategories', [CustomerController::class, 'getAllCategories']);
//.
Route::get('/getRoomsByCategory/{category_id}', [CustomerController::class, 'getRoomsByCategory']);
//.
Route::get('/getItemByRoom/{room_id}', [CustomerController::class, 'getRoomItems']);
//.
Route::get('/showFerniture', [CustomerController::class, 'showFurniture']);
//.
//.
//.
Route::get('/getFeedbackAndRatings', [CustomerController::class, 'getFeedbackAndRatings']);

Route::get('filterItemsWithType', [CustomerController::class, 'filterItemsWithType']);


// Route::post('/payment/process', [CustomerController::class, 'processPayment']);



Route::middleware('auth:sanctum')->group(function () {
    //price
    //home_page:


    Route::post('/getItemDetails/{item_id}', [CustomerController::class, 'getItemDetails']);

    Route::get('/Recommend', [CustomerController::class, 'recommend']);
    //.
    Route::get('/showProfile', [CustomerController::class, 'showProfile']);
    //.
    Route::post('/updateProfile', [CustomerController::class, 'updateProfile']);
    //.
    Route::delete('/deleteProfile', [CustomerController::class, 'deleteProfile']);
    //.
    Route::post('/addFeedback', [CustomerController::class, 'addFeedback']);
    //.
    Route::post('/addToFavorites', [CustomerController::class, 'toggleFavorite']);
    //price
    //.
    Route::get('/getUserSpecificFeedback', [CustomerController::class, 'getUserSpecificFeedback']);
    //.
    Route::post('/customizeItem/{item}', [CustomerController::class, 'customizeItem']);
    //.
    Route::post('/customization-response/{itemId}', [CustomerController::class, 'handleCustomizationResponse']);
    //اخد الوقت انه جمع وليس حسب الاطول
    //.
    Route::post('/addToCart', [CustomerController::class, 'addToCart']);
    //price
    Route::get('/viewCart', [CustomerController::class, 'viewCart']);
    //price
    //price
    Route::post('/cart_remove-partial', [CustomerController::class, 'removePartialFromCart']);
    // Route::post('/cart_decision', [CustomerController::class, 'handleCartDecision']);
    //.
    Route::delete('/deleteCart', [CustomerController::class, 'deleteCart']);
    //.
    Route::post('confirmCart', [CustomerController::class, 'confirmCart']);
    //.
    Route::post('/like_toggle', [CustomerController::class, 'toggleLike']);
    //.
    Route::get('/customer_likes', [CustomerController::class, 'getCustomerLikes']);
    //.
    Route::post('/complaints_submit', [CustomerController::class, 'submitComplaint']);
    //.
    Route::get('/complaints_customer', [CustomerController::class, 'getCustomerComplaints']);
    //غالباً لازم يكون ضمني مع تأكيد الطلب
    //.
    Route::post('/customer_location', [CustomerController::class, 'addDeliveryAddress']);

    // Route::post('/process-payment', [CustomerController::class, 'processPayment']);

    // Route::get('/availableTime', [CustomerController::class, 'findAvailableDeliveryTime']);
    //price
    Route::get('/getItemCustomization/{itemId}', [CustomerController::class, 'getItemCustomization']);
    //price
    Route::post('/customizeRoom/{item}', [CustomerController::class, 'customizeRoom']);
    //price
    Route::get('/getRoomAfterCustomization/{roomCustomizationId}', [CustomerController::class, 'getRoomAfterCustomization']);
    //price
    //اذا العدد اكبر من الموجود
    Route::post('/addtocart2', [CustomerController::class, 'addToCart2']);
});

//.
Route::get('/trending', [CustomerController::class, 'getTrending']);

//.
Route::get('/exchange-rate/{from}/{to}', [CustomerController::class, 'getExchangeRate']);


Route::get('/getType', [CustomerController::class, 'getType']);
Route::get('/getItemsByType/{typeId}', [CustomerController::class, 'getItemsByType']);





//Ali
//----Auth
Route::post('/signup', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



Route::get('/searchItemsByTypeName', [CustomerController::class, 'searchItemsByTypeName']);


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/getFavoritesWithDetails', [CustomerController::class, 'getFavoritesWithDetails']);
    Route::get('/getRoomDetails/{room_id}', [CustomerController::class, 'getRoomDetails']);
    Route::get('/cart_details', [CustomerController::class, 'getCartDetails']);
    Route::get('/getItemDetails/{item_id}', [CustomerController::class, 'getItemDetails']);
    Route::post('/addToCartFavorite', [CustomerController::class, 'addToCartFavorite']);


});