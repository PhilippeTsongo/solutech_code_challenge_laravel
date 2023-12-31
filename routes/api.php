<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
*/

use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BookLoanController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\UserRoleController;
use App\Http\Controllers\BorrowBookController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;


    //optimization routes
    Route::get('/optimize', function(){
        $exitCode = Artisan::call('optimize');
        return 'DONE';
    });

    //caching clear route
    Route::get('/cache', function(){
        $exitCode = Artisan::call('cache:clear');
        $exitCode = Artisan::call('config:cache');
        return 'DONE';
    });

    
    
    //user authentication route
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');

    //Auth routes
    Route::middleware('auth:sanctum')->group(function () {

        //user
        Route::get('/user/authenticated', [AuthenticatedSessionController::class, 'authenticatedUser']);

        Route::resource('/user/role', UserRoleController::class);
        Route::resource('/user', UserController::class);

        //user status
        Route::post('/user/activate/status/{user}', [UserController::class, 'activateUserStatus']);
        Route::post('/user/deactivate/status/{user}', [UserController::class, 'deactivateUserStatus']);
        
        //Log out
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
         
        //book
        Route::resource('/book', BookController::class);

        //book status
        Route::post('/book/status/available/{book}', [BookController::class, 'availableStatus']);
        Route::post('/book/status/unavailable/{book}', [BookController::class, 'unavailableStatus']);

        //book loan 
        Route::resource('/loan', BookLoanController::class);
        //borrow a book
        Route::post('/book/borrow/{bookId}', [BookLoanController::class, 'borrowBook']);
        
        //Book loan status
        Route::post('/loan/approve/{loan}', [BookLoanController::class, 'ApproveBookLoan']);
        Route::post('/loan/reject/{loan}', [BookLoanController::class, 'RejectBookLoan']);

        //Book loan Extend date
        Route::post('/extend/loan/{loan}', [BookLoanController::class, 'extendLoan']);

        Route::post('/loan/return/{loan}', [BookLoanController::class, 'returnBook']);

        
        Route::resource('/category', CategoryController::class);
        Route::resource('/subcategory', SubcategoryController::class);        

        Route::get('/dashboard/book', [DashboardController::class, 'books']);
        Route::get('/dashboard/loan', [DashboardController::class, 'loans']);
        Route::get('/dashboard/user', [DashboardController::class, 'users']);


    });
