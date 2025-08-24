<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    UserAuthController,
    AdminAuthController,
    AdminDashboardController,
    CategoryController,
    RestaurantController,
    HotelController,
    EventHallController,
    EventHallDashboardController,
    PlayGroundController,
    RatingController,
    ReservationController,
    TourController,
    RestaurantDashboardController,
    PlayGroundDashboardController,
    TourDashboardController,
    HotelDashboardController,

};

/*
|--------------------------------------------------------------------------
| User Authentication
|--------------------------------------------------------------------------
*/
Route::prefix('user')->group(function () {
    Route::post('register', [UserAuthController::class, 'register']);
    Route::post('login', [UserAuthController::class, 'login']);
    Route::post('logout', [UserAuthController::class, 'logout']);
    Route::get('profile', [UserAuthController::class, 'get']);
        Route::post('update', [UserAuthController::class, 'update']);

});

/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('login', [AdminAuthController::class, 'login']);
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::get('profile', [AdminAuthController::class, 'get']);
});

/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/create', [CategoryController::class, 'create']);

});

/*
|--------------------------------------------------------------------------
| Restaurants
|--------------------------------------------------------------------------
*/
Route::prefix('restaurants')->group(function () {
    Route::get('/', [RestaurantController::class, 'index']);
    Route::post('reserve', [RestaurantController::class, 'reserve']);
    Route::get('reservations', [RestaurantController::class, 'reservations']);
});

/*
|--------------------------------------------------------------------------
| Hotels
|--------------------------------------------------------------------------
*/
Route::prefix('hotels')->group(function () {
    Route::get('/', [HotelController::class, 'index']);
    Route::get('rooms', [HotelController::class, 'rooms']);
    Route::post('reserve', [HotelController::class, 'reserve']);
    Route::get('reservations', [HotelController::class, 'reservations']);
});

/*
|--------------------------------------------------------------------------
| Event Halls
|--------------------------------------------------------------------------
*/
Route::prefix('event-halls')->group(function () {
    Route::get('/', [EventHallController::class, 'index']);
    Route::post('reserve', [EventHallController::class, 'reserve']);
    Route::get('reservations', [EventHallController::class, 'reservations']);
});

/*
|--------------------------------------------------------------------------
| Playgrounds
|--------------------------------------------------------------------------
*/
Route::prefix('playgrounds')->group(function () {
    Route::get('/', [PlayGroundController::class, 'index']);
    Route::post('reserve', [PlayGroundController::class, 'reserve']);
    Route::get('reservations', [PlayGroundController::class, 'reservations']);
});

/*
|--------------------------------------------------------------------------
| Tours
|--------------------------------------------------------------------------
*/
Route::prefix('tours')->group(function () {
    Route::get('/', [TourController::class, 'index']);
    Route::get('stops', [TourController::class, 'stops']);
    Route::post('reserve', [TourController::class, 'reserve']);
    Route::get('reservations', [TourController::class, 'reservations']);
});

/*
|--------------------------------------------------------------------------
| Reservations 
|--------------------------------------------------------------------------
*/
Route::prefix('reservations')->group(function () {

    Route::get('/', [ReservationController::class, 'reservations']);
    Route::get('/cancel', [ReservationController::class, 'cancel']);
});

/*
|--------------------------------------------------------------------------
| Rating
|--------------------------------------------------------------------------
*/
Route::prefix('rating')->group(function () {

    Route::post('/rate', [RatingController::class, 'rate']);
    Route::post('/edit', [RatingController::class, 'edit']);
    Route::get('/delete', [RatingController::class, 'delete']);
    Route::get('/bestRated', [RatingController::class, 'bestRated']);
    Route::get('/rates', [RatingController::class, 'rates']);
    Route::get('/average', [RatingController::class, 'average']);

});

/*
|--------------------------------------------------------------------------
| Admin Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('Admin-Dashboard')->group(function () {

    Route::get('/unblock', [AdminDashboardController::class, 'unblock']);
    Route::get('/users', [AdminDashboardController::class, 'users']);
    Route::get('/blockedUsers', [AdminDashboardController::class, 'blockedUsers']);
    Route::get('/coupons', [AdminDashboardController::class, 'coupons']);
    Route::post('/create-coupon', [AdminDashboardController::class, 'createCoupon']);
    Route::get('/user-Reservations', [AdminDashboardController::class, 'userReservations']);

});

/*
|--------------------------------------------------------------------------
| Restaurants Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('Restaurants-Dashboard')->group(function () {

    Route::post('/create', [RestaurantDashboardController::class, 'create']);
    Route::post('/delete', [RestaurantDashboardController::class, 'delete']);
    Route::get('/reservations', [RestaurantDashboardController::class, 'reservations']);
    Route::post('/close', [RestaurantDashboardController::class, 'close']);
    Route::post('/reject', [RestaurantDashboardController::class, 'reject']);

});

/*
|--------------------------------------------------------------------------
| PlayGrounds Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('PlayGrounds-Dashboard')->group(function () {

    Route::post('/create', [PlayGroundDashboardController::class, 'create']);
    Route::post('/delete', [PlayGroundDashboardController::class, 'delete']);
    Route::get('/reservations', [PlayGroundDashboardController::class, 'reservations']);
    Route::post('/close', [PlayGroundDashboardController::class, 'close']);
    Route::post('/reject', [PlayGroundDashboardController::class, 'reject']);

});


/*
|--------------------------------------------------------------------------
| EventHalls Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('EventHalls-Dashboard')->group(function () {

    Route::post('/create', [EventHallDashboardController::class, 'create']);
    Route::post('/delete', [EventHallDashboardController::class, 'delete']);
    Route::get('/reservations', [EventHallDashboardController::class, 'reservations']);
    Route::post('/close', [EventHallDashboardController::class, 'close']);
    Route::post('/reject', [EventHallDashboardController::class, 'reject']);

});


/*
|--------------------------------------------------------------------------
| Tours Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('Tours-Dashboard')->group(function () {

    Route::post('/create', [TourDashboardController::class, 'create']);
    Route::post('/delete', [TourDashboardController::class, 'delete']);
    Route::get('/reservations', [TourDashboardController::class, 'reservations']);
    Route::post('/reject', [TourDashboardController::class, 'reject']);
    Route::post('/create-stop', [TourDashboardController::class, 'createStop']);
    Route::post('/delete-stop', [TourDashboardController::class, 'deleteStop']);

});

/*
|--------------------------------------------------------------------------
| Hotels Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('Hotels-Dashboard')->group(function () {

    Route::post('/create', [HotelDashboardController::class, 'create']);
    Route::post('/delete', [HotelDashboardController::class, 'delete']);
    Route::get('/reservations', [HotelDashboardController::class, 'reservations']);
    Route::post('/reject', [HotelDashboardController::class, 'reject']);
    Route::post('/close', [HotelDashboardController::class, 'close']);
    Route::post('/create-room', [HotelDashboardController::class, 'createRoom']);
    Route::post('/delete-room', [HotelDashboardController::class, 'deleteRoom']);

});
