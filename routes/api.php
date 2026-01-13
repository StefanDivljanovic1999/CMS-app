<?php

use App\Http\Controllers\AuthApiController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostCategoryController;
use App\Http\Controllers\PostController;
use App\Models\PostCategory;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


//api ruta za registraciju korisnika
Route::post('/register', [AuthApiController::class, 'register']);

//api ruta za prijavljivanje korisnika gde cemo kreirati token pri prijavi
Route::post('/login', [AuthApiController::class, 'login']);

//skoro sve naredne rute koje cemo kreirati moraju da koriste ovaj token koji smo pri prijavi kreirali
//zato cemo ih sve grupisati
Route::group(['middleware' => 'auth:sanctum'], function () {

    //ruta koja nam sluzi za prikaz profila 
    Route::get('/profile', [AuthApiController::class, 'profile']);

    //ruta za odjavu kojom se brise token koji je kreiran pri prijavi
    Route::post('/logout', [AuthApiController::class, 'logout']);

    //Ruta kojom se dobijaju crud funkcije index i show preko kojih mogu da se pregledaju postovi
    Route::apiResource('post_categories', PostCategoryController::class)->only(['index', 'show'])->middleware('role:admin,author,reader');

    //Ruta kojom se dobijaju crud funkcije store, update i delete kojima mogu da upravljaju samo author i admin uloge
    Route::apiResource('post_categories', PostCategoryController::class)->except(['index', 'show'])->middleware('role:admin');

    //CRUD rute(index,show) koje su dostupne svim ulogama(aadmin,author,reader)
    Route::apiResource('posts', PostController::class)->only('index', 'show')->middleware('role:admin,author,reader');

    //Restriktovani deo CRUD rute(store,update i delete) koje su dostupne samo za admin i author uloge
    Route::apiResource('posts', PostController::class)->except('index', 'show')->middleware('role:admin,author');

    //Ruta koja omogucava adminu da odobri post koji je kreirao author
    Route::put('posts/approve/{id}', [PostController::class, 'approve'])->middleware('role:admin');

    //Ruta kojom se kreira post operacija za Like model kojim se ostavlja i uklanja lajk sa post-a
    Route::post('posts/react', [LikeController::class, 'react']);

    //Ruta kojom se vidi ko je autor post-a
    Route::get('posts/author/{id}', [PostController::class, 'author']);

    //Ruta kojom se omogucava svim korisnicima da mogu da pregledaju stranice(pages)
    Route::apiResource('pages', PageController::class)->only('index', 'show');

    //Ruta kojom se kreiraju meniji i koja je dozovljena svima za pregled
    Route::apiResource('menus', MenuController::class)->only('index', 'show');

    //Ruta kojom se kreiraju meniji i koja je dozovljena samo adminima
    Route::apiResource('menus', MenuController::class)->except('index', 'show');

    //ruta za pregled sajta
    Route::get('menus/preview/{slug}', [MenuController::class, 'previewSite']);

    //Ruta kojom se omogucava adminu da manipulise stranicama(store,update,delet
    Route::apiResource('pages', PageController::class)->except('index', 'show')->middleware('role:admin');
});

//ruta za pregled stranica
Route::get('pages/preview/{slug}', [PageController::class, 'previewPage']);

//ruta za pregled lajkova
Route::apiResource('/likes', LikeController::class);

//ruta kojom se uploaduje slika
Route::post('/upload-image', [ImageController::class, 'store']);
