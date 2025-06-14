<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\RoomController;
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
// Route::get('/trendingItems',[ItemController::class,'trendingItems']);
//عرض العناصر الاكثر مبيعا
// Route::get('/trendingRooms',[RoomController::class,'trendingRooms']);
//.
Route::get('/showCategories', [CustomerController::class, 'getAllCategories']);
//.
Route::get('/getRoomsByCategory/{category_id}', [RoomController::class, 'getRoomsByCategory']);
//.
Route::get('/getItemByRoom/{room_id}', [RoomController::class, 'getRoomItems']);
//.
Route::get('/showFerniture', [RoomController::class, 'showFurniture']);
//.
//.
//.
Route::get('/getFeedbackAndRatings', [RatingController::class, 'getFeedbackAndRatings']);

Route::get('filterItemsWithType', [CustomerController::class, 'filterItemsWithType']);


// Route::post('/payment/process', [CustomerController::class, 'processPayment']);





Route::middleware('auth:sanctum')->group(function () {
    //price
    //home_page:

    Route::get('/getItemDetails/{itemId}', [ItemController::class, 'getItemDetails']);


    Route::get('/Recommend', [RecommendationController::class, 'recommend']);
    //.
    Route::get('/showProfile', [ProfileController::class, 'showProfile']);
    //.
    Route::post('/updateProfile', [ProfileController::class, 'updateProfile']);
    //.
    Route::delete('/deleteProfile', [ProfileController::class, 'deleteProfile']);
    //.
    Route::post('/addFeedback', [RatingController::class, 'addFeedback']);
    //.
    Route::post('/addToFavorites', [FavoriteController::class, 'toggleFavorite']);
    //price
    //.
    Route::get('/getUserSpecificFeedback', [RatingController::class, 'getUserSpecificFeedback']);
    //.
    Route::post('/customizeItem/{item}', [ItemController::class, 'customizeItem']);
    //.
    Route::post('/customization-response/{itemId}', [ItemController::class, 'handleCustomizationResponse']);
    //اخد الوقت انه جمع وليس حسب الاطول
    //.
    Route::post('/addToCart', [CustomerController::class, 'addToCart']);
    //price
    Route::get('/viewCart', [CartController::class, 'viewCart']);
    //price
    //price
    Route::post('/cart_remove-partial', [CustomerController::class, 'removePartialFromCart']);
    // Route::post('/cart_decision', [CustomerController::class, 'handleCartDecision']);
    //.
    Route::delete('/deleteCart', [CustomerController::class, 'deleteCart']);
    //.
    Route::post('confirmCart', [CartController::class, 'confirmCart']);
    //.
    Route::post('/like_toggle', [FavoriteController::class, 'toggleLike']);
    //.
    Route::get('/customer_likes', [FavoriteController::class, 'getCustomerLikes']);
    //.
    Route::post('/complaints_submit', [ComplaintController::class, 'submitComplaint']);
    //.
    Route::get('/complaints_customer', [ComplaintController::class, 'getCustomerComplaints']);
    //غالباً لازم يكون ضمني مع تأكيد الطلب
    //.
    Route::post('/customer_location', [CustomerController::class, 'addDeliveryAddress']);

    // Route::post('/process-payment', [CustomerController::class, 'processPayment']);

    // Route::get('/availableTime', [HelperController::class, 'findAvailableDeliveryTime']);
    //price
    Route::get('/getItemCustomization/{itemId}', [ItemController::class, 'getItemCustomization']);
    //price
    Route::post('/customizeRoom/{item}', [RoomController::class, 'customizeRoom']);
    //price
    Route::get('/getRoomAfterCustomization/{roomCustomizationId}', [RoomController::class, 'getRoomAfterCustomization']);
    //price
    //اذا العدد اكبر من الموجود
    Route::post('/addtocart2', [CartController::class, 'addToCart2']);
});

//.
Route::get('/trending', [RecommendationController::class, 'getTrending']);

//.
Route::get('/exchange-rate/{from}/{to}', [HelperController::class, 'getExchangeRate']);


Route::get('/getType', [CustomerController::class, 'getType']);
Route::get('/getItemsByType/{typeId}', [CustomerController::class, 'getItemsByType']);



Route::get('/discount/{id}', [CustomerController::class, 'showDiscountDetails']);


//Ali
//----Auth
Route::post('/signup', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


Route::get('/searchItemsByTypeName', [CustomerController::class, 'searchItemsByTypeName']);


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/getFavoritesWithDetails', [FavoriteController::class, 'getFavoritesWithDetails']);
    Route::get('/getRoomDetails/{room_id}', [RoomController::class, 'getRoomDetails']);
    Route::get('/cart_details', [CartController::class, 'getCartDetails']);
    Route::post('/addToCartFavorite', [CustomerController::class, 'addToCartFavorite']);
    Route::post('/nearest-branch', [HelperController::class, 'getNearestBranch']);
    Route::post('/getDeliveryPrice', [HelperController::class, 'getDeliveryPrice']);
    Route::post('/ChargeInvestmentWallet', [CustomerController::class, 'ChargeInvestmentWallet']);
    Route::get('/getOrdersByCustomer', [CustomerController::class, 'getOrdersByCustomer']);
    Route::get('/wallet_balance', [CustomerController::class, 'getUserBalance']);
    Route::get('/GetAllOrders', [CustomerController::class, 'getAllOrders']);
    Route::get('/orders_details/{orderId}', [CustomerController::class, 'getOrderDetails']);
    Route::post('/orders_cancel/{orderId}', [CustomerController::class, 'cancelOrder']);
});